<?php
/**
 * الصفحة الرئيسية
 */
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'نظام حجز ملاعب كرة القدم - السودان';
$home_path = '/football-booking/index.php';
$search_path = '/football-booking/search.php';
$css_path = '/football-booking/assets/css/style.css';

// جلب بعض الملاعب المميزة
$sql = "SELECT f.*, u.full_name as owner_name 
        FROM fields f 
        INNER JOIN users u ON f.owner_id = u.id 
        WHERE f.is_active = 1 
        ORDER BY f.created_at DESC 
        LIMIT 6";
$result = $conn->query($sql);
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <h1><i class="fas fa-futbol"></i> احجز ملعبك بسهولة</h1>
        <p>أفضل ملاعب كرة القدم في السودان بين يديك</p>
        <a href="search.php" class="btn btn-light btn-lg">
            <i class="fas fa-search"></i> ابحث عن ملعب الآن
        </a>
        <?php if (!is_logged_in()): ?>
            <a href="auth/register.php" class="btn btn-outline-light btn-lg ml-2">
                <i class="fas fa-user-plus"></i> سجل الآن
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Main Content -->
<div class="container my-5">
    
    <!-- قسم البحث السريع -->
    <div class="search-section fade-in">
        <h3 class="text-center mb-4"><i class="fas fa-filter"></i> بحث سريع</h3>
        <form action="search.php" method="GET">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>المدينة</label>
                        <select name="city" class="form-control">
                            <option value="">جميع المدن</option>
                            <option value="الخرطوم">الخرطوم</option>
                            <option value="الخرطوم بحري">الخرطوم بحري</option>
                            <option value="أم درمان">أم درمان</option>
                            <option value="بورتسودان">بورتسودان</option>
                            <option value="عطبرة">عطبرة</option>
                            <option value="ود مدني">ود مدني</option>
                            <option value="كسلا">كسلا</option>
                            <option value="الأبيض">الأبيض</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>نوع الملعب</label>
                        <select name="field_type" class="form-control">
                            <option value="">جميع الأنواع</option>
                            <option value="خماسي">خماسي</option>
                            <option value="سباعي">سباعي</option>
                            <option value="تساعي">تساعي</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>السعر الأقصى (جنيه/ساعة)</label>
                        <input type="number" name="max_price" class="form-control" placeholder="مثال: 200">
                    </div>
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-search"></i> بحث
                </button>
            </div>
        </form>
    </div>

    <!-- الملاعب المميزة -->
    <div class="mt-5">
        <h2 class="text-center mb-4"><i class="fas fa-star text-warning"></i> ملاعب مميزة</h2>
        <div class="row">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($field = $result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card fade-in">
                            <?php if ($field['image_path']): ?>
                                <img src="assets/images/fields/<?php echo htmlspecialchars($field['image_path']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($field['field_name']); ?>">
                            <?php else: ?>
                                <div style="height: 200px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-futbol fa-5x text-white"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($field['field_name']); ?></h5>
                                <p class="card-text">
                                    <i class="fas fa-map-marker-alt text-danger"></i> 
                                    <?php echo htmlspecialchars($field['city']); ?>
                                </p>
                                <p class="card-text">
                                    <i class="fas fa-users text-primary"></i> 
                                    ملعب <?php echo htmlspecialchars($field['field_type']); ?>
                                </p>
                                
                                <!-- المميزات -->
                                <div class="mb-2">
                                    <?php if ($field['has_lighting']): ?>
                                        <span class="badge badge-success"><i class="fas fa-lightbulb"></i> إضاءة</span>
                                    <?php endif; ?>
                                    <?php if ($field['has_parking']): ?>
                                        <span class="badge badge-info"><i class="fas fa-parking"></i> موقف</span>
                                    <?php endif; ?>
                                    <?php if ($field['has_changing_rooms']): ?>
                                        <span class="badge badge-warning"><i class="fas fa-door-open"></i> غرف تغيير</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="price-tag mb-3">
                                    <?php echo number_format($field['price_per_hour'], 0); ?> ج.س / ساعة
                                </p>
                                
                                <div class="text-center">
                                    <a href="field_details.php?id=<?php echo $field['id']; ?>" 
                                       class="btn btn-success btn-block">
                                        <i class="fas fa-info-circle"></i> التفاصيل والحجز
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <i class="fas fa-futbol"></i>
                        <h4>لا توجد ملاعب متاحة حالياً</h4>
                        <p>تحقق لاحقاً أو كن أول من يضيف ملعبه!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="text-center mt-4">
                <a href="search.php" class="btn btn-outline-success btn-lg">
                    <i class="fas fa-list"></i> عرض جميع الملاعب
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- المميزات -->
    <div class="row mt-5">
        <div class="col-md-4 text-center mb-4">
            <div class="stats-card fade-in">
                <i class="fas fa-calendar-check text-success"></i>
                <h4>حجز سهل وسريع</h4>
                <p>احجز ملعبك في دقائق معدودة من أي مكان</p>
            </div>
        </div>
        <div class="col-md-4 text-center mb-4">
            <div class="stats-card fade-in">
                <i class="fas fa-shield-alt text-primary"></i>
                <h4>آمن وموثوق</h4>
                <p>نظام حجز محمي ومؤمن بالكامل لراحة بالك</p>
            </div>
        </div>
        <div class="col-md-4 text-center mb-4">
            <div class="stats-card fade-in">
                <i class="fas fa-money-bill-wave text-warning"></i>
                <h4>أسعار منافسة</h4>
                <p>أفضل الأسعار والعروض في السوق السوداني</p>
            </div>
        </div>
    </div>
    
    <!-- دعوة للعمل -->
    <?php if (!is_logged_in()): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                    <div class="card-body text-center py-5">
                        <h3><i class="fas fa-user-plus"></i> انضم إلينا الآن!</h3>
                        <p class="lead">سجل حسابك واستمتع بتجربة حجز سهلة ومريحة</p>
                        <a href="auth/register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus"></i> تسجيل حساب جديد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
