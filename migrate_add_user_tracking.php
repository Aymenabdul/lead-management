<?php
/**
 * Migration Script - Add user_id to leads tables
 * This updates the existing database to support associate tracking
 */

require_once __DIR__ . '/config.php';

echo "=== Database Migration: Add Associate Tracking ===\n\n";

try {
    // Check if user_id column already exists in leads table
    $stmt = $pdo->query("SHOW COLUMNS FROM leads LIKE 'user_id'");

    if (!$stmt->fetch()) {
        echo "Adding user_id column to leads table...\n";

        // Add user_id column (allow NULL temporarily for existing records)
        $pdo->exec("ALTER TABLE leads ADD COLUMN user_id INT NULL AFTER id");

        // Get the admin user ID (first user or admin role)
        $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $adminStmt->fetch();

        if ($admin) {
            $adminId = $admin['id'];
            // Update existing leads to belong to admin
            $pdo->exec("UPDATE leads SET user_id = $adminId WHERE user_id IS NULL");
            echo "✓ Assigned existing leads to admin user\n";
        }

        // Make user_id NOT NULL and add foreign key
        $pdo->exec("ALTER TABLE leads MODIFY user_id INT NOT NULL");
        $pdo->exec("ALTER TABLE leads ADD FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE");

        echo "✓ Added user_id to leads table\n";
    } else {
        echo "✓ user_id already exists in leads table\n";
    }

    // Check if user_id column exists in converted_leads table
    $stmt = $pdo->query("SHOW COLUMNS FROM converted_leads LIKE 'user_id'");

    if (!$stmt->fetch()) {
        echo "\nAdding user_id column to converted_leads table...\n";

        // Add user_id column
        $pdo->exec("ALTER TABLE converted_leads ADD COLUMN user_id INT NULL AFTER original_lead_id");

        // Get the admin user ID
        $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $adminStmt->fetch();

        if ($admin) {
            $adminId = $admin['id'];
            // Update existing converted leads to belong to admin
            $pdo->exec("UPDATE converted_leads SET user_id = $adminId WHERE user_id IS NULL");
            echo "✓ Assigned existing converted leads to admin user\n";
        }

        // Make user_id NOT NULL and add foreign key
        $pdo->exec("ALTER TABLE converted_leads MODIFY user_id INT NOT NULL");
        $pdo->exec("ALTER TABLE converted_leads ADD FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE");

        echo "✓ Added user_id to converted_leads table\n";
    } else {
        echo "✓ user_id already exists in converted_leads table\n";
    }

    echo "\n✓ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "✗ Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
?>