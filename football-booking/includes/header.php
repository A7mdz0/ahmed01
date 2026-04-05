<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'نظام حجز ملاعب كرة القدم'; ?></title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-rtl/3.4.0/css/bootstrap-rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- ✅ مسار مطلق للـ CSS يشتغل من أي صفحة في أي مجلد -->
    <link rel="stylesheet" href="/football-booking/assets/css/style.css">

    <!-- تطبيق الوضع المحفوظ قبل رسم الصفحة (يمنع الوميض) -->
    <script>
    (function() {
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark-mode-preload');
        }
    })();
    </script>
    <style>
    html.dark-mode-preload body { background: #060d1a !important; }
    </style>
</head>
<body>

<script>
// تطبيق الوضع المحفوظ على body فور تحميله
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}
</script>

    <!-- ─── Navbar ─────────────────────────────── -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/football-booking/index.php">
                <i class="fas fa-futbol"></i> ملاعب السودان
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/football-booking/index.php">
                            <i class="fas fa-home"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/football-booking/search.php">
                            <i class="fas fa-search"></i> البحث
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav align-items-center">

                    <!-- ✅ زر الدارك مود - id="darkToggle" لا تغيره -->
                    <li class="nav-item ml-2 mr-2">
                        <button class="dark-toggle" id="darkToggle"
                                title="تبديل الوضع الليلي"
                                aria-label="تبديل الوضع الليلي">
                        </button>
                    </li>

                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" data-toggle="dropdown">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-left">
                                <?php if (is_customer()): ?>
                                    <a class="dropdown-item" href="/football-booking/customer/dashboard.php">
                                        <i class="fas fa-tachometer-alt text-success"></i> لوحة التحكم
                                    </a>
                                    <a class="dropdown-item" href="/football-booking/customer/my_bookings.php">
                                        <i class="fas fa-calendar-check text-primary"></i> حجوزاتي
                                    </a>
                                <?php elseif (is_owner()): ?>
                                    <a class="dropdown-item" href="/football-booking/owner/dashboard.php">
                                        <i class="fas fa-tachometer-alt text-success"></i> لوحة التحكم
                                    </a>
                                    <a class="dropdown-item" href="/football-booking/owner/my_fields.php">
                                        <i class="fas fa-futbol text-warning"></i> ملاعبي
                                    </a>
                                    <a class="dropdown-item" href="/football-booking/owner/bookings.php">
                                        <i class="fas fa-calendar text-info"></i> الحجوزات
                                    </a>
                                <?php elseif (is_admin()): ?>
                                    <a class="dropdown-item" href="/football-booking/admin/dashboard.php">
                                        <i class="fas fa-cog text-danger"></i> لوحة الإدارة
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="/football-booking/auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                                </a>
                            </div>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/football-booking/auth/login.php">
                                <i class="fas fa-sign-in-alt"></i> دخول
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/football-booking/auth/register.php"
                               style="border:2px solid rgba(255,255,255,.5); border-radius:8px; padding:7px 14px;">
                                <i class="fas fa-user-plus"></i> تسجيل جديد
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- ─── End Navbar ────────────────────────── -->