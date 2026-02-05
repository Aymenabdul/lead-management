<?php
/**
 * Create project_sheets table
 * This table stores spreadsheet data for projects (similar to lead_requirements for leads)
 */

require_once __DIR__ . '/config.php';

try {
    // Create project_sheets table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS project_sheets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT NOT NULL,
            data LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            UNIQUE KEY unique_project (project_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "✓ project_sheets table created successfully or already exists.\n";
    echo "\nThis table will store spreadsheet data for projects.\n";
    echo "Each project can have one spreadsheet with multiple sheets.\n";

} catch (PDOException $e) {
    echo "✗ Error creating table: " . $e->getMessage() . "\n";
    exit(1);
}
?>