<?php
require_once 'config/database.php';

// بناء استعلام البحث
$where_conditions = ["p.status = 'active'"];
$params = [];

// فلترة حسب نوع العقار
if (!empty($_GET['property_type'])) {
    $where_conditions[] = "p.property_type = ?";
    $params[] = $_GET['property_type'];
}

// فلترة حسب المدينة
if (!empty($_GET['city'])) {
    $where_conditions[] = "l.city = ?";
    $params[] = $_GET['city'];
}

// فلترة حسب نوع الإعلان
if (!empty($_GET['listing_type'])) {
    $where_conditions[] = "p.listing_type = ?";
    $params[] = $_GET['listing_type'];
}

// فلترة حسب السعر الأدنى
if (!empty($_GET['min_price'])) {
    $where_conditions[] = "p.price >= ?";
    $params[] = floatval($_GET['min_price']);
}

// فلترة حسب السعر الأقصى
if (!empty($_GET['max_price'])) {
    $where_conditions[] = "p.price <= ?";
    $params[] = floatval($_GET['max_price']);
}

// بناء الاستعلام النهائي
$where_sql = implode(' AND ', $where_conditions);

$stmt = $pdo->prepare("
    SELECT p.*, l.city, l.district, 
           (SELECT image_path FROM property_images WHERE property_id = p.property_id AND is_primary = 1 LIMIT 1) as main_image,
           u.full_name as owner_name
    FROM properties p
    LEFT JOIN locations l ON p.location_id = l.location_id
    LEFT JOIN users u ON p.owner_id = u.user_id
    WHERE $where_sql
    ORDER BY p.created_at DESC
    LIMIT 50
");
$stmt->execute($params);
$properties = $stmt->fetchAll();

// جلب المدن
$cities = $pdo->query("SELECT DISTINCT city FROM locations ORDER BY city")->fetchAll();

// حفظ قيم البحث
$search_values = [
    'property_type' => $_GET['property_type'] ?? '',
    'city' => $_GET['city'] ?? '',
    'listing_type' => $_GET['listing_type'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دار السودان - منصة العقارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: #333; 
            direction: rtl; 
            line-height: 1.6; 
        }
        
        /* Header & Navigation */
        header { 
            background: rgba(255, 255, 255, 0.98); 
            box-shadow: 0 2px 20px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 0; 
            z-index: 1000;
            backdrop-filter: blur(10px);
        }
        nav { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            gap: 2rem;
        }
        .logo { 
            font-size: 1.8rem; 
            font-weight: bold; 
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .nav-center {
            display: flex;
            gap: 2rem;
            list-style: none;
            flex: 1;
            justify-content: center;
        }
        .nav-center a { 
            text-decoration: none; 
            color: #333; 
            font-weight: 600;
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s; 
        }
        .nav-center a:hover { 
            background: rgba(102, 126, 234, 0.1);
            color: #667eea; 
        }
        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .btn-login {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-login:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        .btn-primary { 
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; 
            padding: 0.7rem 1.5rem; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: all 0.3s; 
            text-decoration: none; 
            display: inline-block;
        }
        .btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); 
        }
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-name {
            font-weight: 600;
            color: #333;
        }
        .btn-logout {
            background: transparent;
            color: #ef4444;
            border: 2px solid #ef4444;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .btn-logout:hover {
            background: #ef4444;
            color: white;
        }
        
        /* Hero Section */
        .hero-section {
            max-width: 1200px;
            margin: 3rem auto 2rem;
            padding: 0 2rem;
            text-align: center;
        }
        .hero-section h1 {
            color: white;
            font-size: 2.8rem;
            margin-bottom: 1rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .hero-section p {
            color: rgba(255,255,255,0.95);
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        /* Search Container */
        .search-container { 
            max-width: 1200px; 
            margin: 0 auto 3rem; 
            padding: 0 2rem; 
        }
        .search-box { 
            background: white; 
            padding: 2rem; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
        }
        .search-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1rem; 
            margin-bottom: 1rem; 
        }
        .search-grid select, .search-grid input { 
            padding: 0.9rem; 
            border: 2px solid #e0e0e0; 
            border-radius: 10px; 
            font-size: 1rem; 
            transition: all 0.3s;
            font-family: inherit;
        }
        .search-grid select:focus, .search-grid input:focus { 
            outline: none; 
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-search {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.9rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .search-results-info {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-clear {
            background: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }
        
        /* Properties Grid */
        .properties-container { 
            max-width: 1200px; 
            margin: 2rem auto; 
            padding: 0 2rem; 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 2rem; 
        }
        .property-card { 
            background: white; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 5px 20px rgba(0,0,0,0.1); 
            transition: all 0.3s; 
            cursor: pointer; 
        }
        .property-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 15px 40px rgba(0,0,0,0.2); 
        }
        .property-image-wrapper {
            position: relative;
            height: 220px;
            overflow: hidden;
        }
        .property-image { 
            width: 100%; 
            height: 100%; 
            object-fit: cover;
            transition: transform 0.3s;
        }
        .property-card:hover .property-image {
            transform: scale(1.05);
        }
        .badge { 
            position: absolute; 
            top: 15px; 
            right: 15px; 
            background: #667eea; 
            color: white; 
            padding: 0.4rem 1rem; 
            border-radius: 25px; 
            font-size: 0.85rem; 
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .verified-badge { 
            background: #10b981; 
        }
        .property-info { 
            padding: 1.5rem; 
        }
        .property-title { 
            font-size: 1.2rem; 
            font-weight: bold; 
            margin-bottom: 0.5rem; 
            color: #333;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .property-location { 
            color: #666; 
            font-size: 0.9rem; 
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .property-details { 
            display: flex; 
            justify-content: space-between; 
            padding: 1rem 0; 
            border-top: 1px solid #e0e0e0; 
            border-bottom: 1px solid #e0e0e0; 
            margin-bottom: 1rem; 
        }
        .detail-item { 
            text-align: center; 
        }
        .detail-label { 
            font-size: 0.8rem; 
            color: #999; 
        }
        .detail-value { 
            font-weight: bold; 
            color: #333; 
            margin-top: 0.2rem; 
        }
        .property-price { 
            font-size: 1.5rem; 
            font-weight: bold; 
            color: #667eea; 
            margin-bottom: 1rem; 
        }
        .btn-contact { 
            background: #10b981; 
            color: white; 
            padding: 0.7rem 1.5rem; 
            border: none; 
            border-radius: 10px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s;
            width: 100%;
        }
        .btn-contact:hover { 
            background: #059669;
            transform: translateY(-2px);
        }
        
        /* No Properties */
        .no-properties { 
            text-align: center; 
            padding: 4rem 2rem; 
            background: white; 
            border-radius: 20px; 
            margin: 2rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .no-properties-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        /* Footer */
        footer { 
            background: rgba(255, 255, 255, 0.98); 
            margin-top: 4rem; 
            padding: 2rem; 
            text-align: center; 
            color: #666;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        
        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: #333;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) { 
            .nav-center { 
                display: none; 
            }
            .mobile-menu-btn {
                display: block;
            }
            .hero-section h1 {
                font-size: 2rem;
            }
            .search-grid {
                grid-template-columns: 1fr;
            }
            .properties-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
    <header>
        <nav>
            <div class="logo">
                🏡 دار السودان
            </div>
            
            <ul class="nav-center">
                <li><a href="index.php">الرئيسية</a></li>
                <li><a href="index.php">العقارات</a></li>
                <li><a href="#about">عن المنصة</a></li>
            </ul>
            
            <button class="mobile-menu-btn">☰</button>
            
            <?php if (isLoggedIn()): ?>
                <div class="nav-actions">
                    <div class="user-menu">
                        <span class="user-name">مرحباً، <?= htmlspecialchars($_SESSION['full_name']) ?></span>
                        
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                            <a href="admin/dashboard.php" class="btn-primary">لوحة التحكم</a>
                        <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'owner'): ?>
                            <a href="add_property.php" class="btn-primary">أضف عقارك</a>
                        <?php endif; ?>
                        
                        <a href="api/logout.php" class="btn-logout">تسجيل الخروج</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="nav-actions">
                    <a href="login.php" class="btn-login">تسجيل الدخول</a>
                    <a href="register.php" class="btn-primary">إنشاء حساب</a>
                </div>
            <?php endif; ?>
        </nav>
    </header>
    
    <div class="hero-section">
        <h1>ابحث عن منزل أحلامك في السودان</h1>
        <p>آلاف العقارات المميزة في انتظارك - بيع، شراء، أو استئجار بكل سهولة</p>
    </div>
    
    <div class="search-container">
        <div class="search-box">
            <form method="GET" action="index.php">
                <div class="search-grid">
                    <select name="property_type">
                        <option value="">نوع العقار</option>
                        <option value="شقة" <?= $search_values['property_type'] == 'شقة' ? 'selected' : '' ?>>شقة</option>
                        <option value="فيلا" <?= $search_values['property_type'] == 'فيلا' ? 'selected' : '' ?>>فيلا</option>
                        <option value="منزل" <?= $search_values['property_type'] == 'منزل' ? 'selected' : '' ?>>منزل</option>
                        <option value="أرض" <?= $search_values['property_type'] == 'أرض' ? 'selected' : '' ?>>أرض</option>
                        <option value="محل تجاري" <?= $search_values['property_type'] == 'محل تجاري' ? 'selected' : '' ?>>محل تجاري</option>
                    </select>
                    
                    <select name="city">
                        <option value="">المدينة</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?= htmlspecialchars($city['city']) ?>" 
                                <?= $search_values['city'] == $city['city'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['city']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="listing_type">
                        <option value="">نوع الإعلان</option>
                        <option value="للبيع" <?= $search_values['listing_type'] == 'للبيع' ? 'selected' : '' ?>>للبيع</option>
                        <option value="للإيجار" <?= $search_values['listing_type'] == 'للإيجار' ? 'selected' : '' ?>>للإيجار</option>
                    </select>
                    
                    <input type="number" name="min_price" placeholder="السعر الأدنى" value="<?= htmlspecialchars($search_values['min_price']) ?>">
                    <input type="number" name="max_price" placeholder="السعر الأقصى" value="<?= htmlspecialchars($search_values['max_price']) ?>">
                    
                    <button type="submit" class="btn-search">🔍 بحث</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (!empty($_GET)): ?>
    <div class="search-container">
        <div class="search-results-info">
            <span><strong>نتائج البحث:</strong> تم العثور على <?= count($properties) ?> عقار</span>
            <a href="index.php" class="btn-clear">✗ مسح البحث</a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (count($properties) > 0): ?>
    <div class="properties-container">
        <?php foreach ($properties as $property): ?>
        <div class="property-card" onclick="window.location.href='property_details.php?id=<?= $property['property_id'] ?>'">
            <div class="property-image-wrapper">
                <?php if ($property['main_image']): ?>
                    <img src="<?= htmlspecialchars($property['main_image']) ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="property-image">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #e0e0e0, #f5f5f5); display: flex; align-items: center; justify-content: center; color: #999; font-size: 3rem;">🏠</div>
                <?php endif; ?>
                
                <?php if ($property['is_verified']): ?>
                    <span class="badge verified-badge">موثّق ✓</span>
                <?php else: ?>
                    <span class="badge"><?= htmlspecialchars($property['listing_type']) ?></span>
                <?php endif; ?>
            </div>
            <div class="property-info">
                <h3 class="property-title"><?= htmlspecialchars($property['title']) ?></h3>
                <div class="property-location">
                    📍 <?= htmlspecialchars($property['city']) ?> - <?= htmlspecialchars($property['district']) ?>
                </div>
                <div class="property-details">
                    <div class="detail-item">
                        <div class="detail-label">المساحة</div>
                        <div class="detail-value"><?= number_format($property['area']) ?> م²</div>
                    </div>
                    <?php if ($property['bedrooms']): ?>
                    <div class="detail-item">
                        <div class="detail-label">غرف النوم</div>
                        <div class="detail-value"><?= $property['bedrooms'] ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($property['bathrooms']): ?>
                    <div class="detail-item">
                        <div class="detail-label">الحمامات</div>
                        <div class="detail-value"><?= $property['bathrooms'] ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="property-price"><?= number_format($property['price']) ?> جنيه</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                    <button class="btn-contact" onclick="event.stopPropagation(); <?= isLoggedIn() ? "alert('سيتم فتح نافذة المحادثة')" : "window.location.href='login.php'" ?>" style="background: #10b981;">
                        💬 تواصل
                    </button>
                    <button class="btn-contact" onclick="event.stopPropagation(); <?= isLoggedIn() ? "window.location.href='checkout.php?id={$property['property_id']}'" : "window.location.href='login.php'" ?>" style="background: #667eea;">
                        🛒 شراء
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="no-properties">
        <div class="no-properties-icon">🔍</div>
        <h2 style="color: #333; margin-bottom: 1rem;">لم يتم العثور على عقارات</h2>
        <p style="color: #666; margin-bottom: 2rem;">جرب تغيير معايير البحث أو تصفح جميع العقارات</p>
        <a href="index.php" class="btn-primary">عرض جميع العقارات</a>
    </div>
    <?php endif; ?>
    
    <footer>
        <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem;">© 2026 دار السودان - منصة العقارات الموثوقة</p>
        <p style="margin-top: 0.5rem; color: #999;">منصة آمنة وموثوقة لبيع وشراء العقارات</p>
    </footer>
    
</body>
</html>