<?php
/**
 * Update project_sheets table to include user_id
 * This allows each assigned user to have their own spreadsheet data for a project
 */

require_once __DIR__ . '/config.php';

try {
    // First, drop the old unique constraint
    echo "Updating project_sheets table schema...\n";

    // Check if user_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM project_sheets LIKE 'user_id'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        // Drop the old unique constraint
        try {
            $pdo->exec("ALTER TABLE project_sheets DROP INDEX unique_project");
            echo "✓ Dropped old unique constraint\n";
        } catch (PDOException $e) {
            // Constraint might not exist, that's okay
            echo "  (No old constraint to drop)\n";
        }

        // Add user_id column
        $pdo->exec("
            ALTER TABLE project_sheets 
            ADD COLUMN user_id INT NOT NULL AFTER project_id,
            ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ");
        echo "✓ Added user_id column\n";

        // Add new composite unique constraint
        $pdo->exec("
            ALTER TABLE project_sheets 
            ADD UNIQUE KEY unique_project_user (project_id, user_id)
        ");
        echo "✓ Added composite unique constraint (project_id, user_id)\n";

        echo "\n✅ Migration completed successfully!\n";
        echo "\nNow each user will have their own spreadsheet data for each project.\n";
    } else {
        echo "✓ Table already has user_id column. No migration needed.\n";
    }

} catch (PDOException $e) {
    echo "✗ Error updating table: " . $e->getMessage() . "\n";
    exit(1);
}
?>