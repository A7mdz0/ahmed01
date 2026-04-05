<?php
/**
 * owner/manage_booking.php
 * قبول أو رفض الحجز + إرسال إيميل للعميل
 */
session_start();
require_once '../auth/check_auth.php';
require_user_type('owner');
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';   // ← ملف الإيميل

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    redirect('bookings.php');
}

$booking_id = intval($_GET['id']);
$action     = $_GET['action'];
$owner_id   = $_SESSION['user_id'];

// ── التحقق من الصلاحية ──────────────────────────────────
$check_sql = "SELECT b.* FROM bookings b
              INNER JOIN fields f ON b.field_id = f.id
              WHERE b.id = ? AND f.owner_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $booking_id, $owner_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    redirect('bookings.php');
}

// ── جلب بيانات الحجز كاملة لإرسال الإيميل ─────────────
$data_sql = "SELECT 
                b.id,
                b.booking_date,
                b.start_time,
                b.end_time,
                b.total_hours,
                b.total_price,
                b.payment_method,
                b.customer_notes,
                f.field_name,
                f.city,
                f.address,
                u_owner.full_name  AS owner_name,
                u_owner.phone      AS owner_phone,
                u_cust.full_name   AS customer_name,
                u_cust.email       AS customer_email
             FROM bookings b
             INNER JOIN fields f       ON b.field_id   = f.id
             INNER JOIN users u_owner  ON f.owner_id   = u_owner.id
             INNER JOIN users u_cust   ON b.customer_id = u_cust.id
             WHERE b.id = ?";

$data_stmt = $conn->prepare($data_sql);
$data_stmt->bind_param("i", $booking_id);
$data_stmt->execute();
$booking_data = $data_stmt->get_result()->fetch_assoc();

// ── تحديث حالة الحجز ────────────────────────────────────
$new_status = ($action === 'accept') ? 'مؤكد' : 'مرفوض';

$update_sql  = "UPDATE bookings SET status = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("si", $new_status, $booking_id);

if ($update_stmt->execute()) {

    // ── إرسال الإيميل بعد التحديث ──────────────────────
    if ($booking_data) {
        if ($action === 'accept') {
            // إيميل تأكيد ✅
            $sent = send_booking_confirmation(
                $booking_data['customer_email'],
                $booking_data['customer_name'],
                $booking_data
            );
            $_SESSION['success_message'] = $sent
                ? '✅ تم قبول الحجز وإرسال إيميل التأكيد للعميل'
                : '✅ تم قبول الحجز (تعذّر إرسال الإيميل، تحقق من إعدادات mailer.php)';
        } else {
            // إيميل رفض ❌
            $sent = send_booking_rejection(
                $booking_data['customer_email'],
                $booking_data['customer_name'],
                $booking_data
            );
            $_SESSION['success_message'] = $sent
                ? '❌ تم رفض الحجز وإشعار العميل'
                : '❌ تم رفض الحجز (تعذّر إرسال الإيميل)';
        }
    } else {
        $_SESSION['success_message'] = 'تم تحديث الحجز بنجاح';
    }
} else {
    $_SESSION['error_message'] = 'حدث خطأ أثناء تحديث الحجز';
}

redirect('bookings.php');