<?php
/**
 * Migration Script: Add 2FA columns to users table
 */

require_once __DIR__ . '/config.php';

echo "=== Migrating Database: Add 2FA Columns ===\n\n";

try {
    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'two_fa_secret'");
    $hasSecret = $stmt->fetch();

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'two_fa_enabled'");
    $hasEnabled = $stmt->fetch();

    if (!$hasSecret) {
        echo "Adding 'two_fa_secret' column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN two_fa_secret VARCHAR(32) NULL AFTER is_active");
        echo "✓ Added 'two_fa_secret'\n";
    } else {
        echo "- 'two_fa_secret' column already exists\n";
    }

    if (!$hasEnabled) {
        echo "Adding 'two_fa_enabled' column...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN two_fa_enabled BOOLEAN DEFAULT FALSE AFTER two_fa_secret");
        echo "✓ Added 'two_fa_enabled'\n";
    } else {
        echo "- 'two_fa_enabled' column already exists\n";
    }

    echo "\n✓ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>