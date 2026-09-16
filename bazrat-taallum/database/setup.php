<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once __DIR__ . '/../config/db.php';

$conn = db();
$schema = file_get_contents(__DIR__ . '/schema.sql');
if ($schema === false || !$conn->multi_query($schema)) {
    throw new RuntimeException('Schema setup failed: ' . $conn->error);
}
while ($conn->more_results() && $conn->next_result()) {
}

$result = $conn->query('SELECT COUNT(*) AS c FROM users');
$count = $result ? (int)$result->fetch_assoc()['c'] : 0;
if ($count === 0) {
    $seed = file_get_contents(__DIR__ . '/seed.sql');
    if ($seed === false || !$conn->multi_query($seed)) {
        throw new RuntimeException('Seed setup failed: ' . $conn->error);
    }
    while ($conn->more_results() && $conn->next_result()) {
    }
}

$migrations = glob(__DIR__ . '/migrations/*.php') ?: [];
sort($migrations, SORT_STRING);
foreach ($migrations as $migration) {
    require $migration;
}

if (APP_ENV === 'production') {
    $conn->query("UPDATE users SET is_active = 0 WHERE email = 'admin@seed-learning.com'");
}

echo "Database setup completed.\n";
