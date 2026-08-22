<?php
/**
 * POST /api/subscribe.php
 * Body: { "email": "someone@example.com" }
 *
 * Adds an email to newsletter_subscribers, or re-activates it
 * if the person had previously unsubscribed.
 */

require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$data  = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide a valid email address.']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO newsletter_subscribers (email, is_active, subscribed_at)
    VALUES (:email, 1, NOW())
    ON DUPLICATE KEY UPDATE
        is_active = 1,
        unsubscribed_at = NULL
");

$stmt->execute([':email' => $email]);

echo json_encode(['success' => true, 'message' => "Thank you! {$email} has been added to the newsletter."]);
