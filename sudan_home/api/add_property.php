<?php
/**
 * إضافة عقار - دار السودان
 * api/add_property.php
 */

// تفعيل عرض الأخطاء للتطوير (احذف هذا في الإنتاج)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
require_once '../classes/ImageUploader.php';

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

    // التحقق من وجود البيانات المطلوبة
    $required_fields = ['title', 'description', 'property_type', 'listing_type', 'price', 'area', 'location_id', 'address'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "الحقل $field مطلوب"]);
            exit;
        }
    }

    // التحقق من وجود الصور
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        echo json_encode(['success' => false, 'message' => 'يجب إضافة صورة واحدة على الأقل']);
        exit;
    }

    // جلب البيانات وتنظيفها
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $property_type = $_POST['property_type'];
    $listing_type = $_POST['listing_type'];
    $price = floatval($_POST['price']);
    $area = floatval($_POST['area']);
    $bedrooms = !empty($_POST['bedrooms']) ? intval($_POST['bedrooms']) : null;
    $bathrooms = !empty($_POST['bathrooms']) ? intval($_POST['bathrooms']) : null;
    $location_id = intval($_POST['location_id']);
    $address = sanitizeInput($_POST['address']);

    // التحقق من صحة البيانات
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'السعر يجب أن يكون أكبر من صفر']);
        exit;
    }

    if ($area <= 0) {
        echo json_encode(['success' => false, 'message' => 'المساحة يجب أن تكون أكبر من صفر']);
        exit;
    }

    // رفع الصور
    $uploader = new ImageUploader();
    $uploadedImages = $uploader->uploadMultipleImages($_FILES['images']);

    if (count($uploadedImages) === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'فشل رفع الصور. تأكد من أن الصور بصيغة JPG أو PNG وبحجم أقل من 5 ميجا. تحقق من صلاحيات مجلد uploads/properties/'
        ]);
        exit;
    }

    // بدء معاملة قاعدة البيانات
    $pdo->beginTransaction();

    try {
        // إدراج العقار في قاعدة البيانات
        $stmt = $pdo->prepare("
            INSERT INTO properties (
                owner_id, title, description, property_type, 
                listing_type, price, area, bedrooms, bathrooms, 
                location_id, address, status, created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        $stmt->execute([
            $_SESSION['user_id'], 
            $title, 
            $description, 
            $property_type,
            $listing_type, 
            $price, 
            $area, 
            $bedrooms, 
            $bathrooms, 
            $location_id, 
            $address
        ]);

        $property_id = $pdo->lastInsertId();

        if (!$property_id) {
            throw new Exception('فشل في إنشاء العقار');
        }

        // إدراج الصور
        $stmt = $pdo->prepare("
            INSERT INTO property_images (property_id, image_path, is_primary, upload_date) 
            VALUES (?, ?, ?, NOW())
        ");

        foreach ($uploadedImages as $index => $image) {
            $is_primary = ($index === 0) ? 1 : 0;
            $stmt->execute([$property_id, $image['path'], $is_primary]);
        }

        // تسجيل النشاط
        logActivity($pdo, $_SESSION['user_id'], 'add_property', "إضافة عقار: $title (#$property_id)");

        // إتمام المعاملة
        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'تم إضافة العقار بنجاح! سيتم مراجعته من قبل الإدارة قريباً', 
            'property_id' => $property_id,
            'images_uploaded' => count($uploadedImages)
        ]);

    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة الخطأ
        $pdo->rollBack();
        
        // حذف الصور المرفوعة
        foreach ($uploadedImages as $image) {
            $uploader->deleteImage($image['path']);
        }
        
        throw $e;
    }

} catch (Exception $e) {
    error_log("Add Property Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false, 
        'message' => 'حدث خطأ أثناء إضافة العقار: ' . $e->getMessage()
    ]);
}
?>