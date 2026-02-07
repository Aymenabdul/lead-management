<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../auth_middleware.php';

$user = AuthMiddleware::requireAuthAPI();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $lead_id = $_GET['lead_id'] ?? 0;
    $project_id = $_GET['project_id'] ?? 0;
    $is_project = $project_id > 0;

    if ($is_project) {
        // Handle project sheet - fetch by project_id AND assignee_id (current user)
        $stmt = $pdo->prepare("SELECT user_id FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
            exit;
        }

        // Check permission - technical users can only access their own projects
        if ($user['role'] === 'technical' && (int) $project['user_id'] != (int) $user['user_id']) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Unauthorized - You can only access your own projects',
                'debug' => "ProjectOwner: {$project['user_id']}, CurrentUser: {$user['user_id']}"
            ]);
            exit;
        }

        // Determine target assignee for GET request
        // If query param provided, use it (assumes Admin/Owner viewing others)
        // Otherwise default to current user
        $target_assignee = $_GET['assignee_id'] ?? $user['user_id'];

        if (empty($target_assignee)) {
            $target_assignee = $user['user_id'];
        }

        // Fetch project sheet data by project_id AND assignee_id
        // Fetch project sheet data by project_id AND assignee_id
        // Actually, we want to fetch the LATEST data regardless of assignee query param if we are implementing "Transfer" model fully
        // But for now keeping it aligned with requested "ownership" logic - fetch ANY existing record to see who owns it

        $stmt = $pdo->prepare("SELECT data, assignee_id FROM lead_requirements WHERE converted_lead_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$project_id]);
        $row = $stmt->fetch();

        echo json_encode([
            'data' => $row ? json_decode($row['data']) : null,
            'assignee' => $row ? $row['assignee_id'] : null
        ]);

    } else {
        // Handle converted lead sheet (original functionality)
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

        // Determine target assignee
        $target_assignee = $_GET['assignee_id'] ?? null;

        // Fetch lead sheet data - find ANY existing record to check ownership
        $stmt = $pdo->prepare("SELECT data, assignee_id FROM lead_requirements WHERE converted_lead_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$lead_id]);
        $row = $stmt->fetch();

        echo json_encode([
            'data' => $row ? json_decode($row['data']) : null,
            'assignee' => $row ? $row['assignee_id'] : null
        ]);
    }

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $lead_id = $input['lead_id'] ?? 0;
    $project_id = $input['project_id'] ?? 0;
    $is_project = $project_id > 0;
    $data = $input['data'] ?? []; // The cell data object

    if ($is_project) {
        // Handle project sheet save
        $stmt = $pdo->prepare("SELECT user_id FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();

        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found']);
            exit;
        }

        // Check permission
        if ($user['role'] === 'technical' && $project['user_id'] != $user['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        // Determine target assignee (if passed and valid, otherwise current user)
        $target_assignee = $input['assignee_id'] ?? $user['user_id'];

        // If empty string passed, fallback to current user
        if (empty($target_assignee)) {
            $target_assignee = $user['user_id'];
        }

        // Check if ANY entry exists for this project_id (regardless of assignee)
        // This implements "Transfer Ownership" logic
        $stmt = $pdo->prepare("SELECT id FROM lead_requirements WHERE converted_lead_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$project_id]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Update existing record (Transfer ownership to new assignee)
            $stmt = $pdo->prepare("UPDATE lead_requirements SET data = ?, assignee_id = ? WHERE id = ?");
            $stmt->execute([json_encode($data), $target_assignee, $exists['id']]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO lead_requirements (converted_lead_id, assignee_id, data) VALUES (?, ?, ?)");
            $stmt->execute([$project_id, $target_assignee, json_encode($data)]);
        }

        echo json_encode(['success' => true]);

    } else {
        // Handle converted lead sheet save (original functionality)
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

        // Determine target assignee
        $target_assignee = $input['assignee_id'] ?? null;

        // Check if ANY entry exists for this lead_id
        $stmt = $pdo->prepare("SELECT id FROM lead_requirements WHERE converted_lead_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$lead_id]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Update existing record (Transfer)
            $stmt = $pdo->prepare("UPDATE lead_requirements SET data = ?, assignee_id = ? WHERE id = ?");
            $stmt->execute([json_encode($data), $target_assignee, $exists['id']]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO lead_requirements (converted_lead_id, assignee_id, data) VALUES (?, ?, ?)");
            $stmt->execute([$lead_id, $target_assignee, json_encode($data)]);
        }

        echo json_encode(['success' => true]);
    }
}
?>