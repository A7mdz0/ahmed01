<?php
session_start();
require_once '../auth/check_auth.php';
require_user_type('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$sql = "SELECT * FROM users ORDER BY created_at DESC";
$users = $conn->query($sql);

$page_title = 'إدارة المستخدمين';
include '../includes/header.php';
?>
<div class="container my-5">
    <div class="page-header">
        <h2><i class="fas fa-users"></i> إدارة المستخدمين</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>#</th>
                    <th>الاسم</th>
                    <th>البريد</th>
                    <th>الهاتف</th>
                    <th>النوع</th>
                    <th>الحالة</th>
                    <th>تاريخ التسجيل</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <?php if ($user['user_type'] === 'customer'): ?>
                                <span class="badge badge-primary">عميل</span>
                            <?php elseif ($user['user_type'] === 'owner'): ?>
                                <span class="badge badge-success">مالك</span>
                            <?php else: ?>
                                <span class="badge badge-danger">مدير</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $user['is_active'] ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-secondary">معطل</span>'; ?>
                        </td>
                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
