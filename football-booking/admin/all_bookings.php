<?php
session_start();
require_once '../auth/check_auth.php';
require_user_type('admin');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$sql = "SELECT b.*, f.field_name, c.full_name as customer_name, o.full_name as owner_name 
        FROM bookings b 
        INNER JOIN fields f ON b.field_id = f.id 
        INNER JOIN users c ON b.customer_id = c.id 
        INNER JOIN users o ON f.owner_id = o.id 
        ORDER BY b.created_at DESC";
$bookings = $conn->query($sql);

$page_title = 'جميع الحجوزات';
include '../includes/header.php';
?>
<div class="container my-5">
    <div class="page-header">
        <h2><i class="fas fa-calendar"></i> جميع الحجوزات</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead class="thead-dark">
                <tr>
                    <th>#</th>
                    <th>العميل</th>
                    <th>الملعب</th>
                    <th>المالك</th>
                    <th>التاريخ</th>
                    <th>الوقت</th>
                    <th>المبلغ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; while ($booking = $bookings->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['field_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['owner_name']); ?></td>
                        <td><?php echo $booking['booking_date']; ?></td>
                        <td><?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></td>
                        <td><?php echo number_format($booking['total_price'], 0); ?> ج.س</td>
                        <td><?php echo get_status_badge($booking['status']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
