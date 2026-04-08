<?php
require_once '../config/database.php';

checkPermission('admin');

// جلب الإحصائيات
$stats = [
    'total_properties' => $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn(),
    'active_properties' => $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'active'")->fetchColumn(),
    'pending_properties' => $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'pending'")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type != 'admin'")->fetchColumn(),
];

// جلب العقارات المعلقة
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name as owner_name, l.city
    FROM properties p
    LEFT JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN locations l ON p.location_id = l.location_id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute();
$pending_properties = $stmt->fetchAll();

// جلب آخر المستخدمين
$stmt = $pdo->query("SELECT * FROM users WHERE user_type != 'admin' ORDER BY created_at DESC LIMIT 10");
$recent_users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - دار السودان</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; direction: rtl; }
        .dashboard { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .sidebar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; }
        .logo { padding: 0 1.5rem; font-size: 1.5rem; font-weight: bold; margin-bottom: 2rem; text-align: center; }
        .menu { list-style: none; }
        .menu-item { padding: 1rem 1.5rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: space-between; }
        .menu-item:hover, .menu-item.active { background: rgba(255, 255, 255, 0.2); border-right: 4px solid white; }
        .main-content { padding: 2rem; }
        .header { background: white; padding: 1.5rem 2rem; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-value { font-size: 2rem; font-weight: bold; color: #333; }
        .stat-label { color: #999; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .content-section { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        .section-title { font-size: 1.4rem; color: #333; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #e0e0e0; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: #f9f9f9; padding: 1rem; text-align: right; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; }
        .table td { padding: 1rem; border-bottom: 1px solid #f0f0f0; color: #666; }
        .table tr:hover { background: #fafafa; }
        .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.85rem; border: none; border-radius: 6px; cursor: pointer; margin-left: 0.3rem; transition: all 0.3s; }
        .btn-approve { background: #10b981; color: white; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-view { background: #3b82f6; color: white; }
        .btn-sm:hover { opacity: 0.8; transform: scale(1.05); }
        @media (max-width: 768px) { .dashboard { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="logo">🏘️ دار السودان</div>
            <ul class="menu">
                <li class="menu-item active">📊 نظرة عامة</li>
                <li class="menu-item">
                    ⏳ العقارات المعلقة
                    <?php if ($stats['pending_properties'] > 0): ?>
                        <span style="background: #ef4444; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem;">
                            <?= $stats['pending_properties'] ?>
                        </span>
                    <?php endif; ?>
                </li>
                <li class="menu-item">🏠 جميع العقارات</li>
                <li class="menu-item">👥 المستخدمين</li>
                <li class="menu-item">⚙️ الإعدادات</li>
                <li class="menu-item" onclick="window.location.href='../api/logout.php'" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem;">
                    🚪 تسجيل الخروج
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>لوحة التحكم</h1>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
                    <div style="font-size: 0.85rem; color: #999;"><?= htmlspecialchars($_SESSION['email']) ?></div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">إجمالي العقارات</div>
                    <div class="stat-value"><?= $stats['total_properties'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">عقارات نشطة</div>
                    <div class="stat-value" style="color: #10b981;"><?= $stats['active_properties'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">قيد المراجعة</div>
                    <div class="stat-value" style="color: #f59e0b;"><?= $stats['pending_properties'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">المستخدمين</div>
                    <div class="stat-value" style="color: #667eea;"><?= $stats['total_users'] ?></div>
                </div>
            </div>
            
            <?php if (count($pending_properties) > 0): ?>
            <div class="content-section">
                <h2 class="section-title">العقارات المعلقة (تحتاج موافقة)</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>العقار</th>
                            <th>المالك</th>
                            <th>النوع</th>
                            <th>المدينة</th>
                            <th>السعر</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_properties as $prop): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($prop['title']) ?></strong></td>
                            <td><?= htmlspecialchars($prop['owner_name']) ?></td>
                            <td><?= htmlspecialchars($prop['property_type']) ?></td>
                            <td><?= htmlspecialchars($prop['city']) ?></td>
                            <td><?= number_format($prop['price']) ?> جنيه</td>
                            <td>
                                <button class="btn-sm btn-approve" onclick="approveProperty(<?= $prop['property_id'] ?>)">✓ موافقة</button>
                                <button class="btn-sm btn-reject" onclick="rejectProperty(<?= $prop['property_id'] ?>)">✗ رفض</button>
                                <button class="btn-sm btn-view" onclick="window.open('../property_details.php?id=<?= $prop['property_id'] ?>', '_blank')">👁️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="content-section" style="text-align: center; padding: 3rem;">
                <h3 style="color: #10b981; margin-bottom: 0.5rem;">✓ لا توجد عقارات معلقة</h3>
                <p style="color: #999;">جميع العقارات تمت مراجعتها</p>
            </div>
            <?php endif; ?>
            
            <?php if (count($recent_users) > 0): ?>
            <div class="content-section">
                <h2 class="section-title">آخر المستخدمين المسجلين</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>البريد</th>
                            <th>الهاتف</th>
                            <th>النوع</th>
                            <th>تاريخ التسجيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['full_name']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td><?= $user['user_type'] == 'owner' ? 'مالك عقار' : 'زبون' ?></td>
                            <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function approveProperty(id) {
            if (confirm('هل تريد الموافقة على هذا العقار؟')) {
                fetch('../api/admin_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'approve', property_id: id})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✓ تم الموافقة على العقار');
                        location.reload();
                    } else {
                        alert('خطأ: ' + data.message);
                    }
                });
            }
        }
        
        function rejectProperty(id) {
            const reason = prompt('سبب الرفض (اختياري):');
            if (reason !== null) {
                fetch('../api/admin_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({action: 'reject', property_id: id, reason: reason})
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('✓ تم رفض العقار');
                        location.reload();
                    } else {
                        alert('خطأ: ' + data.message);
                    }
                });
            }
        }
    </script>
</body>
</html>