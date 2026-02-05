<?php
/**
 * Setup 2FA API Endpoint
 * Generates a secret and QR code for the user
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

try {
    $gauth = new GoogleAuthenticator();

    // Fetch current user secret
    $stmt = $pdo->prepare("SELECT two_fa_secret, two_fa_enabled FROM users WHERE id = :id");
    $stmt->execute(['id' => $user['user_id']]);
    $currentUser = $stmt->fetch();

    $secret = $currentUser['two_fa_secret'];

    // If no secret exists, generate one
    if (!$secret) {
        $secret = $gauth->createSecret();
        // Save new secret
        $updateStmt = $pdo->prepare("UPDATE users SET two_fa_secret = :secret WHERE id = :id");
        $updateStmt->execute(['secret' => $secret, 'id' => $user['user_id']]);
    }

    // Generate QR Code URL
    $otpAuthUrl = 'otpauth://totp/TurtleDot:' . $user['username'] . '?secret=' . $secret . '&issuer=Turtle Dot';

    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'otpauth_url' => $otpAuthUrl,
        'enabled' => (bool) $currentUser['two_fa_enabled']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>