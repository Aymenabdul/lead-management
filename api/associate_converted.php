<?php
/**
 * Associate Converted Leads API
 * Returns all converted leads for a specific associate (admin only)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_middleware.php';

// Require admin authentication
$user = AuthMiddleware::requireAuthAPI();

if ($user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin only.']);
    exit;
}

// Get user_id from query parameter
$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'user_id parameter is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, phone, email, platform, service, payment_status, converted_at 
        FROM converted_leads 
        WHERE user_id = :user_id 
        ORDER BY converted_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $converted = $stmt->fetchAll();

    echo json_encode($converted);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>