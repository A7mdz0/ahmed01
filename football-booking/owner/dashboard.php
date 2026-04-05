<?php
/**
 * لوحة تحكم مالك الملعب
 */
session_start();
require_once '../auth/check_auth.php';
require_user_type('owner');

require_once '../includes/db.php';
require_once '../includes/functions.php';

$owner_id = $_SESSION['user_id'];

// إحصائيات المالك
$stats = [];

// عدد الملاعب
$sql = "SELECT COUNT(*) as count FROM fields WHERE owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_fields'] = $result->fetch_assoc()['count'];

// عدد الحجوزات المعلقة
$sql = "SELECT COUNT(*) as count FROM bookings b 
        INNER JOIN fields f ON b.field_id = f.id 
        WHERE f.owner_id = ? AND b.status = 'معلق'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['pending_bookings'] = $result->fetch_assoc()['count'];

// عدد الحجوزات المؤكدة
$sql = "SELECT COUNT(*) as count FROM bookings b 
        INNER JOIN fields f ON b.field_id = f.id 
        WHERE f.owner_id = ? AND b.status = 'مؤكد'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['confirmed_bookings'] = $result->fetch_assoc()['count'];

// إجمالي الأرباح
$sql = "SELECT SUM(b.total_price) as total FROM bookings b 
        INNER JOIN fields f ON b.field_id = f.id 
        WHERE f.owner_id = ? AND b.status IN ('مؤكد', 'مكتمل')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// آخر الحجوزات المعلقة
$recent_sql = "SELECT b.*, f.field_name, u.full_name as customer_name, u.phone as customer_phone 
               FROM bookings b 
               INNER JOIN fields f ON b.field_id = f.id 
               INNER JOIN users u ON b.customer_id = u.id 
               WHERE f.owner_id = ? AND b.status = 'معلق' 
               ORDER BY b.created_at DESC 
               LIMIT 5";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $owner_id);
$recent_stmt->execute();
$pending_bookings = $recent_stmt->get_result();

$page_title = 'لوحة تحكم المالك';
$home_path = '/football-booking/index.php';
$search_path = '/football-booking/search.php';
$css_path = '/football-booking/assets/css/style.css';
?>

<?php include '../includes/header.php'; ?>

<div class="container my-5">
    
    <!-- الترحيب -->
    <div class="page-header">
        <h2>
            <i class="fas fa-tachometer-alt"></i> 
            مرحباً، <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        </h2>
        <p class="text-muted mb-0">لوحة التحكم - إدارة ملاعبك</p>
    </div>
    
    <!-- الإحصائيات -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="dashboard-card info">
                <h3><?php echo $stats['total_fields']; ?></h3>
                <p><i class="fas fa-futbol"></i> إجمالي الملاعب</p>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="dashboard-card warning">
                <h3><?php echo $stats['pending_bookings']; ?></h3>
                <p><i class="fas fa-clock"></i> حجوزات معلقة</p>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="dashboard-card success">
                <h3><?php echo $stats['confirmed_bookings']; ?></h3>
                <p><i class="fas fa-check-circle"></i> حجوزات مؤكدة</p>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="dashboard-card">
                <h3><?php echo number_format($stats['total_revenue'], 0); ?></h3>
                <p><i class="fas fa-money-bill-wave"></i> إجمالي الأرباح (ج.س)</p>
            </div>
        </div>
    </div>
    
    <!-- روابط سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <a href="add_field.php" class="btn btn-success btn-block btn-lg">
                <i class="fas fa-plus"></i> إضافة ملعب جديد
            </a>
        </div>
        <div class="col-md-3">
            <a href="my_fields.php" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-list"></i> ملاعبي
            </a>
        </div>
        <div class="col-md-3">
            <a href="bookings.php" class="btn btn-info btn-block btn-lg">
                <i class="fas fa-calendar"></i> جميع الحجوزات
            </a>
        </div>
        <div class="col-md-3">
            <a href="bookings.php?status=معلق" class="btn btn-warning btn-block btn-lg">
                <i class="fas fa-hourglass-half"></i> الحجوزات المعلقة
            </a>
        </div>
    </div>
    
    <!-- الحجوزات المعلقة -->
    <div class="card">
        <div class="card-header bg-warning text-white">
            <h4 class="mb-0"><i class="fas fa-clock"></i> حجوزات في انتظار الموافقة</h4>
        </div>
        <div class="card-body">
            <?php if ($pending_bookings->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العميل</th>
                                <th>الملعب</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>المبلغ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            while ($booking = $pending_bookings->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['customer_name']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['field_name']); ?></td>
                                    <td><?php echo format_arabic_date($booking['booking_date']); ?></td>
                                    <td>
                                        <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                    </td>
                                    <td><?php echo number_format($booking['total_price'], 0); ?> ج.س</td>
                                    <td class="actions">
                                        <a href="manage_booking.php?id=<?php echo $booking['id']; ?>&action=accept" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('هل تريد قبول الحجز؟')">
                                            <i class="fas fa-check"></i> قبول
                                        </a>
                                        <a href="manage_booking.php?id=<?php echo $booking['id']; ?>&action=reject" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('هل تريد رفض الحجز؟')">
                                            <i class="fas fa-times"></i> رفض
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="bookings.php?status=معلق" class="btn btn-outline-warning">
                        <i class="fas fa-list"></i> عرض جميع الحجوزات المعلقة
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h4>لا توجد حجوزات معلقة</h4>
                    <p>جميع الحجوزات تمت معالجتها</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php include '../includes/footer.php'; ?>
