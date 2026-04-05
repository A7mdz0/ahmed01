<?php
/**
 * صفحة حجوزاتي - العميل
 */
session_start();
require_once '../auth/check_auth.php';
require_user_type('customer');

require_once '../includes/db.php';
require_once '../includes/functions.php';

$customer_id = $_SESSION['user_id'];

// جلب جميع الحجوزات
$sql = "SELECT b.*, f.field_name, f.city, f.image_path, f.address 
        FROM bookings b 
        INNER JOIN fields f ON b.field_id = f.id 
        WHERE b.customer_id = ? 
        ORDER BY b.booking_date DESC, b.start_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$bookings = $stmt->get_result();

$page_title = 'حجوزاتي';
$home_path = '/football-booking/index.php';
$search_path = '/football-booking/search.php';
$css_path = '/football-booking/assets/css/style.css';
?>

<?php include '../includes/header.php'; ?>

<div class="container my-5">
    
    <div class="page-header">
        <h2><i class="fas fa-calendar-check"></i> حجوزاتي</h2>
        <p class="text-muted mb-0">جميع حجوزاتك في مكان واحد</p>
    </div>
    
    <?php if (isset($_SESSION['booking_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    
    <!-- فلتر الحالة -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary active" onclick="filterBookings('all')">
                            الكل
                        </button>
                        <button type="button" class="btn btn-outline-warning" onclick="filterBookings('معلق')">
                            معلقة
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="filterBookings('مؤكد')">
                            مؤكدة
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="filterBookings('مرفوض')">
                            مرفوضة
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="filterBookings('ملغي')">
                            ملغية
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="filterBookings('مكتمل')">
                            مكتملة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- الحجوزات -->
    <div class="row" id="bookingsContainer">
        <?php if ($bookings->num_rows > 0): ?>
            <?php while ($booking = $bookings->fetch_assoc()): ?>
                <div class="col-md-6 mb-4 booking-item" data-status="<?php echo $booking['status']; ?>">
                    <div class="card h-100">
                        <?php if ($booking['image_path']): ?>
                            <img src="../assets/images/fields/<?php echo htmlspecialchars($booking['image_path']); ?>" 
                                 class="card-img-top" style="height: 150px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($booking['field_name']); ?>">
                        <?php else: ?>
                            <div style="height: 150px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-futbol fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($booking['field_name']); ?></h5>
                                <?php echo get_status_badge($booking['status']); ?>
                            </div>
                            
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($booking['city']); ?> - <?php echo htmlspecialchars($booking['address']); ?>
                            </p>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1">
                                        <i class="fas fa-calendar text-primary"></i> 
                                        <strong>التاريخ:</strong>
                                    </p>
                                    <p class="text-muted"><?php echo format_arabic_date($booking['booking_date']); ?></p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1">
                                        <i class="fas fa-clock text-warning"></i> 
                                        <strong>الوقت:</strong>
                                    </p>
                                    <p class="text-muted">
                                        <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <p class="mb-0">
                                        <i class="fas fa-hourglass-half text-info"></i> 
                                        <strong><?php echo $booking['total_hours']; ?> ساعة</strong>
                                    </p>
                                </div>
                                <div>
                                    <p class="mb-0 price-tag">
                                        <?php echo number_format($booking['total_price'], 0); ?> ج.س
                                    </p>
                                </div>
                            </div>
                            
                            <!-- معلومات الدفع -->
                            <hr>
                            <div class="payment-info">
                                <p class="mb-1">
                                    <i class="fas fa-money-bill-wave text-success"></i> 
                                    <strong>طريقة الدفع:</strong>
                                    <?php if ($booking['payment_method'] === 'cash'): ?>
                                        <span class="badge badge-success">نقداً</span>
                                    <?php else: ?>
                                        <span class="badge badge-primary">بطاقة ائتمانية</span>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($booking['payment_method'] === 'card' && !empty($booking['card_number'])): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-credit-card"></i> 
                                        <strong>البطاقة:</strong> <?php echo htmlspecialchars($booking['card_number']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-user"></i> 
                                        <strong>الحامل:</strong> <?php echo htmlspecialchars($booking['card_holder']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="mb-0">
                                    <i class="fas fa-check-circle"></i> 
                                    <strong>حالة الدفع:</strong>
                                    <?php 
                                    $payment_status = $booking['payment_status'] ?? 'معلق';
                                    if ($payment_status === 'مدفوع'): ?>
                                        <span class="badge badge-success">مدفوع</span>
                                    <?php elseif ($payment_status === 'مرفوض'): ?>
                                        <span class="badge badge-danger">مرفوض</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">معلق</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <?php if (!empty($booking['customer_notes'])): ?>
                                <hr>
                                <p class="mb-0">
                                    <strong>ملاحظاتك:</strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($booking['customer_notes']); ?></small>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($booking['owner_notes'])): ?>
                                <hr>
                                <div class="alert alert-info mb-0">
                                    <strong><i class="fas fa-comment"></i> رد المالك:</strong><br>
                                    <?php echo htmlspecialchars($booking['owner_notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> 
                                    تم الحجز: <?php echo date('Y-m-d', strtotime($booking['created_at'])); ?>
                                </small>
                                
                                <?php if ($booking['status'] === 'معلق' || $booking['status'] === 'مؤكد'): ?>
                                    <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('هل أنت متأكد من إلغاء الحجز؟')">
                                        <i class="fas fa-times"></i> إلغاء الحجز
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h4>لا توجد حجوزات</h4>
                    <p>لم تقم بأي حجز بعد</p>
                    <a href="/football-booking/search.php" class="btn btn-success btn-lg">
                        <i class="fas fa-search"></i> ابحث عن ملعب
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<script>
function filterBookings(status) {
    const bookings = document.querySelectorAll('.booking-item');
    const buttons = document.querySelectorAll('.btn-group button');
    
    // تحديث الأزرار
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // فلترة الحجوزات
    bookings.forEach(booking => {
        if (status === 'all' || booking.dataset.status === status) {
            booking.style.display = 'block';
        } else {
            booking.style.display = 'none';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
