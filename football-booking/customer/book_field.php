<?php
/**
 * صفحة حجز ملعب - العميل
 */
session_start();
require_once '../auth/check_auth.php';
require_user_type('customer');

require_once '../includes/db.php';
require_once '../includes/functions.php';

// معالجة الحجز
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field_id = intval($_POST['field_id']);
    $booking_date = clean_input($_POST['booking_date']);
    $start_time = clean_input($_POST['start_time']);
    $end_time = clean_input($_POST['end_time']);
    $customer_notes = isset($_POST['customer_notes']) ? clean_input($_POST['customer_notes']) : '';
    $payment_method = clean_input($_POST['payment_method']);
    $customer_id = $_SESSION['user_id'];
    
    // التحقق من المدخلات
    $validation_errors = validate_booking_time($booking_date, $start_time, $end_time);
    
    if (!empty($validation_errors)) {
        $_SESSION['error_message'] = implode('<br>', $validation_errors);
        header("Location: ../field_details.php?id=" . $field_id);
        exit();
    }
    
    // التحقق من توفر الوقت
    if (!is_time_available($conn, $field_id, $booking_date, $start_time, $end_time)) {
        $_SESSION['error_message'] = 'عذراً، الوقت المحدد محجوز مسبقاً. يرجى اختيار وقت آخر';
        header("Location: ../field_details.php?id=" . $field_id);
        exit();
    }
    
    // حساب عدد الساعات
    $total_hours = calculate_hours($start_time, $end_time);
    
    // جلب سعر الملعب
    $sql = "SELECT price_per_hour FROM fields WHERE id = ? AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $field_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = 'الملعب غير موجود أو غير نشط';
        redirect('my_bookings.php');
    }
    
    $field = $result->fetch_assoc();
    $total_price = $total_hours * $field['price_per_hour'];
    
    // معالجة بيانات الدفع
    $payment_status = 'معلق';
    $card_number = null;
    $card_holder = null;
    
    if ($payment_method === 'card') {
        $card_number = clean_input($_POST['card_number']);
        $card_holder = clean_input($_POST['card_holder']);
        
        // في نظام حقيقي، هنا يتم التحقق من البطاقة ومعالجة الدفع
        // لكن للتجربة سنعتبر الدفع معلق
        $payment_status = 'معلق';
        
        // تشفير آخر 4 أرقام فقط للعرض
        $card_last_4 = substr($card_number, -4);
        $card_number = '****-****-****-' . $card_last_4;
    }
    
    // إدخال الحجز
    $insert_sql = "INSERT INTO bookings 
                  (customer_id, field_id, booking_date, start_time, end_time, 
                   total_hours, total_price, status, customer_notes, payment_method, 
                   payment_status, card_number, card_holder) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'معلق', ?, ?, ?, ?, ?)";
    
    $insert_stmt = $conn->prepare($insert_sql);
    
    if ($insert_stmt === false) {
        $_SESSION['error_message'] = 'خطأ في الاتصال بقاعدة البيانات: ' . $conn->error;
        redirect('my_bookings.php');
    }
    
    $insert_stmt->bind_param("iisssidsssss", 
        $customer_id, $field_id, $booking_date, 
        $start_time, $end_time, $total_hours, $total_price, 
        $customer_notes, $payment_method, $payment_status, 
        $card_number, $card_holder);
    
    if ($insert_stmt->execute()) {
        $_SESSION['booking_success'] = 'تم إرسال طلب الحجز بنجاح! في انتظار موافقة المالك.';
        redirect('my_bookings.php');
    } else {
        $_SESSION['error_message'] = 'حدث خطأ أثناء الحجز: ' . $conn->error;
        header("Location: ../field_details.php?id=" . $field_id);
        exit();
    }
} else {
    redirect('my_bookings.php');
}
?>
