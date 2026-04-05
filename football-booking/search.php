<?php
/**
 * صفحة البحث عن الملاعب
 */
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'البحث عن ملاعب';
$home_path = '/football-booking/index.php';
$search_path = '/football-booking/search.php';
$css_path = '/football-booking/assets/css/style.css';

// بناء استعلام البحث
$where_conditions = ["f.is_active = 1"];
$params = [];
$types = "";

if (isset($_GET['city']) && !empty($_GET['city'])) {
    $where_conditions[] = "f.city = ?";
    $params[] = $_GET['city'];
    $types .= "s";
}

if (isset($_GET['field_type']) && !empty($_GET['field_type'])) {
    $where_conditions[] = "f.field_type = ?";
    $params[] = $_GET['field_type'];
    $types .= "s";
}

if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $where_conditions[] = "f.price_per_hour <= ?";
    $params[] = $_GET['max_price'];
    $types .= "d";
}

if (isset($_GET['has_lighting']) && $_GET['has_lighting'] == '1') {
    $where_conditions[] = "f.has_lighting = 1";
}

if (isset($_GET['has_parking']) && $_GET['has_parking'] == '1') {
    $where_conditions[] = "f.has_parking = 1";
}

// بناء الاستعلام النهائي
$sql = "SELECT f.*, u.full_name as owner_name 
        FROM fields f 
        INNER JOIN users u ON f.owner_id = u.id 
        WHERE " . implode(" AND ", $where_conditions) . "
        ORDER BY f.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    
    <div class="page-header">
        <h2><i class="fas fa-search"></i> البحث عن ملاعب</h2>
    </div>
    
    <!-- نموذج البحث -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>المدينة</label>
                            <select name="city" class="form-control">
                                <option value="">جميع المدن</option>
                                <option value="الخرطوم" <?php echo (isset($_GET['city']) && $_GET['city'] == 'الخرطوم') ? 'selected' : ''; ?>>الخرطوم</option>
                                <option value="الخرطوم بحري" <?php echo (isset($_GET['city']) && $_GET['city'] == 'الخرطوم بحري') ? 'selected' : ''; ?>>الخرطوم بحري</option>
                                <option value="أم درمان" <?php echo (isset($_GET['city']) && $_GET['city'] == 'أم درمان') ? 'selected' : ''; ?>>أم درمان</option>
                                <option value="بورتسودان" <?php echo (isset($_GET['city']) && $_GET['city'] == 'بورتسودان') ? 'selected' : ''; ?>>بورتسودان</option>
                                <option value="عطبرة" <?php echo (isset($_GET['city']) && $_GET['city'] == 'عطبرة') ? 'selected' : ''; ?>>عطبرة</option>
                                <option value="ود مدني" <?php echo (isset($_GET['city']) && $_GET['city'] == 'ود مدني') ? 'selected' : ''; ?>>ود مدني</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>نوع الملعب</label>
                            <select name="field_type" class="form-control">
                                <option value="">جميع الأنواع</option>
                                <option value="خماسي" <?php echo (isset($_GET['field_type']) && $_GET['field_type'] == 'خماسي') ? 'selected' : ''; ?>>خماسي</option>
                                <option value="سباعي" <?php echo (isset($_GET['field_type']) && $_GET['field_type'] == 'سباعي') ? 'selected' : ''; ?>>سباعي</option>
                                <option value="تساعي" <?php echo (isset($_GET['field_type']) && $_GET['field_type'] == 'تساعي') ? 'selected' : ''; ?>>تساعي</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>السعر الأقصى</label>
                            <input type="number" name="max_price" class="form-control" 
                                   value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>" 
                                   placeholder="200">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="has_lighting" name="has_lighting" value="1" 
                                       <?php echo (isset($_GET['has_lighting'])) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="has_lighting">إضاءة</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="has_parking" name="has_parking" value="1"
                                       <?php echo (isset($_GET['has_parking'])) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="has_parking">موقف سيارات</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-search"></i> بحث
                            </button>
                            <a href="search.php" class="btn btn-secondary btn-block btn-sm">
                                <i class="fas fa-redo"></i> إعادة تعيين
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- النتائج -->
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3">
                النتائج: <span class="badge badge-success"><?php echo $result->num_rows; ?> ملعب</span>
            </h4>
        </div>
    </div>
    
    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($field = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
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
                                <?php echo htmlspecialchars($field['city']); ?> - <?php echo htmlspecialchars($field['address']); ?>
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
                    <i class="fas fa-search"></i>
                    <h4>لم يتم العثور على نتائج</h4>
                    <p>حاول تغيير معايير البحث</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
