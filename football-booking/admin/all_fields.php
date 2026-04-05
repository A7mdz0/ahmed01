<?php
session_start();
require_once '../auth/check_auth.php';
require_user_type('admin');
require_once '../includes/db.php';

$sql = "SELECT f.*, u.full_name as owner_name FROM fields f 
        INNER JOIN users u ON f.owner_id = u.id ORDER BY f.created_at DESC";
$fields = $conn->query($sql);

$page_title = 'جميع الملاعب';
include '../includes/header.php';
?>
<div class="container my-5">
    <div class="page-header">
        <h2><i class="fas fa-futbol"></i> جميع الملاعب</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>#</th>
                    <th>اسم الملعب</th>
                    <th>المالك</th>
                    <th>المدينة</th>
                    <th>النوع</th>
                    <th>السعر</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; while ($field = $fields->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($field['field_name']); ?></td>
                        <td><?php echo htmlspecialchars($field['owner_name']); ?></td>
                        <td><?php echo htmlspecialchars($field['city']); ?></td>
                        <td><?php echo htmlspecialchars($field['field_type']); ?></td>
                        <td><?php echo number_format($field['price_per_hour'], 0); ?> ج.س</td>
                        <td><?php echo $field['is_active'] ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-secondary">معطل</span>'; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
