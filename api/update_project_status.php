<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../auth_middleware.php';

$user = AuthMiddleware::requireAuthAPI();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$project_id = $input['project_id'] ?? 0;
$status = $input['status'] ?? '';

// Validate inputs
if (!$project_id || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate status value
$valid_statuses = ['Pending', 'In Progress', 'Completed', 'On Hold'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status value']);
    exit;
}

try {
    // Check if project exists and user has access
    $stmt = $pdo->prepare("SELECT user_id FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        http_response_code(404);
        echo json_encode(['error' => 'Project not found']);
        exit;
    }

    // Technical users can only update their own projects
    // Admins can update any project
    if ($user['role'] === 'technical' && $project['user_id'] != $user['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized - You can only update your own projects']);
        exit;
    }

    // Update project status
    $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->execute([$status, $project_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Project status updated successfully',
        'status' => $status
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>