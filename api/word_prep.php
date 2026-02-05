<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../auth_middleware.php';

$user = AuthMiddleware::requireAuthAPI();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $lead_id = $_GET['lead_id'] ?? 0;

    // Check permission
    $stmt = $pdo->prepare("SELECT user_id FROM converted_leads WHERE id = ?");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch();

    if (!$lead) {
        http_response_code(404);
        echo json_encode(['error' => 'Lead not found']);
        exit;
    }

    if ($user['role'] !== 'admin' && $lead['user_id'] != $user['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT document_data FROM lead_requirements WHERE converted_lead_id = ?");
    $stmt->execute([$lead_id]);
    $row = $stmt->fetch();

    echo json_encode(['data' => $row ? json_decode($row['document_data']) : null]);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $lead_id = $input['lead_id'] ?? 0;
    $data = $input['data'] ?? []; // The document data

    // Check permission
    $stmt = $pdo->prepare("SELECT user_id FROM converted_leads WHERE id = ?");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch();

    if (!$lead) {
        http_response_code(404);
        echo json_encode(['error' => 'Lead not found']);
        exit;
    }

    if ($user['role'] !== 'admin' && $lead['user_id'] != $user['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Check if entry exists
    $stmt = $pdo->prepare("SELECT id FROM lead_requirements WHERE converted_lead_id = ?");
    $stmt->execute([$lead_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE lead_requirements SET document_data = ? WHERE converted_lead_id = ?");
        $stmt->execute([json_encode($data), $lead_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO lead_requirements (converted_lead_id, document_data) VALUES (?, ?)");
        $stmt->execute([$lead_id, json_encode($data)]);
    }

    echo json_encode(['success' => true]);
}
?>