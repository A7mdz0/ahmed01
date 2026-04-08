<?php
/**
 * ملف الاتصال بقاعدة البيانات - دار السودان
 * config/database.php
 */

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'sudan_home_platform');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// إعدادات الموقع
define('SITE_URL', 'http://localhost/sudan_home');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات");
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkPermission($required_role) {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
    if ($_SESSION['user_type'] !== $required_role && $_SESSION['user_type'] !== 'admin') {
        die('ليس لديك صلاحية');
    }
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function logActivity($pdo, $user_id, $action, $description) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $_SERVER['REMOTE_ADDR']]);
    } catch (Exception $e) {
        error_log("Log Error: " . $e->getMessage());
    }
}
?>