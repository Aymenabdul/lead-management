<?php
/**
 * Associates API
 * Manage associate users (admin only)
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
    // Get associates with optional role filter
    $roleFilter = $_GET['role'] ?? null;
    $sql = "SELECT id, username, email, full_name, role, is_active, created_at, last_login FROM users WHERE role != 'admin'";

    if ($roleFilter) {
        $sql .= " AND role = " . $pdo->quote($roleFilter);
    }

    $sql .= " ORDER BY created_at DESC";

    try {
        $stmt = $pdo->query($sql);
        $associates = $stmt->fetchAll();
        echo json_encode($associates);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

} elseif ($method === 'POST') {
    // Create new associate
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username, email, and password are required']);
        exit;
    }

    // Validate password length
    if (strlen($data['password']) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters']);
        exit;
    }

    $role = isset($data['role']) && in_array($data['role'], ['user', 'technical']) ? $data['role'] : 'user';

    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email']]);

        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Username or email already exists']);
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        // Insert new associate
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, role, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['full_name'] ?? null,
            $role
        ]);

        echo json_encode([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'message' => 'Associate created successfully'
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

} elseif ($method === 'PUT') {
    // Update associate (toggle status or change password)
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['action']) || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Action and user_id are required']);
        exit;
    }

    try {
        if ($data['action'] === 'toggle_status') {
            // Toggle active/inactive status
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ? AND role != 'admin'");
            $stmt->execute([$data['is_active'] ? 1 : 0, $data['user_id']]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Status updated successfully'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Associate not found or cannot modify admin']);
            }

        } elseif ($data['action'] === 'change_password') {
            // Change password
            if (!isset($data['new_password']) || strlen($data['new_password']) < 6) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 6 characters']);
                exit;
            }

            $hashedPassword = password_hash($data['new_password'], PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role != 'admin'");
            $stmt->execute([$hashedPassword, $data['user_id']]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Associate not found or cannot modify admin']);
            }

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }

} elseif ($method === 'DELETE') {
    // Delete associate
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'user_id is required']);
        exit;
    }

    try {
        // Prevent deleting admin users
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$data['user_id']]);
        $userToDelete = $stmt->fetch();

        if (!$userToDelete) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        if ($userToDelete['role'] === 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot delete admin users']);
            exit;
        }

        // Delete the user (CASCADE will delete their leads and conversions)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$data['user_id']]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Associate deleted successfully'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Associate not found']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>