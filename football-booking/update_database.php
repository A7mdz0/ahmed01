<?php
/**
 * صفحة فحص وتحديث قاعدة البيانات
 * قم بزيارة هذه الصفحة مرة واحدة لإضافة حقول الدفع
 */
require_once 'includes/db.php';

echo "<h2>فحص وتحديث قاعدة البيانات</h2>";

// التحقق من وجود حقول الدفع
$check_sql = "SHOW COLUMNS FROM bookings LIKE 'payment_method'";
$result = $conn->query($check_sql);

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ حقول الدفع موجودة بالفعل!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ حقول الدفع غير موجودة. جاري الإضافة...</p>";
    
    // إضافة الحقول
    $updates = [
        "ALTER TABLE bookings ADD COLUMN payment_method ENUM('cash', 'card') DEFAULT 'cash' AFTER status",
        "ALTER TABLE bookings ADD COLUMN payment_status ENUM('معلق', 'مدفوع', 'مرفوض') DEFAULT 'معلق' AFTER payment_method",
        "ALTER TABLE bookings ADD COLUMN card_number VARCHAR(20) AFTER payment_status",
        "ALTER TABLE bookings ADD COLUMN card_holder VARCHAR(100) AFTER card_number"
    ];
    
    $success = true;
    foreach ($updates as $sql) {
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color: green;'>✓ تم تنفيذ: " . htmlspecialchars(substr($sql, 0, 50)) . "...</p>";
        } else {
            // تجاهل خطأ "العمود موجود مسبقاً"
            if (strpos($conn->error, 'Duplicate column name') === false) {
                echo "<p style='color: red;'>✗ خطأ: " . $conn->error . "</p>";
                $success = false;
            } else {
                echo "<p style='color: blue;'>ℹ️ العمود موجود مسبقاً</p>";
            }
        }
    }
    
    if ($success) {
        echo "<h3 style='color: green;'>✅ تم التحديث بنجاح!</h3>";
        echo "<p>يمكنك الآن حذف هذا الملف (update_database.php) من المشروع.</p>";
    }
}

// عرض هيكل الجدول الحالي
echo "<hr>";
echo "<h3>هيكل جدول الحجوزات الحالي:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>اسم الحقل</th><th>النوع</th><th>قيمة افتراضية</th></tr>";

$columns_sql = "SHOW COLUMNS FROM bookings";
$columns_result = $conn->query($columns_sql);

while ($col = $columns_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>الخطوات التالية:</h3>";
echo "<ol>";
echo "<li>✅ قاعدة البيانات جاهزة</li>";
echo "<li>اذهب إلى <a href='index.php'>الصفحة الرئيسية</a></li>";
echo "<li>سجل دخول وجرب الحجز</li>";
echo "<li><strong>احذف هذا الملف بعد الانتهاء!</strong></li>";
echo "</ol>";
?>
