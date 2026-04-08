<?php
/**
 * تسجيل الخروج - دار السودان
 * api/logout.php
 */

require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'logout', 'تسجيل خروج');
}

session_unset();
session_destroy();

header('Location: ../index.php');
exit;
?>