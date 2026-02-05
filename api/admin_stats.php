<?php
/**
 * Admin Statistics API
 * Returns comprehensive stats and associate performance data
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

try {
    // --- Stats Overview (Global) ---
    // Total users excluding admin
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin' AND is_active = 1");
    $totalAssociates = $stmt->fetch()['count'];

    // Total leads
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM leads");
    $totalLeads = $stmt->fetch()['count'];

    // Total converted leads
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM converted_leads");
    $totalConverted = $stmt->fetch()['count'];

    // Paid conversions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM converted_leads WHERE payment_status = 'Paid'");
    $totalPaid = $stmt->fetch()['count'];

    // Pending payments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM converted_leads WHERE payment_status = 'Pending'");
    $totalPending = $stmt->fetch()['count'];

    // Conversion rate
    $conversionRate = $totalLeads > 0 ? round(($totalConverted / $totalLeads) * 100, 1) : 0;

    // --- Marketing Associates Performance ---
    $marketingStmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.full_name,
            COUNT(DISTINCT l.id) as total_leads,
            COUNT(DISTINCT cl.id) as converted,
            SUM(CASE WHEN cl.payment_status = 'Paid' THEN 1 ELSE 0 END) as paid,
            SUM(CASE WHEN cl.payment_status = 'Pending' THEN 1 ELSE 0 END) as pending
        FROM users u
        LEFT JOIN leads l ON u.id = l.user_id
        LEFT JOIN converted_leads cl ON u.id = cl.user_id
        WHERE u.role = 'user' AND u.is_active = 1
        GROUP BY u.id, u.username, u.email, u.full_name
        ORDER BY total_leads DESC, converted DESC
    ");
    $marketingAssociates = $marketingStmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Technical Associates Performance ---
    // Note: projects table: id, user_id, project_name, status, ...
    $technicalStmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.full_name,
            COUNT(DISTINCT p.id) as total_projects,
            SUM(CASE WHEN p.status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN p.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN p.status = 'Pending' OR p.status = 'On Hold' THEN 1 ELSE 0 END) as pending
        FROM users u
        LEFT JOIN projects p ON u.id = p.user_id
        WHERE u.role = 'technical' AND u.is_active = 1
        GROUP BY u.id, u.username, u.email, u.full_name
        ORDER BY total_projects DESC
    ");
    $technicalAssociates = $technicalStmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode([
        'success' => true,
        'stats' => [
            'total_associates' => (int) $totalAssociates,
            'total_leads' => (int) $totalLeads,
            'total_converted' => (int) $totalConverted,
            'total_paid' => (int) $totalPaid,
            'total_pending' => (int) $totalPending,
            'conversion_rate' => $conversionRate
        ],
        'marketing_associates' => $marketingAssociates,
        'technical_associates' => $technicalAssociates
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>