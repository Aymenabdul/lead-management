<?php
/**
 * Logout API Endpoint
 * Clears authentication token
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth_middleware.php';

// Clear auth cookie
AuthMiddleware::clearAuthCookie();

echo json_encode([
    'success' => true,
    'message' => 'Logged out successfully'
]);
?>