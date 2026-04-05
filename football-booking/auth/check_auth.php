<?php
/**
 * ملف التحقق من الصلاحيات
 * يتم استخدامه في بداية كل صفحة تتطلب تسجيل دخول
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// التحقق من تسجيل الدخول
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    redirect('/football-booking/auth/login.php');
}

/**
 * التحقق من نوع المستخدم المطلوب
 */
function require_user_type($required_type) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== $required_type) {
        redirect('/football-booking/index.php');
    }
}

/**
 * التحقق من صلاحية المستخدم لإجراء معين
 */
function can_access_resource($resource_type, $resource_id) {
    global $conn;
    
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    
    // المدير يمكنه الوصول لكل شيء
    if ($user_type === 'admin') {
        return true;
    }
    
    // التحقق حسب نوع المورد
    if ($resource_type === 'field') {
        // التحقق من أن المستخدم هو مالك الملعب
        $sql = "SELECT owner_id FROM fields WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $resource_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $field = $result->fetch_assoc();
            return $field['owner_id'] == $user_id;
        }
    } elseif ($resource_type === 'booking') {
        // التحقق من أن المستخدم هو العميل أو مالك الملعب
        $sql = "SELECT b.customer_id, f.owner_id 
                FROM bookings b 
                INNER JOIN fields f ON b.field_id = f.id 
                WHERE b.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $resource_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $booking = $result->fetch_assoc();
            return $booking['customer_id'] == $user_id || $booking['owner_id'] == $user_id;
        }
    }
    
    return false;
}
?>
