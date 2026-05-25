<?php

$db = Database::getInstance()->getConnection();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(255) NOT NULL UNIQUE,
        value TEXT NULL,
        description TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    echo "✓ Settings table created or already exists.<br>";

    $defaults = [
        ['church_name', 'UPPER ROOM ASSEMBLY MAMPONG', 'Church display name'],
        ['church_logo', '', 'Church logo relative path'],
        ['theme', 'dark', 'Application theme'],
        ['finance_currency', 'GHS', 'Default finance currency'],
        ['sms_provider', 'infobip', 'Primary SMS gateway provider'],
        ['sms_sender_id', 'UPPERROOM', 'Default SMS sender identifier']
    ];

    $stmt = $db->prepare("INSERT INTO settings (key_name, value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = value");
    foreach ($defaults as $row) {
        $stmt->execute($row);
    }

    echo "✓ Default settings verified.<br>";
} catch (PDOException $e) {
    echo "✗ Error creating settings table: " . $e->getMessage() . "<br>";
}
