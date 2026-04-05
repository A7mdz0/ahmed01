<?php
/**
 * لوحة تحكم العميل
 */
session_start();
require_once '../auth/check_auth.php';
require_user_type('customer');

require_once '../includes/db.php';
require_once '../includes/functions.php';

$customer_id = $_SESSION['user_id'];

// إحصائيات العميل
$stats = [];

// عدد الحجوزات المعلقة
$sql = "SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? AND status = 'معلق'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['pending'] = $result->fetch_assoc()['count'];

// عدد الحجوزات المؤكدة
$sql = "SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? AND status = 'مؤكد'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['confirmed'] = $result->fetch_assoc()['count'];

// عدد الحجوزات المكتملة
$sql = "SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? AND status = 'مكتمل'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['completed'] = $result->fetch_assoc()['count'];

// إجمالي المبلغ المدفوع
$sql = "SELECT SUM(total_price) as total FROM bookings 
        WHERE customer_id = ? AND status IN ('مؤكد', 'مكتمل')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_spent'] = $result->fetch_assoc()['total'] ?? 0;

// آخر الحجوزات
$recent_sql = "SELECT b.*, f.field_name, f.city, f.image_path 
               FROM bookings b 
               INNER JOIN fields f ON b.field_id = f.id 
               WHERE b.customer_id = ? 
               ORDER BY b.created_at DESC 
               LIMIT 5";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("i", $customer_id);
$recent_stmt->execute();
$recent_bookings = $recent_stmt->get_result();

$page_title = 'لوحة تحكم العميل';
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
        <p class="text-muted mb-0">لوحة التحكم الخاصة بك</p>
    </div>
    
    <!-- الإحصائيات -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="dashboard-card warning">
                <h3><?php echo $stats['pending']; ?></h3>
                <p><i class="fas fa-clock"></i> حجوزات معلقة</p>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="dashboard-card success">
                <h3><?php echo $stats['confirmed']; ?></h3>
                <p><i class="fas fa-check-circle"></i> حجوزات مؤكدة</p>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="dashboard-card info">
                <h3><?php echo $stats['completed']; ?></h3>
                <p><i class="fas fa-flag-checkered"></i> حجوزات مكتملة</p>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="dashboard-card">
                <h3><?php echo number_format($stats['total_spent'], 0); ?></h3>
                <p><i class="fas fa-money-bill-wave"></i> إجمالي الإنفاق (ج.س)</p>
            </div>
        </div>
    </div>
    
    <!-- روابط سريعة -->
    <div class="row mb-4">
        <div class="col-md-4">
            <a href="/football-booking/search.php" class="btn btn-success btn-block btn-lg">
                <i class="fas fa-search"></i> ابحث عن ملعب
            </a>
        </div>
        <div class="col-md-4">
            <a href="my_bookings.php" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-list"></i> جميع حجوزاتي
            </a>
        </div>
        <div class="col-md-4">
            <a href="/football-booking/index.php" class="btn btn-info btn-block btn-lg">
                <i class="fas fa-home"></i> الصفحة الرئيسية
            </a>
        </div>
    </div>
    
    <!-- آخر الحجوزات -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0"><i class="fas fa-history"></i> آخر الحجوزات</h4>
        </div>
        <div class="card-body">
            <?php if ($recent_bookings->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الملعب</th>
                                <th>التاريخ</th>
                                <th>الوقت</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            while ($booking = $recent_bookings->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['field_name']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['city']); ?>
                                        </small>
                                    </td>
                                    <td><?php echo format_arabic_date($booking['booking_date']); ?></td>
                                    <td>
                                        <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                    </td>
                                    <td><?php echo number_format($booking['total_price'], 0); ?> ج.س</td>
                                    <td><?php echo get_status_badge($booking['status']); ?></td>
                                    <td>
                                        <?php if ($booking['status'] === 'معلق' || $booking['status'] === 'مؤكد'): ?>
                                            <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('هل أنت متأكد من إلغاء الحجز؟')">
                                                <i class="fas fa-times"></i> إلغاء
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="text-center mt-3">
                    <a href="my_bookings.php" class="btn btn-outline-success">
                        <i class="fas fa-list"></i> عرض جميع الحجوزات
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>لا توجد حجوزات بعد</h4>
                    <p>ابدأ بحجز ملعبك الأول!</p>
                    <a href="/football-booking/search.php" class="btn btn-success btn-lg">
                        <i class="fas fa-search"></i> ابحث عن ملعب
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
</div>

<?php include '../includes/footer.php'; ?>
