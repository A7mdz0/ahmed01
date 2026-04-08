<?php
/**
 * معالجة التسجيل - دار السودان
 * api/register.php
 */

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

$full_name = sanitizeInput($_POST['full_name']);
$email = sanitizeInput($_POST['email']);
$phone = sanitizeInput($_POST['phone']);
$password = $_POST['password'];
$user_type = $_POST['user_type'];

if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني غير صحيح']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقاً']);
        exit;
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, user_type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$full_name, $email, $phone, $password_hash, $user_type]);
    
    $user_id = $pdo->lastInsertId();
    logActivity($pdo, $user_id, 'register', "تسجيل مستخدم جديد: $full_name");
    
    echo json_encode(['success' => true, 'message' => 'تم إنشاء الحساب بنجاح']);
    
} catch (PDOException $e) {
    error_log("Registration Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء التسجيل']);
}
?>