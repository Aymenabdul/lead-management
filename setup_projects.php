<?php
require_once __DIR__ . '/config.php';

try {
    // Create projects table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            project_name VARCHAR(255) NOT NULL,
            start_date DATE,
            end_date DATE,
            status VARCHAR(50) DEFAULT 'In Progress',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Projects table created successfully or already exists.<br>";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>