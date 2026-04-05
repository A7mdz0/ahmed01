<?php
session_start();
require_once '../auth/check_auth.php';
require_user_type('owner');
require_once '../includes/db.php';

if (!isset($_GET['id'])) redirect('my_fields.php');

$field_id = intval($_GET['id']);
$owner_id = $_SESSION['user_id'];

$sql = "DELETE FROM fields WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $field_id, $owner_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'تم حذف الملعب بنجاح';
}

redirect('my_fields.php');
?>
