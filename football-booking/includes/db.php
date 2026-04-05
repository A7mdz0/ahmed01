<?php
/**
 * ملف الاتصال بقاعدة البيانات
 * Database Connection File
 */

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'football_booking');

// إنشاء الاتصال
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تعيين الترميز إلى UTF-8
$conn->set_charset("utf8mb4");

/**
 * دالة لتنظيف المدخلات من أي محاولات حقن
 * @param string $data البيانات المراد تنظيفها
 * @return string البيانات النظيفة
 */
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * دالة لعرض رسائل النجاح أو الخطأ
 * @param string $message الرسالة
 * @param string $type نوع الرسالة (success أو danger)
 * @return string HTML للرسالة
 */
function show_message($message, $type = 'success') {
    $class = $type === 'success' ? 'alert-success' : 'alert-danger';
    return "<div class='alert {$class} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
            </div>";
}
?>
