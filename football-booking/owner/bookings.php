<?php
session_start();
require_once '../auth/check_auth.php';
require_user_type('owner');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$owner_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

$sql = "SELECT b.*, f.field_name, u.full_name as customer_name, u.phone as customer_phone 
        FROM bookings b 
        INNER JOIN fields f ON b.field_id = f.id 
        INNER JOIN users u ON b.customer_id = u.id 
        WHERE f.owner_id = ?";

if ($status_filter) {
    $sql .= " AND b.status = ?";
}

$sql .= " ORDER BY b.booking_date DESC, b.start_time DESC";

$stmt = $conn->prepare($sql);
if ($status_filter) {
    $stmt->bind_param("is", $owner_id, $status_filter);
} else {
    $stmt->bind_param("i", $owner_id);
}
$stmt->execute();
$bookings = $stmt->get_result();

$page_title = 'الحجوزات';
include '../includes/header.php';
?>
<div class="container my-5">
    <div class="page-header">
        <h2><i class="fas fa-calendar"></i> الحجوزات</h2>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <a href="bookings.php" class="btn btn-outline-secondary <?php echo empty($status_filter) ? 'active' : ''; ?>">الكل</a>
            <a href="bookings.php?status=معلق" class="btn btn-outline-warning <?php echo $status_filter === 'معلق' ? 'active' : ''; ?>">معلقة</a>
            <a href="bookings.php?status=مؤكد" class="btn btn-outline-success <?php echo $status_filter === 'مؤكد' ? 'active' : ''; ?>">مؤكدة</a>
            <a href="bookings.php?status=مرفوض" class="btn btn-outline-danger <?php echo $status_filter === 'مرفوض' ? 'active' : ''; ?>">مرفوضة</a>
        </div>
    </div>
    
    <?php if ($bookings->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>الملعب</th>
                        <th>العميل</th>
                        <th>التاريخ</th>
                        <th>الوقت</th>
                        <th>المبلغ</th>
                        <th>طريقة الدفع</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['field_name']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><br>
                                <small class="text-muted"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['customer_phone']); ?></small>
                            </td>
                            <td><?php echo $booking['booking_date']; ?></td>
                            <td>
                                <small>
                                    <?php echo date('h:i A', strtotime($booking['start_time'])); ?><br>
                                    <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                </small>
                            </td>
                            <td><strong><?php echo number_format($booking['total_price'], 0); ?> ج.س</strong></td>
                            <td>
                                <?php if ($booking['payment_method'] === 'cash'): ?>
                                    <span class="badge badge-success"><i class="fas fa-money-bill"></i> نقداً</span>
                                <?php else: ?>
                                    <span class="badge badge-primary"><i class="fas fa-credit-card"></i> بطاقة</span>
                                    <?php if (!empty($booking['card_number'])): ?>
                                        <br><small class="text-muted"><?php echo $booking['card_number']; ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_status_badge($booking['status']); ?></td>
                            <td class="actions">
                                <?php if ($booking['status'] === 'معلق'): ?>
                                    <a href="manage_booking.php?id=<?php echo $booking['id']; ?>&action=accept" 
                                       class="btn btn-sm btn-success" onclick="return confirm('هل تريد قبول الحجز؟')">
                                        <i class="fas fa-check"></i> قبول
                                    </a>
                                    <a href="manage_booking.php?id=<?php echo $booking['id']; ?>&action=reject" 
                                       class="btn btn-sm btn-danger" onclick="return confirm('هل تريد رفض الحجز؟')">
                                        <i class="fas fa-times"></i> رفض
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>تمت المعالجة</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h4>لا توجد حجوزات</h4>
            <p>لا توجد حجوزات بالفلتر المحدد</p>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
