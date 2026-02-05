<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../auth_middleware.php';

// Require authentication
$user = AuthMiddleware::requireAuthAPI();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch leads
    try {
        // Admins see all leads, associates see only their own
        if ($user['role'] === 'admin') {
            $stmt = $pdo->query("
                SELECT l.*, u.full_name as associate_name, u.username as associate_username 
                FROM leads l 
                LEFT JOIN users u ON l.user_id = u.id 
                ORDER BY l.created_at DESC
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT * FROM leads 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC
            ");
            $stmt->execute(['user_id' => $user['user_id']]);
        }

        $leads = $stmt->fetchAll();
        echo json_encode($leads);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    // Create new lead
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        $data = $_POST; // Fallback for form data
    }

    if (!isset($data['name']) || !isset($data['phone'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and Phone are required']);
        exit;
    }

    try {
        // Automatically assign the lead to the logged-in user
        $stmt = $pdo->prepare("
            INSERT INTO leads (user_id, name, phone, email, platform, service, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['user_id'], // Assign to current user
            $data['name'],
            $data['phone'],
            $data['email'] ?? '',
            $data['platform'] ?? 'Direct',
            $data['service'] ?? 'Web Development',
            $data['status'] ?? 'New'
        ]);

        $newLeadId = $pdo->lastInsertId();

        // If status is Converted, also add to converted_leads table
        if (($data['status'] ?? 'New') === 'Converted') {
            $stmtConvert = $pdo->prepare("
                INSERT INTO converted_leads 
                (original_lead_id, user_id, name, phone, email, platform, service, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $stmtConvert->execute([
                $newLeadId,
                $user['user_id'],
                $data['name'],
                $data['phone'],
                $data['email'] ?? '',
                $data['platform'] ?? 'Direct',
                $data['service'] ?? 'Web Development'
            ]);
        }

        echo json_encode(['success' => true, 'id' => $newLeadId]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($method === 'PUT') {
    // Update lead (status)
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Lead ID is required']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Check if lead exists and belongs to user
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$data['id']]);
        $lead = $stmt->fetch();

        if (!$lead) {
            throw new Exception("Lead not found");
        }

        if ($user['role'] !== 'admin' && $lead['user_id'] != $user['user_id']) {
            throw new Exception("Unauthorized");
        }

        $newStatus = $data['status'] ?? $lead['status'];

        // Update lead status
        $stmt = $pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $data['id']]);

        // If status changed to Converted, add to converted_leads
        if ($newStatus === 'Converted' && $lead['status'] !== 'Converted') {
            // Check if already in converted_leads to avoid duplicates (though business logic might allow re-conversion, let's assume one-time)
            $stmtCheck = $pdo->prepare("SELECT id FROM converted_leads WHERE original_lead_id = ?");
            $stmtCheck->execute([$data['id']]);

            if (!$stmtCheck->fetch()) {
                $stmtConvert = $pdo->prepare("
                    INSERT INTO converted_leads 
                    (original_lead_id, user_id, name, phone, email, platform, service, payment_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmtConvert->execute([
                    $lead['id'],
                    $user['user_id'], // Keep original owner
                    $lead['name'],
                    $lead['phone'],
                    $lead['email'],
                    $lead['platform'],
                    $lead['service'],
                    $data['payment_status'] ?? 'Pending'
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>