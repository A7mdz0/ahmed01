<?php
/**
 * صفحة تسجيل الخروج
 */
session_start();

// تدمير الجلسة
session_unset();
session_destroy();

// إعادة التوجيه للصفحة الرئيسية
header("Location: ../index.php");
exit();
?>
