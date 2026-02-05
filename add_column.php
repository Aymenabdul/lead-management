<?php
require_once __DIR__ . '/config.php';

try {
    $pdo->exec("ALTER TABLE lead_requirements ADD COLUMN document_data LONGTEXT");
    echo "Column document_data added successfully";
} catch (PDOException $e) {
    echo "Error (column might already exist): " . $e->getMessage();
}
?>