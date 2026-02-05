<?php
/**
 * Technical Associates API
 * Manage technical team and projects
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = $_GET['user_id'] ?? null;

    if ($userId) {
        // Get all projects for a specific user
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM projects 
                WHERE user_id = ? 
                ORDER BY start_date DESC
            ");
            $stmt->execute([$userId]);
            $projects = $stmt->fetchAll();
            echo json_encode($projects);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        // Get technical associates list with project count
        try {
            $stmt = $pdo->query("
                SELECT 
                    u.id, 
                    u.username, 
                    u.full_name, 
                    u.email, 
                    u.is_active,
                    (SELECT COUNT(*) FROM projects WHERE user_id = u.id) as project_count
                FROM users u
                WHERE u.role = 'technical'
                ORDER BY u.created_at DESC
            ");
            $associates = $stmt->fetchAll();
            echo json_encode($associates);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

} elseif ($method === 'POST') {
    // Assign Project / Create Project
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id']) || !isset($data['project_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID and Project Name are required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO projects (user_id, project_name, start_date, end_date, status, description)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['user_id'],
            $data['project_name'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['status'] ?? 'In Progress',
            $data['description'] ?? ''
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Project assigned successfully'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

} elseif ($method === 'PUT') {
    // Update Project
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['project_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Project ID is required']);
        exit;
    }

    // Build dynamic update query
    $fields = [];
    $params = [];

    if (isset($data['project_name'])) {
        $fields[] = "project_name = ?";
        $params[] = $data['project_name'];
    }
    // Ensure we can set dates to null if needed, but usually empty string from form might need handling
    if (array_key_exists('start_date', $data)) {
        $fields[] = "start_date = ?";
        $params[] = $data['start_date'] ?: null;
    }
    if (array_key_exists('end_date', $data)) {
        $fields[] = "end_date = ?";
        $params[] = $data['end_date'] ?: null;
    }
    if (isset($data['status'])) {
        $fields[] = "status = ?";
        $params[] = $data['status'];
    }
    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $params[] = $data['description'];
    }

    if (empty($fields)) {
        echo json_encode(['success' => true, 'message' => 'No changes provided']);
        exit;
    }

    // Add ID to params
    $params[] = $data['project_id'];

    try {
        $sql = "UPDATE projects SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Project updated successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($method === 'DELETE') {
    // Delete Project
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['project_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Project ID is required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$data['project_id']]);

        echo json_encode(['success' => true, 'message' => 'Project deleted successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>