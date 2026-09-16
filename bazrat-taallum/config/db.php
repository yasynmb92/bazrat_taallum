<?php
if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === basename(__FILE__)) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/config.php';

function db() {
    static $conn = null;
    if ($conn === null) {
        if (!class_exists('mysqli')) {
            error_log('Database connection unavailable: mysqli extension is not loaded.');
            http_response_code(503);
            exit('Database service unavailable.');
        }
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        if ($conn->connect_error) {
            error_log('Database connection failed: ' . $conn->connect_error);
            http_response_code(503);
            exit('Database service unavailable.');
        }
        $conn->set_charset('utf8mb4');
        if (!$conn->select_db(DB_NAME)) {
            error_log('Database selection failed for ' . DB_NAME . ': ' . $conn->error);
            http_response_code(503);
            exit('Database service unavailable.');
        }
    }
    return $conn;
}
