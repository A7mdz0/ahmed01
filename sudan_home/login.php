<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - دار السودان</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; direction: rtl; }
        .login-container { background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 450px; animation: slideUp 0.5s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo-icon { font-size: 4rem; margin-bottom: 0.5rem; }
        .logo-text { font-size: 1.8rem; font-weight: bold; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        h2 { text-align: center; color: #333; margin-bottom: 0.5rem; font-size: 1.5rem; }
        .subtitle { text-align: center; color: #666; margin-bottom: 2rem; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600; font-size: 0.95rem; }
        .input-wrapper { position: relative; }
        .input-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 1.2rem; }
        .form-group input { width: 100%; padding: 1rem 1rem 1rem 3rem; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 1rem; transition: all 0.3s; }
        .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        .remember-forgot { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .remember-me { display: flex; align-items: center; gap: 0.5rem; }
        .forgot-password { color: #667eea; text-decoration: none; font-weight: 600; }
        .btn-login { width: 100%; padding: 1rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 12px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: all 0.3s; margin-bottom: 1rem; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); }
        .register-link { text-align: center; color: #666; margin-top: 1.5rem; }
        .register-link a { color: #667eea; text-decoration: none; font-weight: bold; }
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: none; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .back-home { position: absolute; top: 2rem; right: 2rem; background: rgba(255, 255, 255, 0.2); color: white; padding: 0.7rem 1.5rem; border-radius: 10px; text-decoration: none; font-weight: 600; backdrop-filter: blur(10px); }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">← العودة للرئيسية</a>
    
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🏘️</div>
            <div class="logo-text">دار السودان</div>
        </div>
        
        <h2>مرحباً بعودتك</h2>
        <p class="subtitle">سجل دخولك للوصول إلى حسابك</p>
        
        <div id="alert" class="alert"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <div class="input-wrapper">
                    <span class="input-icon">📧</span>
                    <input type="email" name="email" placeholder="example@email.com" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>كلمة المرور</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">تذكرني</label>
                </div>
            </div>
            
            <button type="submit" class="btn-login">تسجيل الدخول</button>
            
            <div class="register-link">
                ليس لديك حساب؟ <a href="register.php">سجل الآن</a>
            </div>
        </form>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const alert = document.getElementById('alert');
            
            alert.className = 'alert alert-success';
            alert.textContent = 'جاري تسجيل الدخول...';
            alert.style.display = 'block';
            
            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = '✓ تم تسجيل الدخول بنجاح!';
                    
                    setTimeout(() => {
                        if (data.user_type === 'admin') {
                            window.location.href = 'admin/dashboard.php';
                        } else {
                            window.location.href = 'index.php';
                        }
                    }, 1000);
                } else {
                    alert.className = 'alert alert-error';
                    alert.textContent = '✗ ' + data.message;
                }
            } catch (error) {
                alert.className = 'alert alert-error';
                alert.textContent = '✗ حدث خطأ. يرجى المحاولة مرة أخرى.';
            }
        });
    </script>
</body>
</html>