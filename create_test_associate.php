<?php
/**
 * Create Test Associate User
 * Creates a sample associate account for testing
 */

require_once __DIR__ . '/config.php';

echo "=== Create Test Associate ===\n\n";

// Test associate credentials
$username = 'associate1';
$email = 'associate1@test.com';
$password = 'test123';
$full_name = 'Test Associate';
$role = 'user';

try {
    // Check if associate already exists
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $checkStmt->execute(['username' => $username, 'email' => $email]);

    if ($checkStmt->fetch()) {
        echo "⚠ Associate user already exists!\n";
        echo "\nExisting credentials:\n";
        echo "Username: $username\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
        exit(0);
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert associate user
    $insertStmt = $pdo->prepare("
        INSERT INTO users (username, email, password, full_name, role, is_active)
        VALUES (:username, :email, :password, :full_name, :role, 1)
    ");

    $insertStmt->execute([
        'username' => $username,
        'email' => $email,
        'password' => $hashedPassword,
        'full_name' => $full_name,
        'role' => $role
    ]);

    echo "✓ Test associate created successfully!\n\n";
    echo "Login credentials:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "You can now login at: http://localhost:8000/login.php\n";
    echo "Associates will see their own dashboard with only their leads.\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>