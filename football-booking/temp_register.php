<?php
/**
 * ⚠️ صفحة تسجيل مؤقتة — للسيرفر المحلي فقط
 * احذف الملف بعد الانتهاء
 */
session_start();
require_once 'includes/db.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $user_type = $_POST['user_type'];

    // تحقق بسيط
    if (empty($full_name) || empty($email) || empty($password)) {
        $message = '❌ يرجى ملء جميع الحقول';
    } else {
        // تحقق من عدم تكرار الإيميل
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = '❌ البريد الإلكتروني مسجل مسبقاً';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql    = "INSERT INTO users (full_name, email, phone, password, user_type) VALUES (?, ?, ?, ?, ?)";
            $stmt   = $conn->prepare($sql);
            $stmt->bind_param("sssss", $full_name, $email, $phone, $hashed, $user_type);
            if ($stmt->execute()) {
                $success = true;
                $message = '✅ تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.';
            } else {
                $message = '❌ حدث خطأ: ' . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد — مؤقت</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-rtl/3.4.0/css/bootstrap-rtl.min.css">
    <style>
        body { background: #f0f4f8; font-family: Arial, sans-serif; direction: rtl; }
        .card { border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.1); border: none; margin-top: 60px; }
        .card-header { background: linear-gradient(135deg, #16a34a, #15803d); border-radius: 16px 16px 0 0 !important; padding: 24px; }
        .btn-success { background: linear-gradient(135deg, #16a34a, #15803d); border: none; padding: 12px; font-size: 16px; border-radius: 10px; }
        .form-control { border-radius: 10px; padding: 10px 14px; }
        .form-control:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.15); }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #856404; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">

            <!-- تحذير -->
            <div class="warning-box mt-4">
                ⚠️ <strong>تحذير:</strong> هذه صفحة مؤقتة للتطوير المحلي فقط.
                احذفها من السيرفر عند الانتهاء.
            </div>

            <div class="card mt-3 mb-5">
                <div class="card-header text-center text-white">
                    <h4 class="mb-1">⚽ إنشاء حساب جديد</h4>
                    <small style="opacity:.85;">للسيرفر المحلي — football-booking</small>
                </div>
                <div class="card-body p-4">

                    <?php if ($message): ?>
                        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $message; ?>
                            <?php if ($success): ?>
                                <br><a href="/football-booking/auth/login.php" class="btn btn-success btn-sm mt-2">
                                    → تسجيل الدخول الآن
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>الاسم الكامل</label>
                            <input type="text" class="form-control" name="full_name"
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                   placeholder="مثال: أحمد محمد" required>
                        </div>

                        <div class="form-group">
                            <label>البريد الإلكتروني</label>
                            <input type="email" class="form-control" name="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   placeholder="example@gmail.com" required>
                        </div>

                        <div class="form-group">
                            <label>رقم الهاتف</label>
                            <input type="text" class="form-control" name="phone"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   placeholder="0912345678">
                        </div>

                        <div class="form-group">
                            <label>كلمة المرور</label>
                            <input type="password" class="form-control" name="password"
                                   placeholder="6 أحرف على الأقل" minlength="6" required>
                        </div>

                        <div class="form-group">
                            <label>نوع الحساب</label>
                            <select class="form-control" name="user_type" required>
                                <option value="admin"   <?php echo ($_POST['user_type'] ?? '') === 'admin'    ? 'selected' : ''; ?>>🔧 مدير</option>
                                <option value="owner"   <?php echo ($_POST['user_type'] ?? '') === 'owner'    ? 'selected' : ''; ?>>🏟️ مالك ملعب</option>
                                <option value="customer"<?php echo ($_POST['user_type'] ?? '') === 'customer' ? 'selected' : ''; ?>>👤 عميل</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success btn-block">
                            إنشاء الحساب ✅
                        </button>

                        <div class="text-center mt-3">
                            <a href="/football-booking/auth/login.php" style="color:#16a34a;">
                                لديك حساب؟ سجّل دخول
                            </a>
                        </div>
                    </form>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
