<?php
/**
 * Add assignee_id column to lead_requirements table
 * This allows filtering sheet data by assignee for projects
 */

require_once __DIR__ . '/config.php';

try {
    echo "Adding assignee_id column to lead_requirements table...\n";

    // Check if assignee_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM lead_requirements LIKE 'assignee_id'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Add assignee_id column (nullable for backward compatibility with leads)
        $pdo->exec("
            ALTER TABLE lead_requirements 
            ADD COLUMN assignee_id INT NULL AFTER converted_lead_id,
            ADD INDEX idx_assignee (assignee_id)
        ");
        echo "✓ Added assignee_id column\n";
        echo "✓ Added index on assignee_id\n";

        echo "\n✅ Migration completed successfully!\n";
        echo "\nNow each assignee can have their own spreadsheet data.\n";
    } else {
        echo "✓ Table already has assignee_id column. No migration needed.\n";
    }

} catch (PDOException $e) {
    echo "✗ Error updating table: " . $e->getMessage() . "\n";
    exit(1);
}
?>