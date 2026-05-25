<?php
$db = Database::getInstance()->getConnection();
try {
    $db->exec("CREATE TABLE IF NOT EXISTS visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(100),
        gender VARCHAR(20) NULL,
        address TEXT NULL,
        visit_date DATE,
        service_attended VARCHAR(100) NULL,
        is_first_time BOOLEAN DEFAULT TRUE,
        invited_by VARCHAR(100),
        preferred_contact_method VARCHAR(30) NULL,
        prayer_request TEXT,
        follow_up_status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
        follow_up_date DATE NULL,
        follow_up_notes TEXT NULL,
        assigned_to INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✓ Visitors table created.<br>";
} catch (PDOException $e) { echo "✗ Error: " . $e->getMessage() . "<br>"; }
