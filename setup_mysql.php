<?php
/**
 * MySQL Database Setup Script
 * Run this script once to create the database and tables
 */

// Database credentials
$db_host = 'localhost';
$db_name = 'lead_maintenance';
$db_user = 'root';
$db_pass = 'Parkour@123';
$db_charset = 'utf8mb4';

echo "=== Lead Maintenance MySQL Setup ===\n\n";

try {
    // Connect to MySQL server (without database)
    echo "Connecting to MySQL server...\n";
    $pdo = new PDO("mysql:host=$db_host;charset=$db_charset", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to MySQL server\n\n";

    // Create database
    echo "Creating database '$db_name'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database created/verified\n\n";

    // Use the database
    $pdo->exec("USE `$db_name`");

    // Read and execute schema
    echo "Creating tables...\n";
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    $pdo->exec($schema);
    echo "✓ Tables created successfully\n\n";

    // Verify tables
    echo "Verifying tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    echo "\n✓ Setup completed successfully!\n";
    echo "\nYou can now access your application at: http://localhost:8000\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>