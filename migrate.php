<?php
// migrate.php
require_once __DIR__ . '/config.php';

try {
    echo "Migrating database...\n";

    // Add service column to leads if missing
    try {
        $pdo->exec("ALTER TABLE leads ADD COLUMN service TEXT");
        echo "Added 'service' column to 'leads' table.\n";
    } catch (Exception $e) {
        // Column might already exist, ignore
        echo "Leads table might already have service column: " . $e->getMessage() . "\n";
    }

    // Add service column to converted_leads if missing
    try {
        $pdo->exec("ALTER TABLE converted_leads ADD COLUMN service TEXT");
        echo "Added 'service' column to 'converted_leads' table.\n";
    } catch (Exception $e) {
        // Column might already exist, ignore
        echo "Converted_leads table might already have service column: " . $e->getMessage() . "\n";
    }

    echo "Migration complete.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>