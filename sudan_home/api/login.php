<?php
/**
 * معالجة تسجيل الدخول - دار السودان
 * api/login.php
 */

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

$email = sanitizeInput($_POST['email']);
$password = $_POST['password'];

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'يرجى إدخال البريد الإلكتروني وكلمة المرور']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, user_type, is_active FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة']);
        exit;
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة']);
        exit;
    }
    
    if (!$user['is_active']) {
        echo json_encode(['success' => false, 'message' => 'حسابك معطل']);
        exit;
    }
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    
    logActivity($pdo, $user['user_id'], 'login', "تسجيل دخول");
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'user_type' => $user['user_type']
    ]);
    
} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ']);
}
?>