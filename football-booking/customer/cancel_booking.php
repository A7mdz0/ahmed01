<?php
/**
 * صفحة إلغاء الحجز - العميل
 */
session_start();
require_once '../auth/check_auth.php';
require_user_type('customer');

require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('my_bookings.php');
}

$booking_id = intval($_GET['id']);
$customer_id = $_SESSION['user_id'];

// التحقق من أن الحجز يخص المستخدم الحالي
$check_sql = "SELECT * FROM bookings WHERE id = ? AND customer_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $booking_id, $customer_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    redirect('my_bookings.php');
}

$booking = $check_result->fetch_assoc();

// التحقق من إمكانية الإلغاء
if (!in_array($booking['status'], ['معلق', 'مؤكد'])) {
    $_SESSION['error_message'] = 'لا يمكن إلغاء هذا الحجز';
    redirect('my_bookings.php');
}

// إلغاء الحجز
$update_sql = "UPDATE bookings SET status = 'ملغي' WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $booking_id);

if ($update_stmt->execute()) {
    $_SESSION['success_message'] = 'تم إلغاء الحجز بنجاح';
} else {
    $_SESSION['error_message'] = 'حدث خطأ أثناء الإلغاء';
}

redirect('my_bookings.php');
?>
