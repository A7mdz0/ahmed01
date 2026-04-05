<?php
/**
 * لوحة تحكم الإدارة - محدّثة
 * admin/dashboard.php
 * تتضمن: إحصائيات + آخر الأنشطة + رسم بياني بسيط
 */
session_start();
require_once '../auth/check_auth.php';
require_user_type('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ─────────────────────────────────────────────
// 📊 الإحصائيات الأساسية
// ─────────────────────────────────────────────
$stats = [];

$sql = "SELECT COUNT(*) c FROM users WHERE user_type='customer'";
$stats['customers'] = $conn->query($sql)->fetch_assoc()['c'];

$sql = "SELECT COUNT(*) c FROM users WHERE user_type='owner'";
$stats['owners'] = $conn->query($sql)->fetch_assoc()['c'];

$sql = "SELECT COUNT(*) c FROM fields WHERE is_active=1";
$stats['fields'] = $conn->query($sql)->fetch_assoc()['c'];

$sql = "SELECT COUNT(*) c FROM bookings";
$stats['bookings'] = $conn->query($sql)->fetch_assoc()['c'];

$sql = "SELECT COUNT(*) c FROM bookings WHERE status='معلق'";
$stats['pending'] = $conn->query($sql)->fetch_assoc()['c'];

$sql = "SELECT COUNT(*) c FROM bookings WHERE DATE(created_at)=CURDATE()";
$stats['today_bookings'] = $conn->query($sql)->fetch_assoc()['c'];

$sql = "SELECT COALESCE(SUM(total_price),0) t FROM bookings WHERE status IN ('مؤكد','مكتمل')";
$stats['revenue'] = $conn->query($sql)->fetch_assoc()['t'];

$sql = "SELECT COUNT(*) c FROM users WHERE DATE(created_at)=CURDATE()";
$stats['today_users'] = $conn->query($sql)->fetch_assoc()['c'];

// ─────────────────────────────────────────────
// 📈 إحصائيات آخر 7 أيام (للرسم البياني)
// ─────────────────────────────────────────────
$chart_sql = "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
              FROM bookings
              WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
              GROUP BY DATE(created_at)
              ORDER BY day ASC";
$chart_result = $conn->query($chart_sql);
$chart_labels = [];
$chart_data   = [];
$days_map = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $days_map[$d] = 0;
}
while ($row = $chart_result->fetch_assoc()) {
    $days_map[$row['day']] = (int)$row['cnt'];
}
foreach ($days_map as $d => $cnt) {
    $chart_labels[] = date('d/m', strtotime($d));
    $chart_data[]   = $cnt;
}

// ─────────────────────────────────────────────
// 🕐 آخر الأنشطة (حجوزات + مستخدمون جدد + ملاعب)
// ─────────────────────────────────────────────
$activity_sql = "
    (SELECT 
        'booking' AS type,
        CONCAT('حجز جديد من ', c.full_name, ' في ', f.field_name) AS description,
        CONCAT(b.total_price, ' جنيه — ', b.status) AS meta,
        b.created_at AS time,
        b.status AS status_val
     FROM bookings b
     INNER JOIN users c ON b.customer_id = c.id
     INNER JOIN fields f ON b.field_id = f.id
     ORDER BY b.created_at DESC
     LIMIT 5)

    UNION ALL

    (SELECT
        'user' AS type,
        CONCAT('مستخدم جديد: ', full_name) AS description,
        CONCAT(
            CASE user_type
                WHEN 'customer' THEN '👤 عميل'
                WHEN 'owner'    THEN '🏟️ مالك ملعب'
                ELSE '🔧 مدير'
            END
        ) AS meta,
        created_at AS time,
        '' AS status_val
     FROM users
     ORDER BY created_at DESC
     LIMIT 4)

    UNION ALL

    (SELECT
        'field' AS type,
        CONCAT('ملعب جديد: ', field_name) AS description,
        CONCAT(city, ' — ', price_per_hour, ' جنيه/ساعة') AS meta,
        created_at AS time,
        CASE WHEN is_active = 1 THEN 'نشط' ELSE 'معطل' END AS status_val
     FROM fields
     ORDER BY created_at DESC
     LIMIT 3)

    ORDER BY time DESC
    LIMIT 15
";
$activities = $conn->query($activity_sql);

// ─────────────────────────────────────────────
// 🏟️ أعلى الملاعب حجزاً
// ─────────────────────────────────────────────
$top_fields_sql = "SELECT f.field_name, f.city, COUNT(b.id) AS cnt,
                          COALESCE(SUM(b.total_price),0) AS revenue
                   FROM fields f
                   LEFT JOIN bookings b ON f.id = b.field_id AND b.status IN ('مؤكد','مكتمل')
                   WHERE f.is_active = 1
                   GROUP BY f.id
                   ORDER BY cnt DESC
                   LIMIT 5";
$top_fields = $conn->query($top_fields_sql);

$page_title  = 'لوحة الإدارة';
$css_path    = '/football-booking/assets/css/style.css';
$home_path   = '/football-booking/index.php';
$search_path = '/football-booking/search.php';
include '../includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<div class="container-fluid my-4 px-4">

    <!-- ── Page Header ──────────────────────────── -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-cog"></i> لوحة الإدارة</h2>
            <p class="text-muted mb-0" style="font-size:.9rem">
                مرحباً، <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> —
                <?php echo date('l، d F Y'); ?>
            </p>
        </div>
        <div>
            <a href="all_bookings.php" class="btn btn-success btn-sm ml-2">
                <i class="fas fa-calendar"></i> الحجوزات
            </a>
            <a href="users.php" class="btn btn-primary btn-sm">
                <i class="fas fa-users"></i> المستخدمون
            </a>
        </div>
    </div>

    <!-- ── Stats Row 1 ──────────────────────────── -->
    <div class="row mb-3">
        <div class="col-6 col-md-3 mb-3">
            <div class="dashboard-card info">
                <p><i class="fas fa-users"></i> العملاء</p>
                <h3><?php echo number_format($stats['customers']); ?></h3>
                <small style="opacity:.75">+<?php echo $stats['today_users']; ?> اليوم</small>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="dashboard-card success">
                <p><i class="fas fa-futbol"></i> الملاعب النشطة</p>
                <h3><?php echo number_format($stats['fields']); ?></h3>
                <small style="opacity:.75"><?php echo $stats['owners']; ?> مالك</small>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="dashboard-card warning">
                <p><i class="fas fa-calendar-check"></i> الحجوزات</p>
                <h3><?php echo number_format($stats['bookings']); ?></h3>
                <small style="opacity:.75"><?php echo $stats['today_bookings']; ?> اليوم</small>
            </div>
        </div>
        <div class="col-6 col-md-3 mb-3">
            <div class="dashboard-card" style="background:linear-gradient(135deg,#7c3aed,#5b21b6)">
                <p><i class="fas fa-money-bill-wave"></i> الإيرادات</p>
                <h3 style="font-size:1.7rem"><?php echo number_format($stats['revenue'], 0); ?></h3>
                <small style="opacity:.75">جنيه سوداني</small>
            </div>
        </div>
    </div>

    <!-- ── Alert: حجوزات معلقة ──────────────────── -->
    <?php if ($stats['pending'] > 0): ?>
    <div class="alert alert-warning d-flex align-items-center mb-4">
        <i class="fas fa-clock fa-lg ml-3"></i>
        <div>
            <strong><?php echo $stats['pending']; ?> حجز معلق</strong> في انتظار موافقة أصحاب الملاعب.
            <a href="all_bookings.php?status=معلق" class="alert-link mr-2">عرضها &larr;</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">

        <!-- ── Chart + Top Fields ─────────────────── -->
        <div class="col-md-8">

            <!-- رسم بياني -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="color:var(--text-heading)">
                        <i class="fas fa-chart-line"></i> الحجوزات — آخر 7 أيام
                    </h5>
                    <span class="badge badge-success"><?php echo array_sum($chart_data); ?> إجمالي</span>
                </div>
                <div class="card-body">
                    <canvas id="bookingsChart" height="80"></canvas>
                </div>
            </div>

            <!-- أعلى الملاعب -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0" style="color:var(--text-heading)">
                        <i class="fas fa-trophy text-warning"></i> أعلى الملاعب حجزاً
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الملعب</th>
                                    <th>المدينة</th>
                                    <th>الحجوزات</th>
                                    <th>الإيراد</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while ($f = $top_fields->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $medals = ['🥇','🥈','🥉'];
                                        echo $medals[$rank-1] ?? $rank;
                                        $rank++;
                                        ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($f['field_name']); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($f['city']); ?></span></td>
                                    <td>
                                        <span class="badge badge-success"><?php echo $f['cnt']; ?></span>
                                    </td>
                                    <td><strong style="color:var(--accent)"><?php echo number_format($f['revenue'], 0); ?> ج.س</strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /col-md-8 -->

        <!-- ── Activity Feed ──────────────────────── -->
        <div class="col-md-4">
            <div class="card mb-4" style="height:fit-content">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="color:var(--text-heading)">
                        <i class="fas fa-bolt"></i> آخر الأنشطة
                    </h5>
                    <span class="badge badge-secondary" id="activityTime">مباشر</span>
                </div>
                <div class="card-body p-0" style="max-height:520px; overflow-y:auto;">
                    <?php if ($activities && $activities->num_rows > 0): ?>
                    <div style="padding:8px 16px">
                        <?php while ($act = $activities->fetch_assoc()):
                            // أيقونة ولون حسب النوع
                            $icons = [
                                'booking' => ['icon' => 'fa-calendar-check', 'bg' => 'rgba(22,163,74,.1)',  'color' => '#16a34a'],
                                'user'    => ['icon' => 'fa-user-plus',      'bg' => 'rgba(29,78,216,.1)', 'color' => '#1d4ed8'],
                                'field'   => ['icon' => 'fa-futbol',         'bg' => 'rgba(217,119,6,.1)', 'color' => '#d97706'],
                            ];
                            $ic = $icons[$act['type']] ?? $icons['booking'];

                            // حساب الوقت النسبي
                            $diff = time() - strtotime($act['time']);
                            if ($diff < 60)          $time_str = 'منذ ' . $diff . ' ث';
                            elseif ($diff < 3600)    $time_str = 'منذ ' . floor($diff/60) . ' د';
                            elseif ($diff < 86400)   $time_str = 'منذ ' . floor($diff/3600) . ' س';
                            else                     $time_str = date('Y-m-d', strtotime($act['time']));

                            // لون حالة الحجز
                            $status_colors = [
                                'معلق'   => '#d97706', 'مؤكد'  => '#16a34a',
                                'مرفوض' => '#dc2626', 'ملغي'  => '#6b7280',
                                'مكتمل' => '#0891b2'
                            ];
                            $sc = $status_colors[$act['status_val']] ?? '#6b7280';
                        ?>
                        <div class="activity-item">
                            <div class="activity-icon"
                                 style="background:<?php echo $ic['bg']; ?>; color:<?php echo $ic['color']; ?>;">
                                <i class="fas <?php echo $ic['icon']; ?>"></i>
                            </div>
                            <div class="activity-text">
                                <p><?php echo htmlspecialchars($act['description']); ?></p>
                                <?php if (!empty($act['meta'])): ?>
                                    <small style="color:<?php echo $sc; ?>; font-weight:600">
                                        <?php echo htmlspecialchars($act['meta']); ?>
                                    </small><br>
                                <?php endif; ?>
                                <small><?php echo $time_str; ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state" style="padding:40px 20px">
                        <i class="fas fa-history"></i>
                        <h5>لا توجد أنشطة بعد</h5>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="all_bookings.php" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-list"></i> عرض كل الحجوزات
                    </a>
                </div>
            </div>

            <!-- روابط سريعة -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0" style="color:var(--text-heading)">
                        <i class="fas fa-link"></i> إجراءات سريعة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column" style="gap:10px">
                        <a href="users.php" class="btn btn-primary btn-block">
                            <i class="fas fa-users ml-2"></i> إدارة المستخدمين
                            <span class="badge badge-light float-left"><?php echo $stats['customers'] + $stats['owners']; ?></span>
                        </a>
                        <a href="all_fields.php" class="btn btn-success btn-block">
                            <i class="fas fa-futbol ml-2"></i> إدارة الملاعب
                            <span class="badge badge-light float-left"><?php echo $stats['fields']; ?></span>
                        </a>
                        <a href="all_bookings.php" class="btn btn-info btn-block">
                            <i class="fas fa-calendar ml-2"></i> إدارة الحجوزات
                            <span class="badge badge-light float-left"><?php echo $stats['bookings']; ?></span>
                        </a>
                        <?php if ($stats['pending'] > 0): ?>
                        <a href="all_bookings.php?status=معلق" class="btn btn-warning btn-block">
                            <i class="fas fa-clock ml-2"></i> الحجوزات المعلقة
                            <span class="badge badge-light float-left"><?php echo $stats['pending']; ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- /col-md-4 -->

    </div><!-- /row -->

</div><!-- /container -->

<!-- ─── Chart.js Script ───────────────────── -->
<script>
(function () {
    const isDark  = document.body.classList.contains('dark-mode');
    const gridClr = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)';
    const tickClr = isDark ? '#64748b' : '#9ca3af';
    const accent  = isDark ? '#34d399' : '#16a34a';

    const ctx = document.getElementById('bookingsChart').getContext('2d');

    // Gradient fill
    const grad = ctx.createLinearGradient(0, 0, 0, 200);
    grad.addColorStop(0, isDark ? 'rgba(52,211,153,.3)' : 'rgba(22,163,74,.2)');
    grad.addColorStop(1, 'rgba(0,0,0,0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels, JSON_UNESCAPED_UNICODE); ?>,
            datasets: [{
                label: 'الحجوزات',
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: accent,
                backgroundColor: grad,
                borderWidth: 2.5,
                pointBackgroundColor: accent,
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: .4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#0f1c2e' : '#fff',
                    titleColor: isDark ? '#34d399' : '#16a34a',
                    bodyColor: isDark ? '#94a3b8' : '#6b7280',
                    borderColor: isDark ? 'rgba(52,211,153,.2)' : 'rgba(22,163,74,.2)',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: ctx => `  ${ctx.parsed.y} حجز`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: tickClr, stepSize: 1 },
                    grid: { color: gridClr }
                },
                x: {
                    ticks: { color: tickClr },
                    grid: { color: gridClr }
                }
            }
        }
    });

    // تحديث ساعة النشاط
    function updateClock() {
        const now = new Date();
        document.getElementById('activityTime').textContent =
            now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
    }
    updateClock();
    setInterval(updateClock, 30000);
})();
</script>

<?php include '../includes/footer.php'; ?>