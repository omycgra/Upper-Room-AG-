<?php
$db = Database::getInstance()->getConnection();
try {
    $db->exec("CREATE TABLE IF NOT EXISTS finances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NULL,
        transaction_type ENUM('Offering', 'Tithe', 'Donation', 'Pledge Fulfillment', 'Expense'),
        amount DECIMAL(15, 2) NOT NULL,
        payment_method ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Check'),
        transaction_date DATE,
        description TEXT,
        reference_no VARCHAR(100),
        recorded_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE SET NULL,
        FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✓ Finances table created.<br>";
} catch (PDOException $e) { echo "✗ Error: " . $e->getMessage() . "<br>"; }
