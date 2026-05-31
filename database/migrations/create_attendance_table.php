<?php
$db = Database::getInstance()->getConnection();
try {
    $db->exec("CREATE TABLE IF NOT EXISTS attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT,
        service_date DATE,
        service_type ENUM('Sunday Service', 'Mid-week Service', 'Midweek Service', 'Youth Meeting', 'Youth Service', 'Children Service', 'Special Event') DEFAULT 'Sunday Service',
        status ENUM('Present', 'Absent', 'Excused', 'Late') DEFAULT 'Present',
        check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        qr_code_scanned BOOLEAN DEFAULT FALSE,
        source VARCHAR(20) NULL,
        bio_id VARCHAR(50) NULL,
        device_time DATETIME NULL,
        device_serial VARCHAR(60) NULL,
        punch_type VARCHAR(30) NULL,
        raw_payload TEXT NULL,
        imported_at DATETIME NULL,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✓ Attendance table created.<br>";
} catch (PDOException $e) { echo "✗ Error: " . $e->getMessage() . "<br>"; }
