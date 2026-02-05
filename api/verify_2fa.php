<?php
/**
 * Verify 2FA API Endpoint
 * Verifies a code and enables 2FA for the user
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';
require_once __DIR__ . '/../auth_middleware.php';
require_once __DIR__ . '/../lib/GoogleAuthenticator.php';

// Auth check
$user = AuthMiddleware::requireAuthAPI();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Code is required']);
    exit;
}

$code = trim((string) $input['code']);
if (strlen($code) !== 6 || !ctype_digit($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid code format. Must be 6 digits.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT two_fa_secret FROM users WHERE id = :id");
    $stmt->execute(['id' => $user['user_id']]);
    $currentUser = $stmt->fetch();

    if (!$currentUser || !$currentUser['two_fa_secret']) {
        http_response_code(400);
        echo json_encode(['error' => '2FA not initiated. Please setup 2FA first.']);
        exit;
    }

    $gauth = new GoogleAuthenticator();
    $checkResult = $gauth->verifyCode($currentUser['two_fa_secret'], $code);

    if ($checkResult) {
        // Enable 2FA
        $updateStmt = $pdo->prepare("UPDATE users SET two_fa_enabled = 1 WHERE id = :id");
        $updateStmt->execute(['id' => $user['user_id']]);

        echo json_encode(['success' => true, 'message' => '2FA Enabled Successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid code. Please try again.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>