<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!csrf_validate(is_string($csrfHeader) ? $csrfHeader : '')) {
    http_response_code(419);
    echo json_encode(['success' => false, 'error' => 'CSRF validation failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email']);
    exit;
}

// Check if pending registration
$conn = db();
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND email_verified = 0 AND verification_code IS NOT NULL LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => true, 'message' => 'If the account is pending verification, a new code will be sent.']);
    exit;
}

// Generate new code and resend
if (resend_verification_email($email)) {
    echo json_encode(['success' => true, 'message' => 'Code resent successfully']);
} else {
    echo json_encode(['success' => true, 'message' => 'If the account is pending verification, a new code will be sent.']);
}
?>

