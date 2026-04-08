<?php
/**
 * إجراءات المدير - دار السودان
 * api/admin_actions.php
 */

require_once '../config/database.php';

checkPermission('admin');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action']) || !isset($data['property_id'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير كاملة']);
    exit;
}

$action = $data['action'];
$property_id = intval($data['property_id']);

try {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE properties SET status = 'active', is_verified = 1, verification_date = NOW() WHERE property_id = ?");
        $stmt->execute([$property_id]);
        logActivity($pdo, $_SESSION['user_id'], 'approve_property', "موافقة على العقار #$property_id");
        echo json_encode(['success' => true, 'message' => 'تم الموافقة']);
        
    } elseif ($action === 'reject') {
        $reason = $data['reason'] ?? 'غير محدد';
        $stmt = $pdo->prepare("UPDATE properties SET status = 'rejected' WHERE property_id = ?");
        $stmt->execute([$property_id]);
        logActivity($pdo, $_SESSION['user_id'], 'reject_property', "رفض العقار #$property_id: $reason");
        echo json_encode(['success' => true, 'message' => 'تم الرفض']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
    }
} catch (Exception $e) {
    error_log("Admin Action Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ']);
}
?>