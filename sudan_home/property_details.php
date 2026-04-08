<?php
require_once 'config/database.php';

$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$property_id) {
    header('Location: index.php');
    exit;
}

// جلب بيانات العقار
$stmt = $pdo->prepare("
    SELECT p.*, l.city, l.district, u.full_name as owner_name, u.phone as owner_phone, u.user_id as owner_id
    FROM properties p
    LEFT JOIN locations l ON p.location_id = l.location_id
    LEFT JOIN users u ON p.owner_id = u.user_id
    WHERE p.property_id = ? AND p.status = 'active'
");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    header('Location: index.php');
    exit;
}

// جلب الصور
$stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC");
$stmt->execute([$property_id]);
$images = $stmt->fetchAll();

// زيادة عدد المشاهدات
$stmt = $pdo->prepare("UPDATE properties SET views_count = views_count + 1 WHERE property_id = ?");
$stmt->execute([$property_id]);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['title']) ?> - دار السودان</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; direction: rtl; }
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 0; }
        nav { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .gallery { background: white; border-radius: 15px; overflow: hidden; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .main-image { width: 100%; height: 500px; object-fit: cover; }
        .thumbnail-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem; padding: 1rem; background: #f9f9f9; }
        .thumbnail { width: 100%; height: 100px; object-fit: cover; cursor: pointer; border-radius: 8px; border: 3px solid transparent; transition: all 0.3s; }
        .thumbnail:hover, .thumbnail.active { border-color: #667eea; }
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .main-content { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .property-title { font-size: 2rem; color: #333; margin-bottom: 1rem; }
        .property-meta { display: flex; gap: 2rem; color: #666; margin-bottom: 1rem; flex-wrap: wrap; }
        .badge { display: inline-block; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .badge-verified { background: #10b981; color: white; }
        .badge-sale { background: #667eea; color: white; }
        .price-section { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 2rem; border-radius: 12px; margin: 2rem 0; }
        .price { font-size: 2.5rem; font-weight: bold; }
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; margin: 2rem 0; }
        .feature-item { text-align: center; padding: 1.5rem; background: #f9f9f9; border-radius: 12px; }
        .feature-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .feature-value { font-size: 1.3rem; font-weight: bold; color: #333; }
        .sidebar { display: flex; flex-direction: column; gap: 1.5rem; }
        .contact-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .owner-avatar { width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; }
        .btn { width: 100%; padding: 1rem; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; margin-bottom: 0.8rem; transition: all 0.3s; text-align: center; display: block; text-decoration: none; font-size: 1rem; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
        .btn-secondary { background: #10b981; color: white; }
        .btn-secondary:hover { background: #059669; }
        .btn-outline { background: white; color: #667eea; border: 2px solid #667eea; }
        .btn-outline:hover { background: #667eea; color: white; }
        .description { line-height: 1.8; color: #555; margin: 2rem 0; white-space: pre-wrap; }
        @media (max-width: 768px) { .content-grid { grid-template-columns: 1fr; } .main-image { height: 300px; } }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">🏡 دار السودان</div>
            <a href="index.php" style="color: white; text-decoration: none;">← العودة للرئيسية</a>
        </nav>
    </header>
    
    <div class="container">
        <?php if (count($images) > 0): ?>
        <div class="gallery">
            <img src="<?= htmlspecialchars($images[0]['image_path']) ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="main-image" id="mainImage">
            
            <?php if (count($images) > 1): ?>
            <div class="thumbnail-container">
                <?php foreach ($images as $index => $image): ?>
                    <img src="<?= htmlspecialchars($image['image_path']) ?>" 
                         class="thumbnail <?= $index == 0 ? 'active' : '' ?>" 
                         onclick="changeImage('<?= htmlspecialchars($image['image_path']) ?>', this)">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="content-grid">
            <div class="main-content">
                <h1 class="property-title"><?= htmlspecialchars($property['title']) ?></h1>
                
                <div class="property-meta">
                    <span>📍 <?= htmlspecialchars($property['city']) ?> - <?= htmlspecialchars($property['district']) ?></span>
                    <span>👁️ <?= $property['views_count'] ?> مشاهدة</span>
                    <span>🕒 <?= date('Y-m-d', strtotime($property['created_at'])) ?></span>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <?php if ($property['is_verified']): ?>
                        <span class="badge badge-verified">موثّق ✓</span>
                    <?php endif; ?>
                    <span class="badge badge-sale"><?= htmlspecialchars($property['listing_type']) ?></span>
                </div>
                
                <div class="price-section">
                    <div class="price"><?= number_format($property['price']) ?> جنيه</div>
                    <div><?= htmlspecialchars($property['listing_type']) ?></div>
                </div>
                
                <h2 style="margin: 2rem 0 1rem; color: #333;">📊 المواصفات</h2>
                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">📐</div>
                        <div style="color: #999; font-size: 0.85rem;">المساحة</div>
                        <div class="feature-value"><?= number_format($property['area']) ?> م²</div>
                    </div>
                    
                    <?php if ($property['bedrooms']): ?>
                    <div class="feature-item">
                        <div class="feature-icon">🛏️</div>
                        <div style="color: #999; font-size: 0.85rem;">غرف النوم</div>
                        <div class="feature-value"><?= $property['bedrooms'] ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($property['bathrooms']): ?>
                    <div class="feature-item">
                        <div class="feature-icon">🚿</div>
                        <div style="color: #999; font-size: 0.85rem;">دورات المياه</div>
                        <div class="feature-value"><?= $property['bathrooms'] ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="feature-item">
                        <div class="feature-icon">🏗️</div>
                        <div style="color: #999; font-size: 0.85rem;">النوع</div>
                        <div class="feature-value"><?= htmlspecialchars($property['property_type']) ?></div>
                    </div>
                </div>
                
                <h2 style="margin: 2rem 0 1rem; color: #333;">📝 الوصف</h2>
                <div class="description"><?= nl2br(htmlspecialchars($property['description'])) ?></div>
                
                <h2 style="margin: 2rem 0 1rem; color: #333;">📍 الموقع</h2>
                <p style="color: #666;"><?= htmlspecialchars($property['address']) ?></p>
            </div>
            
            <div class="sidebar">
                <div class="contact-card">
                    <h3 style="margin-bottom: 1.5rem; text-align: center;">تواصل مع المالك</h3>
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div class="owner-avatar">👤</div>
                        <div style="font-weight: bold; margin-bottom: 0.3rem;"><?= htmlspecialchars($property['owner_name']) ?></div>
                        <div style="color: #666; font-size: 0.9rem;">مالك العقار</div>
                    </div>
                    
                    <?php if (isLoggedIn() && $_SESSION['user_id'] != $property['owner_id']): ?>
                        <?php if ($property['listing_type'] == 'للبيع'): ?>
                            <a href="checkout.php?id=<?= $property_id ?>" class="btn btn-primary">
                                🛒 شراء الآن
                            </a>
                        <?php endif; ?>
                        
                        <button class="btn btn-secondary" onclick="alert('سيتم فتح نافذة المحادثة')">
                            💬 إرسال رسالة
                        </button>
                        
                        <button class="btn btn-outline" onclick="alert('رقم الهاتف: <?= htmlspecialchars($property['owner_phone']) ?>')">
                            📞 إظهار رقم الهاتف
                        </button>
                    <?php elseif (isLoggedIn() && $_SESSION['user_id'] == $property['owner_id']): ?>
                        <div style="text-align: center; padding: 2rem; background: #f9f9f9; border-radius: 10px; color: #666;">
                            هذا العقار الخاص بك
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">سجل دخول للشراء</a>
                        <a href="register.php" class="btn btn-outline">إنشاء حساب جديد</a>
                    <?php endif; ?>
                </div>
                
                <div class="contact-card">
                    <h3 style="margin-bottom: 1rem;">معلومات إضافية</h3>
                    <div style="color: #666; line-height: 2;">
                        <div style="margin-bottom: 0.5rem;">🆔 رقم الإعلان: <strong>#<?= $property['property_id'] ?></strong></div>
                        <div style="margin-bottom: 0.5rem;">📅 تاريخ النشر: <strong><?= date('Y-m-d', strtotime($property['created_at'])) ?></strong></div>
                        <div>✅ الحالة: <strong style="color: #10b981;">متاح</strong></div>
                    </div>
                </div>
                
                <div class="contact-card" style="background: #fffbeb; border: 2px solid #fbbf24;">
                    <h3 style="margin-bottom: 1rem; color: #92400e;">⚠️ نصائح الأمان</h3>
                    <ul style="color: #78350f; font-size: 0.9rem; padding-right: 1.5rem; line-height: 1.8;">
                        <li>تحقق من هوية المالك</li>
                        <li>لا تدفع مقدماً قبل المعاينة</li>
                        <li>قم بالمعاينة شخصياً</li>
                        <li>استخدم طرق دفع آمنة</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function changeImage(src, thumbnail) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }
    </script>
</body>
</html>