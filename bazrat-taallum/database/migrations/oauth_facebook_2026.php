<?php
/**
 * تشغيل مرة واحدة: إضافة ربط فيسبوك لتسجيل الدخول.
 * افتح من المتصفح أو: php oauth_facebook_2026.php
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();

function col_exists_fb(mysqli $conn, string $table, string $col): bool {
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($col);
    $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $r && $r->num_rows > 0;
}

if (!col_exists_fb($conn, 'users', 'facebook_id')) {
    $conn->query("ALTER TABLE users ADD COLUMN facebook_id VARCHAR(32) NULL DEFAULT NULL AFTER temp_password");
}

$idx = $conn->query("SHOW INDEX FROM users WHERE Key_name = 'uq_users_facebook_id'");
if (!$idx || $idx->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD UNIQUE KEY uq_users_facebook_id (facebook_id)");
}

echo "OK: users.facebook_id + unique index\n";
