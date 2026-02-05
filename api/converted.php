<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../auth_middleware.php';

// Require authentication
$user = AuthMiddleware::requireAuthAPI();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch converted leads
    try {
        // Admins see all converted leads, associates see only their own
        if ($user['role'] === 'admin') {
            $stmt = $pdo->query("
                SELECT cl.*, u.full_name as associate_name, u.username as associate_username 
                FROM converted_leads cl 
                LEFT JOIN users u ON cl.user_id = u.id 
                ORDER BY cl.converted_at DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM converted_leads 
                WHERE user_id = :user_id 
                ORDER BY converted_at DESC
            ");
            $stmt->execute(['user_id' => $user['user_id']]);
        }

        $data = $stmt->fetchAll();
        echo json_encode($data);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($method === 'PUT') {
    // Update converted lead (payment status)
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Converted Lead ID is required']);
        exit;
    }

    try {
        // Check if lead belongs to user
        $stmt = $pdo->prepare("SELECT * FROM converted_leads WHERE id = ?");
        $stmt->execute([$data['id']]);
        $lead = $stmt->fetch();

        if (!$lead) {
            throw new Exception("Lead not found");
        }

        if ($user['role'] !== 'admin' && $lead['user_id'] != $user['user_id']) {
            throw new Exception("Unauthorized");
        }

        // Update payment status
        $stmt = $pdo->prepare("UPDATE converted_leads SET payment_status = ? WHERE id = ?");
        $stmt->execute([
            $data['payment_status'] ?? $lead['payment_status'],
            $data['id']
        ]);

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>