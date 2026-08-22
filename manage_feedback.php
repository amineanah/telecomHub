<?php
/**
 * POST /manage_feedback.php
 * Body: { "id": 1, "action": "approved|rejected|deleted" }
 * Manages feedback status (approve, reject, or delete)
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$id = intval($data['id'] ?? 0);
$action = trim($data['action'] ?? '');

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid feedback ID']);
    exit;
}

try {
    if ($action === 'deleted') {
        // Delete feedback
        $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $message = 'Feedback deleted successfully';
    } elseif (in_array($action, ['approved', 'rejected'])) {
        // Update status
        $stmt = $pdo->prepare("
            UPDATE feedback 
            SET status = :status, updated_at = NOW() 
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $action,
            ':id' => $id
        ]);
        $message = 'Feedback ' . $action . ' successfully';
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => $message
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update feedback']);
    error_log("Manage feedback error: " . $e->getMessage());
}
?>
