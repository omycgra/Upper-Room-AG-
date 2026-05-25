<?php
$db = Database::getInstance()->getConnection();
try {
    $db->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT,
        service_date DATE,
        service_type ENUM('Sunday Service', 'Mid-week Service', 'Youth Meeting', 'Special Event'),
        status ENUM('Present', 'Absent', 'Excused') DEFAULT 'Present',
        check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        qr_code_scanned BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✓ Attendance table created.<br>";
} catch (PDOException $e) { echo "✗ Error: " . $e->getMessage() . "<br>"; }
