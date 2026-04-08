<?php
/**
 * معالجة عملية الدفع - دار السودان
 * api/process_payment.php
 */

// تفعيل عرض الأخطاء للتطوير
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // التحقق من تسجيل الدخول
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
        exit;
    }

    // التحقق من نوع الطلب
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير صحيحة']);
        exit;
    }

    // جلب وتنظيف البيانات
    $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitizeInput($_POST['payment_method']) : '';
    $buyer_name = isset($_POST['buyer_name']) ? sanitizeInput($_POST['buyer_name']) : '';
    $buyer_phone = isset($_POST['buyer_phone']) ? sanitizeInput($_POST['buyer_phone']) : '';
    $buyer_email = isset($_POST['buyer_email']) ? sanitizeInput($_POST['buyer_email']) : '';
    $buyer_address = isset($_POST['buyer_address']) ? sanitizeInput($_POST['buyer_address']) : '';
    $notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';

    // التحقق من البيانات المطلوبة
    if ($property_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'رقم العقار غير صحيح']);
        exit;
    }

    if (empty($payment_method)) {
        echo json_encode(['success' => false, 'message' => 'يجب اختيار طريقة الدفع']);
        exit;
    }

    if (empty($buyer_name) || empty($buyer_phone) || empty($buyer_email)) {
        echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع البيانات المطلوبة']);
        exit;
    }

    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($buyer_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير صحيح']);
        exit;
    }

    // جلب بيانات العقار
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as owner_name, u.email as owner_email 
        FROM properties p
        LEFT JOIN users u ON p.owner_id = u.user_id
        WHERE p.property_id = ? AND p.status = 'active'
    ");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch();

    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'العقار غير متاح للشراء']);
        exit;
    }

    // التأكد من أن المشتري ليس المالك
    if ($property['owner_id'] == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'لا يمكنك شراء عقارك الخاص']);
        exit;
    }

    // حساب المبالغ
    $sale_price = floatval($property['price']);
    $service_fee = $sale_price * 0.02; // 2%
    $tax = $sale_price * 0.05; // 5%
    $total_amount = $sale_price + $service_fee + $tax;

    // توليد رقم طلب فريد
    $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // بدء معاملة قاعدة البيانات
    $pdo->beginTransaction();

    try {
        // التحقق من وجود جدول المبيعات
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'sales'");
        if ($tableCheck->rowCount() === 0) {
            // إنشاء جدول المبيعات إذا لم يكن موجوداً
            $pdo->exec("
                CREATE TABLE sales (
                    sale_id INT PRIMARY KEY AUTO_INCREMENT,
                    order_number VARCHAR(50) UNIQUE NOT NULL,
                    property_id INT NOT NULL,
                    buyer_id INT NOT NULL,
                    seller_id INT NOT NULL,
                    sale_price DECIMAL(15, 2) NOT NULL,
                    service_fee DECIMAL(15, 2) DEFAULT 0,
                    tax DECIMAL(15, 2) DEFAULT 0,
                    total_amount DECIMAL(15, 2) NOT NULL,
                    payment_method ENUM('card', 'bank', 'wallet', 'cash') NOT NULL,
                    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
                    buyer_name VARCHAR(100) NOT NULL,
                    buyer_phone VARCHAR(20) NOT NULL,
                    buyer_email VARCHAR(100) NOT NULL,
                    buyer_address TEXT,
                    notes TEXT,
                    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    completed_date TIMESTAMP NULL,
                    FOREIGN KEY (property_id) REFERENCES properties(property_id),
                    FOREIGN KEY (buyer_id) REFERENCES users(user_id),
                    FOREIGN KEY (seller_id) REFERENCES users(user_id),
                    INDEX idx_order (order_number),
                    INDEX idx_buyer (buyer_id),
                    INDEX idx_status (payment_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // إدراج عملية البيع
        $stmt = $pdo->prepare("
            INSERT INTO sales (
                order_number, property_id, buyer_id, seller_id,
                sale_price, service_fee, tax, total_amount,
                payment_method, payment_status,
                buyer_name, buyer_phone, buyer_email, buyer_address, notes,
                completed_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            $order_number,
            $property_id,
            $_SESSION['user_id'],
            $property['owner_id'],
            $sale_price,
            $service_fee,
            $tax,
            $total_amount,
            $payment_method,
            $buyer_name,
            $buyer_phone,
            $buyer_email,
            $buyer_address,
            $notes
        ]);

        if (!$result) {
            throw new Exception('فشل في إنشاء عملية البيع');
        }

        $sale_id = $pdo->lastInsertId();

        // تحديث حالة العقار إلى "مباع"
        $stmt = $pdo->prepare("UPDATE properties SET status = 'sold' WHERE property_id = ?");
        $stmt->execute([$property_id]);

        // تسجيل النشاط
        logActivity(
            $pdo, 
            $_SESSION['user_id'], 
            'purchase', 
            "شراء عقار: {$property['title']} (#$property_id) - رقم الطلب: $order_number"
        );

        // إتمام المعاملة
        $pdo->commit();

        // إرسال الاستجابة الناجحة
        echo json_encode([
            'success' => true,
            'message' => 'تمت عملية الشراء بنجاح!',
            'order_number' => $order_number,
            'sale_id' => $sale_id,
            'total_amount' => $total_amount,
            'property_title' => $property['title']
        ]);

    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة الخطأ
        $pdo->rollBack();
        
        error_log("Payment Transaction Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        throw new Exception('فشلت عملية الدفع: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Payment Processing Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى.'
    ]);
}
?>