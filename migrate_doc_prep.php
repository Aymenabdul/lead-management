<?php
require_once 'config.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lead_requirements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            converted_lead_id INT NOT NULL,
            data JSON,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (converted_lead_id) REFERENCES converted_leads(id) ON DELETE CASCADE
        )
    ");
    echo "Migration successful: lead_requirements table created.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>