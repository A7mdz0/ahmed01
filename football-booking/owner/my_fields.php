<?php
session_start();
require_once '../auth/check_auth.php';
require_user_type('owner');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$owner_id = $_SESSION['user_id'];
$sql = "SELECT * FROM fields WHERE owner_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$fields = $stmt->get_result();

$page_title = 'ملاعبي';
$home_path = '/football-booking/index.php';
$search_path = '/football-booking/search.php';
$css_path = '/football-booking/assets/css/style.css';

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<?php include '../includes/header.php'; ?>
<div class="container my-5">
    <div class="page-header">
        <h2><i class="fas fa-futbol"></i> ملاعبي</h2>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="mb-3">
        <a href="add_field.php" class="btn btn-success">
            <i class="fas fa-plus"></i> إضافة ملعب جديد
        </a>
    </div>
    
    <div class="row">
        <?php if ($fields->num_rows > 0): ?>
            <?php while ($field = $fields->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <?php if ($field['image_path']): ?>
                            <img src="../assets/images/fields/<?php echo $field['image_path']; ?>" class="card-img-top">
                        <?php else: ?>
                            <div style="height: 200px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-futbol fa-5x text-white"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5><?php echo htmlspecialchars($field['field_name']); ?></h5>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo $field['city']; ?></p>
                            <p class="price-tag"><?php echo number_format($field['price_per_hour'], 0); ?> ج.س/ساعة</p>
                            <div>
                                <a href="edit_field.php?id=<?php echo $field['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> تعديل
                                </a>
                                <a href="delete_field.php?id=<?php echo $field['id']; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('هل أنت متأكد من حذف الملعب؟')">
                                    <i class="fas fa-trash"></i> حذف
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 empty-state">
                <i class="fas fa-futbol"></i>
                <h4>لم تضف أي ملعب بعد</h4>
                <a href="add_field.php" class="btn btn-success btn-lg">
                    <i class="fas fa-plus"></i> إضافة ملعب جديد
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
