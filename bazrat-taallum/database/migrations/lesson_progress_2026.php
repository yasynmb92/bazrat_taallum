<?php
/**
 * Run once via browser: /database/migrations/lesson_progress_2026.php
 * Adds lesson_completions + optional duration column on lessons.
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();

$conn->query("CREATE TABLE IF NOT EXISTS lesson_completions (
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    course_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, lesson_id),
    KEY idx_lc_course (user_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$res = $conn->query("SHOW COLUMNS FROM lessons LIKE 'duration'");
if ($res && $res->num_rows === 0) {
    $conn->query("ALTER TABLE lessons ADD COLUMN duration VARCHAR(32) NULL DEFAULT NULL AFTER video_url");
}

echo 'OK: lesson_completions + lessons.duration';
