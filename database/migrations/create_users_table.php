<?php

$db = Database::getInstance()->getConnection();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(30) DEFAULT 'staff',
        last_login DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "✓ Users table created or already exists.<br>";

    // Check if admin user exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['ranchoyc2019@gmail.com']);
    if ($stmt->fetchColumn() == 0) {
        $password = password_hash('Admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['System Admin', 'ranchoyc2019@gmail.com', $password, 'admin']);
        echo "✓ New admin user created (ranchoyc2019@gmail.com / Admin123).<br>";
    }

} catch (PDOException $e) {
    echo "✗ Error creating users table: " . $e->getMessage() . "<br>";
}
