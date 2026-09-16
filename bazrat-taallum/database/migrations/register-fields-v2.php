<?php
require_once __DIR__ . '/../../config/db.php';

$conn = db();
$conn->set_charset('utf8mb4');

$fields = [
    'birth_year INT NULL',
    'birth_month INT NULL',
    'phone_country_code VARCHAR(10) NULL',
    'phone_number VARCHAR(20) NULL',
    'whatsapp VARCHAR(20) NULL',
    'city VARCHAR(100) NULL',
    'state VARCHAR(100) NULL',
    'email_verified TINYINT(1) DEFAULT 0',
    'verification_code VARCHAR(10) NULL',
    'temp_password VARCHAR(255) NULL',
    'parent_phone VARCHAR(20) NULL',
    'parent_name VARCHAR(120) NULL'
];

foreach ($fields as $field) {
    $column = explode(' ', trim($field), 2)[0];
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($result->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD $field");
        echo "✅ Added: $field\n";
    } else {
        echo "⏭️  Exists: $field\n";
    }
}

echo "\n🎉 Migration Complete! Run: http://localhost/learning-seeds2/bazrat-taallum/database/migrations/register-fields-v2.php";
?>

