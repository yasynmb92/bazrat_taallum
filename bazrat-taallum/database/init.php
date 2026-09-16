<?php
require_once __DIR__ . '/../config/db.php';

$conn = db();

$schema = file_get_contents(__DIR__ . '/schema.sql');
if (!$conn->multi_query($schema)) {
    die('Schema error: ' . $conn->error);
}
while ($conn->more_results() && $conn->next_result()) {;}

$hasUsers = $conn->query("SELECT COUNT(*) c FROM users");
$count = $hasUsers ? (int)$hasUsers->fetch_assoc()['c'] : 0;

if ($count === 0) {
    $seed = file_get_contents(__DIR__ . '/seed.sql');
    if (!$conn->multi_query($seed)) {
        die('Seed error: ' . $conn->error);
    }
    while ($conn->more_results() && $conn->next_result()) {;}
}

echo "Database initialized successfully.";
