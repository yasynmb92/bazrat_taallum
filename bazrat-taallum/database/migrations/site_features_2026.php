<?php
/**
 * Run once: php database/migrations/site_features_2026.php
 * Users (register), courses (free/discount), lessons (transcript), enrollments column fix hint.
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();

function col_exists(mysqli $conn, string $table, string $col): bool {
    $c = $conn->real_escape_string($col);
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $r && $r->num_rows > 0;
}

$usersCols = [
    "birth_year INT NULL",
    "birth_month INT NULL",
    "phone_country_code VARCHAR(16) NULL",
    "phone_number VARCHAR(64) NULL",
    "whatsapp VARCHAR(64) NULL",
    "city VARCHAR(120) NULL",
    "state VARCHAR(120) NULL",
    "parent_phone VARCHAR(64) NULL",
    "parent_name VARCHAR(120) NULL",
    "email_verified TINYINT(1) NOT NULL DEFAULT 0",
    "verification_code VARCHAR(32) NULL",
    "temp_password VARCHAR(255) NULL",
];
foreach ($usersCols as $def) {
    $name = preg_replace('/\s.*/', '', $def);
    if (!col_exists($conn, 'users', $name)) {
        $conn->query("ALTER TABLE users ADD COLUMN $def");
    }
}

if (!col_exists($conn, 'courses', 'is_free')) {
    $conn->query("ALTER TABLE courses ADD COLUMN is_free TINYINT(1) NOT NULL DEFAULT 0 AFTER price");
}
if (!col_exists($conn, 'courses', 'course_discount_type')) {
    $conn->query("ALTER TABLE courses ADD COLUMN course_discount_type ENUM('none','percent','fixed') NOT NULL DEFAULT 'none' AFTER is_free");
}
if (!col_exists($conn, 'courses', 'course_discount_value')) {
    $conn->query("ALTER TABLE courses ADD COLUMN course_discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER course_discount_type");
}

if (!col_exists($conn, 'lessons', 'timed_transcript_json')) {
    $conn->query('ALTER TABLE lessons ADD COLUMN timed_transcript_json MEDIUMTEXT NULL AFTER duration');
}

if (!col_exists($conn, 'enrollments', 'enrolled_at')) {
    if (col_exists($conn, 'enrollments', 'created_at')) {
        $conn->query('ALTER TABLE enrollments CHANGE created_at enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    } else {
        $conn->query('ALTER TABLE enrollments ADD COLUMN enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
    }
}

echo "OK: site_features_2026 migration\n";
