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
    <title>إنشاء حساب - دار السودان</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 0; direction: rtl; }
        .register-container { background: white; padding: 2.5rem; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 550px; margin: 2rem; }
        .logo { text-align: center; margin-bottom: 1.5rem; }
        .logo-icon { font-size: 3.5rem; }
        .logo-text { font-size: 1.6rem; font-weight: bold; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        h2 { text-align: center; color: #333; margin-bottom: 0.3rem; }
        .subtitle { text-align: center; color: #666; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; color: #333; font-weight: 600; font-size: 0.9rem; }
        .input-wrapper { position: relative; }
        .input-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        .form-group input, .form-group select { width: 100%; padding: 0.9rem 0.9rem 0.9rem 3rem; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 0.95rem; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .user-type-select { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .user-type-option { border: 2px solid #e0e0e0; padding: 1rem; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .user-type-option.selected { border-color: #667eea; background: rgba(102, 126, 234, 0.05); }
        .user-type-option input { display: none; }
        .type-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .type-label { font-weight: 600; color: #333; }
        .terms { display: flex; align-items: flex-start; gap: 0.5rem; margin: 1.2rem 0; }
        .terms input { width: 18px; height: 18px; margin-top: 2px; }
        .btn-register { width: 100%; padding: 1rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 12px; font-size: 1.05rem; font-weight: bold; cursor: pointer; transition: all 0.3s; margin-bottom: 1rem; }
        .btn-register:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); }
        .btn-register:disabled { opacity: 0.6; cursor: not-allowed; }
        .login-link { text-align: center; color: #666; margin-top: 1.2rem; font-size: 0.9rem; }
        .login-link a { color: #667eea; text-decoration: none; font-weight: bold; }
        .alert { padding: 0.9rem; border-radius: 10px; margin-bottom: 1.2rem; display: none; font-size: 0.9rem; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .required { color: #ef4444; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <div class="logo-icon">🏘️</div>
            <div class="logo-text">دار السودان</div>
        </div>
        
        <h2>إنشاء حساب جديد</h2>
        <p class="subtitle">انضم إلينا وابدأ رحلتك في عالم العقارات</p>
        
        <div id="alert" class="alert"></div>
        
        <form id="registerForm">
            <label style="display: block; margin-bottom: 0.8rem; color: #333; font-weight: 600;">أنا:</label>
            <div class="user-type-select">
                <div class="user-type-option selected" onclick="selectUserType(this, 'customer')">
                    <input type="radio" name="user_type" value="customer" id="type_customer" checked>
                    <div class="type-icon">👤</div>
                    <label for="type_customer" class="type-label">زبون</label>
                </div>
                <div class="user-type-option" onclick="selectUserType(this, 'owner')">
                    <input type="radio" name="user_type" value="owner" id="type_owner">
                    <div class="type-icon">🏠</div>
                    <label for="type_owner" class="type-label">مالك عقار</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>الاسم الكامل <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" name="full_name" placeholder="محمد أحمد" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>البريد الإلكتروني <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" name="email" placeholder="example@email.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>رقم الهاتف <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <span class="input-icon">📱</span>
                        <input type="tel" name="phone" placeholder="+249123456789" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>كلمة المرور <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>تأكيد كلمة المرور <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="••••••••" required>
                </div>
            </div>
            
            <div class="terms">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">أوافق على الشروط والأحكام وسياسة الخصوصية</label>
            </div>
            
            <button type="submit" class="btn-register" id="submitBtn">إنشاء الحساب</button>
            
            <div class="login-link">
                لديك حساب بالفعل؟ <a href="login.php">سجل دخولك</a>
            </div>
        </form>
    </div>
    
    <script>
        function selectUserType(element, type) {
            document.querySelectorAll('.user-type-option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            document.getElementById('type_' + type).checked = true;
        }
        
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const alert = document.getElementById('alert');
            const submitBtn = document.getElementById('submitBtn');
            
            if (password !== confirmPassword) {
                alert.className = 'alert alert-error';
                alert.textContent = '✗ كلمات المرور غير متطابقة';
                alert.style.display = 'block';
                return;
            }
            
            if (password.length < 8) {
                alert.className = 'alert alert-error';
                alert.textContent = '✗ كلمة المرور يجب أن تكون 8 أحرف على الأقل';
                alert.style.display = 'block';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'جاري إنشاء الحساب...';
            
            alert.className = 'alert alert-success';
            alert.textContent = 'جاري إنشاء حسابك...';
            alert.style.display = 'block';
            
            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    body: new FormData(this)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = '✓ تم إنشاء الحساب بنجاح!';
                    setTimeout(() => window.location.href = 'login.php', 1500);
                } else {
                    alert.className = 'alert alert-error';
                    alert.textContent = '✗ ' + data.message;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'إنشاء الحساب';
                }
            } catch (error) {
                alert.className = 'alert alert-error';
                alert.textContent = '✗ حدث خطأ';
                submitBtn.disabled = false;
                submitBtn.textContent = 'إنشاء الحساب';
            }
        });
    </script>
</body>
</html>