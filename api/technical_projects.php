<?php
/**
 * Technical Projects API
 * For technical employees to view their assigned projects
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_middleware.php';

// Require technical authentication
$user = AuthMiddleware::requireAuthAPI();

if ($user['role'] !== 'technical') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Technical employees only.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all projects for the logged-in technical user
    try {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                project_name,
                start_date,
                end_date,
                status,
                description,
                created_at
            FROM projects 
            WHERE user_id = ? 
            ORDER BY 
                CASE 
                    WHEN status = 'In Progress' THEN 1
                    WHEN status = 'Pending' THEN 2
                    WHEN status = 'On Hold' THEN 3
                    WHEN status = 'Completed' THEN 4
                    ELSE 5
                END,
                end_date ASC
        ");
        $stmt->execute([$user['user_id']]);
        $projects = $stmt->fetchAll();
        echo json_encode($projects);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>