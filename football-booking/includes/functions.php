<?php
/**
 * ملف الدوال المساعدة
 * Helper Functions File
 */

/**
 * التحقق من تسجيل دخول المستخدم
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * التحقق من نوع المستخدم - عميل
 */
function is_customer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer';
}

/**
 * التحقق من نوع المستخدم - مالك ملعب
 */
function is_owner() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner';
}

/**
 * التحقق من نوع المستخدم - مدير
 */
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * إعادة التوجيه
 */
function redirect($page) {
    header("Location: " . $page);
    exit();
}

/**
 * التحقق من توفر الوقت للحجز
 */
function is_time_available($conn, $field_id, $booking_date, $start_time, $end_time, $exclude_booking_id = null) {
    $sql = "SELECT id FROM bookings 
            WHERE field_id = ? 
            AND booking_date = ? 
            AND status NOT IN ('ملغي', 'مرفوض')
            AND (
                (start_time < ? AND end_time > ?) OR
                (start_time < ? AND end_time > ?) OR
                (start_time >= ? AND end_time <= ?)
            )";
    
    if ($exclude_booking_id) {
        $sql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($exclude_booking_id) {
        $stmt->bind_param("isssssssi", $field_id, $booking_date, $end_time, $start_time, 
                         $end_time, $start_time, $start_time, $end_time, $exclude_booking_id);
    } else {
        $stmt->bind_param("isssssss", $field_id, $booking_date, $end_time, $start_time, 
                         $end_time, $start_time, $start_time, $end_time);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 0;
}

/**
 * حساب عدد الساعات بين وقتين
 */
function calculate_hours($start_time, $end_time) {
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    return $interval->h + ($interval->days * 24);
}

/**
 * تنسيق التاريخ بالعربي
 */
function format_arabic_date($date) {
    $timestamp = strtotime($date);
    $days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $months = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
               'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    
    $day_name = $days[date('w', $timestamp)];
    $day = date('d', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "{$day_name} {$day} {$month} {$year}";
}

/**
 * رفع صورة الملعب
 */
function upload_field_image($file) {
    $target_dir = "../assets/images/fields/";
    
    // إنشاء المجلد إذا لم يكن موجوداً
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // التحقق من نوع الملف
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح. يرجى رفع صورة فقط (JPG, PNG, GIF)'];
    }
    
    // التحقق من حجم الملف (5MB)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'حجم الصورة كبير جداً. الحد الأقصى 5MB'];
    }
    
    // التحقق من أن الملف صورة حقيقية
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'الملف ليس صورة'];
    }
    
    // رفع الملف
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'message' => 'فشل رفع الصورة'];
    }
}

/**
 * حذف صورة الملعب
 */
function delete_field_image($filename) {
    if (empty($filename)) return;
    
    $file_path = "../assets/images/fields/" . $filename;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

/**
 * الحصول على عدد الإشعارات للمستخدم
 */
function get_notifications_count($conn, $user_id, $user_type) {
    if ($user_type === 'customer') {
        // عدد الحجوزات المؤكدة أو المرفوضة حديثاً
        $sql = "SELECT COUNT(*) as count FROM bookings 
                WHERE customer_id = ? AND status IN ('مؤكد', 'مرفوض') 
                AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    } else if ($user_type === 'owner') {
        // عدد الحجوزات المعلقة على ملاعبه
        $sql = "SELECT COUNT(*) as count FROM bookings b
                INNER JOIN fields f ON b.field_id = f.id
                WHERE f.owner_id = ? AND b.status = 'معلق'";
    } else {
        return 0;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * الحصول على شارة الحالة
 */
function get_status_badge($status) {
    $badges = [
        'معلق' => 'badge-warning',
        'مؤكد' => 'badge-success',
        'مرفوض' => 'badge-danger',
        'ملغي' => 'badge-secondary',
        'مكتمل' => 'badge-info'
    ];
    
    $class = isset($badges[$status]) ? $badges[$status] : 'badge-secondary';
    return "<span class='badge {$class}'>{$status}</span>";
}

/**
 * التحقق من صلاحية التاريخ والوقت
 */
function validate_booking_time($booking_date, $start_time, $end_time) {
    $errors = [];
    
    // التحقق من أن التاريخ ليس في الماضي
    $today = date('Y-m-d');
    if ($booking_date < $today) {
        $errors[] = "لا يمكن الحجز في تاريخ ماضي";
    }
    
    // التحقق من أن وقت النهاية بعد وقت البداية
    if ($start_time >= $end_time) {
        $errors[] = "وقت النهاية يجب أن يكون بعد وقت البداية";
    }
    
    // التحقق من أن الحجز على الأقل ساعة واحدة
    $hours = calculate_hours($start_time, $end_time);
    if ($hours < 1) {
        $errors[] = "الحد الأدنى للحجز ساعة واحدة";
    }
    
    return $errors;
}
?>
