<?php
/**
 * صفحة التسجيل
 */
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// إذا كان المستخدم مسجل دخول، إعادة توجيه
if (is_logged_in()) {
    redirect('../index.php');
}

$error = '';
$success = '';

// معالجة النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = clean_input($_POST['user_type']);
    
    // التحقق من المدخلات
    if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($user_type)) {
        $error = 'يرجى ملء جميع الحقول';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } elseif (strlen($password) < 6) {
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif ($password !== $confirm_password) {
        $error = 'كلمات المرور غير متطابقة';
    } elseif (!in_array($user_type, ['customer', 'owner'])) {
        $error = 'نوع الحساب غير صحيح';
    } else {
        // التحقق من عدم وجود البريد الإلكتروني
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'البريد الإلكتروني مسجل مسبقاً';
        } else {
            // تشفير كلمة المرور
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // إدخال المستخدم الجديد
            $insert_sql = "INSERT INTO users (full_name, email, phone, password, user_type) 
                          VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $full_name, $email, $phone, $hashed_password, $user_type);
            
            if ($insert_stmt->execute()) {
                $success = 'تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول';
                
                // إعادة توجيه بعد 2 ثانية
                header("refresh:2;url=login.php");
            } else {
                $error = 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى';
            }
        }
    }
}

$page_title = 'تسجيل حساب جديد';
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
                    <h2><i class="fas fa-user-plus"></i> تسجيل حساب جديد</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="full_name">الاسم الكامل</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">رقم الهاتف</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                   placeholder="مثال: 0912345678" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="user_type">نوع الحساب</label>
                            <select class="form-control" id="user_type" name="user_type" required>
                                <option value="">-- اختر نوع الحساب --</option>
                                <option value="customer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'customer') ? 'selected' : ''; ?>>
                                    عميل (أريد حجز ملاعب)
                                </option>
                                <option value="owner" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'owner') ? 'selected' : ''; ?>>
                                    مالك ملعب (أريد عرض ملاعبي)
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="6" required>
                            <small class="form-text text-muted">6 أحرف على الأقل</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">تأكيد كلمة المرور</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   minlength="6" required>
                            <small id="passwordError" class="text-danger"></small>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-user-plus"></i> تسجيل
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="text-center">
                        <p>لديك حساب؟ <a href="login.php" class="text-success font-weight-bold">سجل الدخول</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
