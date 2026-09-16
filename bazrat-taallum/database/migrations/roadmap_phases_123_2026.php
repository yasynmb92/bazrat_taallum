<?php
/**
 * Run once:
 * php database/migrations/roadmap_phases_123_2026.php
 *
 * Phase 1: learning tracks + age group on courses.
 * Phase 2: projects, quizzes, attempts, challenges.
 * Phase 3: KPI-ready support tables.
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();

function col_exists_roadmap(mysqli $conn, string $table, string $col): bool {
    $c = $conn->real_escape_string($col);
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $r && $r->num_rows > 0;
}

$conn->query("
CREATE TABLE IF NOT EXISTS learning_tracks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name_ar VARCHAR(160) NOT NULL,
    name_en VARCHAR(160) NOT NULL,
    description_ar TEXT NULL,
    description_en TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$seedTracks = [
    ['ai-ml', 'الذكاء الاصطناعي وتعلم الآلة', 'AI & Machine Learning', 10],
    ['cyber-security', 'الأمن السيبراني', 'Cyber Security', 20],
    ['robotics-iot', 'الروبوتات وإنترنت الأشياء', 'Robotics & IoT', 30],
    ['data-analytics', 'تحليل البيانات', 'Data Analytics', 40],
    ['programming-dev', 'البرمجة والتطوير', 'Programming & Development', 50],
    ['digital-marketing', 'التسويق الإلكتروني', 'Digital Marketing', 60],
];
foreach ($seedTracks as $t) {
    $slug = $conn->real_escape_string($t[0]);
    $ar = $conn->real_escape_string($t[1]);
    $en = $conn->real_escape_string($t[2]);
    $so = (int)$t[3];
    $conn->query("INSERT INTO learning_tracks (slug, name_ar, name_en, sort_order)
                  VALUES ('$slug', '$ar', '$en', $so)
                  ON DUPLICATE KEY UPDATE name_ar = VALUES(name_ar), name_en = VALUES(name_en), sort_order = VALUES(sort_order)");
}

if (!col_exists_roadmap($conn, 'courses', 'track_id')) {
    $conn->query("ALTER TABLE courses ADD COLUMN track_id INT NULL AFTER category_id");
    $conn->query("ALTER TABLE courses ADD CONSTRAINT fk_courses_track FOREIGN KEY (track_id) REFERENCES learning_tracks(id) ON DELETE SET NULL");
}
if (!col_exists_roadmap($conn, 'courses', 'age_group')) {
    $conn->query("ALTER TABLE courses ADD COLUMN age_group ENUM('8-12','13-15','16-18') NULL AFTER level");
}

$conn->query("
CREATE TABLE IF NOT EXISTS course_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title_ar VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) NULL,
    description_ar MEDIUMTEXT NULL,
    description_en MEDIUMTEXT NULL,
    rubric_json MEDIUMTEXT NULL,
    pass_score INT NOT NULL DEFAULT 60,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS course_quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title_ar VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) NULL,
    questions_json LONGTEXT NOT NULL,
    pass_score INT NOT NULL DEFAULT 70,
    max_attempts INT NOT NULL DEFAULT 3,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    user_id INT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    answers_json LONGTEXT NULL,
    status ENUM('passed','failed') NOT NULL DEFAULT 'failed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES course_quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_quiz_user (quiz_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS weekly_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title_ar VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) NULL,
    description_ar MEDIUMTEXT NULL,
    description_en MEDIUMTEXT NULL,
    challenge_type ENUM('hackathon','project','quiz') NOT NULL DEFAULT 'project',
    start_at DATETIME NULL,
    end_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("
CREATE TABLE IF NOT EXISTS challenge_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT NOT NULL,
    user_id INT NOT NULL,
    submission_url VARCHAR(700) NULL,
    notes TEXT NULL,
    score INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES weekly_challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_challenge_user (challenge_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo "OK: roadmap_phases_123_2026 migration\n";

