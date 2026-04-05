<?php
/**
 * صفحة تسجيل الدخول
 */
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// إذا كان المستخدم مسجل دخول، إعادة توجيه
if (is_logged_in()) {
    if (is_customer()) {
        redirect('../customer/dashboard.php');
    } elseif (is_owner()) {
        redirect('../owner/dashboard.php');
    } elseif (is_admin()) {
        redirect('../admin/dashboard.php');
    }
}

$error = '';

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    
    // التحقق من المدخلات
    if (empty($email) || empty($password)) {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور';
    } else {
        // البحث عن المستخدم
        $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // التحقق من كلمة المرور
            if (password_verify($password, $user['password'])) {
                // تسجيل الدخول ناجح
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_email'] = $user['email'];
                
                // إعادة التوجيه حسب نوع المستخدم
                if ($user['user_type'] === 'customer') {
                    redirect('../customer/dashboard.php');
                } elseif ($user['user_type'] === 'owner') {
                    redirect('../owner/dashboard.php');
                } elseif ($user['user_type'] === 'admin') {
                    redirect('../admin/dashboard.php');
                }
            } else {
                $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
            }
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
}

$page_title = 'تسجيل الدخول';
$home_path = '/football-booking/index.php';
$search_path = '/football-booking/search.php';
$css_path = '/football-booking/assets/css/style.css';
?>

<?php include '../includes/header.php'; ?>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-card">
                    <h2><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-sign-in-alt"></i> دخول
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p>ليس لديك حساب؟ <a href="register.php" class="text-success font-weight-bold">سجل الآن</a></p>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <strong>حسابات تجريبية:</strong><br>
                        <small>
                            • عميل: customer@example.sd / customer123<br>
                            • مالك: owner@example.sd / owner123<br>
                            • مدير: admin@footballbooking.sd / admin123
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
