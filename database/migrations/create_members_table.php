<?php
$db = Database::getInstance()->getConnection();
try {
    $db->exec("CREATE TABLE IF NOT EXISTS members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_code VARCHAR(20) UNIQUE,
        bio_id VARCHAR(50) UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        nationality VARCHAR(100) NULL,
        address TEXT,
        stays_at VARCHAR(255) NULL,
        home_town VARCHAR(120) NULL,
        date_of_birth DATE,
        gender ENUM('Male', 'Female', 'Other'),
        marital_status ENUM('Single', 'Married', 'Widowed', 'Divorced'),
        spouse_name VARCHAR(150) NULL,
        mother_name VARCHAR(150) NULL,
        father_name VARCHAR(150) NULL,
        is_baptized BOOLEAN DEFAULT FALSE,
        baptism_pastor_church VARCHAR(255) NULL,
        currently_working BOOLEAN DEFAULT FALSE,
        work_name VARCHAR(150) NULL,
        occupation VARCHAR(100),
        cluster_id INT,
        department_id INT,
        membership_status ENUM('Active', 'Inactive', 'Under Discipline', 'Transferred', 'Deceased') DEFAULT 'Active',
        join_date DATE,
        baptism_date DATE,
        photo_path VARCHAR(255),
        emergency_contact_name VARCHAR(100),
        emergency_contact_phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (cluster_id) REFERENCES clusters(id) ON DELETE SET NULL,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    $db->exec("CREATE TABLE IF NOT EXISTS member_departments (
        member_id INT NOT NULL,
        department_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (member_id, department_id),
        KEY idx_member_departments_department (department_id),
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✓ Members table created.<br>";
} catch (PDOException $e) { echo "✗ Error: " . $e->getMessage() . "<br>"; }
