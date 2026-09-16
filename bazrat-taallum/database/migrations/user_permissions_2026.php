<?php
/**
 * Run once: php database/migrations/user_permissions_2026.php
 * Adds permissions_json column to users for admin-assigned fine-grained permissions.
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();

function col_exists_perm(mysqli $conn, string $table, string $col): bool {
    $c = $conn->real_escape_string($col);
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $r && $r->num_rows > 0;
}

if (!col_exists_perm($conn, 'users', 'permissions_json')) {
    $conn->query("ALTER TABLE users ADD COLUMN permissions_json TEXT NULL AFTER full_name_change_used");
}

echo "OK: user_permissions_2026 migration\n";

