<?php
require_once '../config/database.php';

checkPermission('admin');

// جلب إحصائيات المبيعات
$stats = [
    'total_sales' => $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_status = 'completed'")->fetchColumn(),
    'pending_sales' => $pdo->query("SELECT COUNT(*) FROM sales WHERE payment_status = 'pending'")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT SUM(total_amount) FROM sales WHERE payment_status = 'completed'")->fetchColumn(),
    'today_sales' => $pdo->query("SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE() AND payment_status = 'completed'")->fetchColumn(),
];

// جلب المبيعات
$stmt = $pdo->prepare("
    SELECT s.*, 
           p.title as property_title, p.property_type,
           buyer.full_name as buyer_name_user, buyer.email as buyer_email_user,
           seller.full_name as seller_name
    FROM sales s
    LEFT JOIN properties p ON s.property_id = p.property_id
    LEFT JOIN users buyer ON s.buyer_id = buyer.user_id
    LEFT JOIN users seller ON s.seller_id = seller.user_id
    ORDER BY s.sale_date DESC
    LIMIT 50
");
$stmt->execute();
$sales = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المبيعات - دار السودان</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; direction: rtl; }
        .dashboard { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .sidebar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2rem 0; }
        .logo { padding: 0 1.5rem; font-size: 1.5rem; font-weight: bold; margin-bottom: 2rem; text-align: center; }
        .menu { list-style: none; }
        .menu-item { padding: 1rem 1.5rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: space-between; text-decoration: none; color: white; display: block; }
        .menu-item:hover, .menu-item.active { background: rgba(255, 255, 255, 0.2); border-right: 4px solid white; }
        .main-content { padding: 2rem; }
        .header { background: white; padding: 1.5rem 2rem; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-value { font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem; }
        .stat-label { color: #999; font-size: 0.9rem; }
        .stat-revenue { color: #10b981; }
        .stat-total { color: #667eea; }
        .stat-pending { color: #f59e0b; }
        .stat-today { color: #ef4444; }
        .content-section { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .section-title { font-size: 1.4rem; color: #333; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #e0e0e0; }
        .filters { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .filter-select { padding: 0.7rem 1rem; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 0.95rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: #f9f9f9; padding: 1rem; text-align: right; font-weight: 600; color: #333; border-bottom: 2px solid #e0e0e0; }
        .table td { padding: 1rem; border-bottom: 1px solid #f0f0f0; color: #666; }
        .table tr:hover { background: #fafafa; }
        .badge { padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.85rem; font-weight: 600; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        .badge-card { background: #dbeafe; color: #1e40af; }
        .badge-bank { background: #fce7f3; color: #831843; }
        .badge-wallet { background: #e0e7ff; color: #3730a3; }
        .badge-cash { background: #dcfce7; color: #14532d; }
        .btn-view { background: #667eea; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
        @media (max-width: 768px) { .dashboard { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <div class="logo">🏡 دار السودان</div>
            <ul class="menu">
                <a href="dashboard.php" class="menu-item">📊 نظرة عامة</a>
                <a href="sales.php" class="menu-item active">
                    💰 المبيعات
                    <?php if ($stats['pending_sales'] > 0): ?>
                        <span style="background: #ef4444; color: white; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.8rem;">
                            <?= $stats['pending_sales'] ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="dashboard.php" class="menu-item">⏳ العقارات المعلقة</a>
                <a href="#" class="menu-item">🏠 جميع العقارات</a>
                <a href="#" class="menu-item">👥 المستخدمين</a>
                <a href="#" class="menu-item">⚙️ الإعدادات</a>
                <a href="../api/logout.php" class="menu-item" style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 1rem;">
                    🚪 تسجيل الخروج
                </a>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="header">
                <h1>إدارة المبيعات</h1>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
                    <div style="font-size: 0.85rem; color: #999;"><?= htmlspecialchars($_SESSION['email']) ?></div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">إجمالي الإيرادات</div>
                    <div class="stat-value stat-revenue"><?= number_format($stats['total_revenue'] ?? 0) ?> جنيه</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">إجمالي المبيعات</div>
                    <div class="stat-value stat-total"><?= $stats['total_sales'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">معلقة</div>
                    <div class="stat-value stat-pending"><?= $stats['pending_sales'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">مبيعات اليوم</div>
                    <div class="stat-value stat-today"><?= $stats['today_sales'] ?></div>
                </div>
            </div>
            
            <div class="content-section">
                <h2 class="section-title">جميع عمليات البيع</h2>
                
                <div class="filters">
                    <select class="filter-select" onchange="filterSales(this.value, 'status')">
                        <option value="">جميع الحالات</option>
                        <option value="completed">مكتملة</option>
                        <option value="pending">معلقة</option>
                        <option value="failed">فاشلة</option>
                    </select>
                    
                    <select class="filter-select" onchange="filterSales(this.value, 'method')">
                        <option value="">جميع طرق الدفع</option>
                        <option value="card">بطاقة ائتمان</option>
                        <option value="bank">تحويل بنكي</option>
                        <option value="wallet">محفظة إلكترونية</option>
                        <option value="cash">نقدي</option>
                    </select>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العقار</th>
                                <th>المشتري</th>
                                <th>البائع</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sale['order_number']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($sale['property_title']) ?></strong><br>
                                    <small style="color: #999;"><?= htmlspecialchars($sale['property_type']) ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($sale['buyer_name']) ?><br>
                                    <small style="color: #999;"><?= htmlspecialchars($sale['buyer_email']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($sale['seller_name']) ?></td>
                                <td><strong style="color: #10b981;"><?= number_format($sale['total_amount']) ?> جنيه</strong></td>
                                <td>
                                    <span class="badge badge-<?= $sale['payment_method'] ?>">
                                        <?php
                                        $methods = ['card' => 'بطاقة', 'bank' => 'تحويل', 'wallet' => 'محفظة', 'cash' => 'نقدي'];
                                        echo $methods[$sale['payment_method']] ?? $sale['payment_method'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $sale['payment_status'] ?>">
                                        <?php
                                        $statuses = ['completed' => '✓ مكتمل', 'pending' => '⏳ معلق', 'failed' => '✗ فاشل'];
                                        echo $statuses[$sale['payment_status']] ?? $sale['payment_status'];
                                        ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($sale['sale_date'])) ?></td>
                                <td>
                                    <button class="btn-view" onclick="viewSale(<?= $sale['sale_id'] ?>)">عرض</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function filterSales(value, type) {
            // يمكن تطبيق الفلترة باستخدام AJAX
            console.log('Filter:', type, value);
        }
        
        function viewSale(saleId) {
            alert('عرض تفاصيل عملية البيع #' + saleId);
            // يمكن فتح modal أو صفحة جديدة
        }
    </script>
</body>
</html>