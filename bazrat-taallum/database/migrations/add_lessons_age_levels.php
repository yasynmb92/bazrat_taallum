<?php
// Run this once to add lessons table and update courses
$conn = db();
$conn->set_charset("utf8mb4");

function column_exists_lessons(mysqli $conn, string $table, string $column): bool {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Create lessons table
$lessons_sql = "CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title_ar VARCHAR(255) NOT NULL,
    title_en VARCHAR(255),
    video_url VARCHAR(500) NOT NULL,
    duration VARCHAR(20),
    sort_order INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)";

if ($conn->query($lessons_sql)) {
    echo "✅ Lessons table created<br>";
} else {
    echo "❌ Lessons table error: " . $conn->error . "<br>";
}

// Update courses table for kids education
$course_columns = [
    'age_group_range' => "ALTER TABLE courses ADD COLUMN age_group_range VARCHAR(50) AFTER level",
    'schedule' => "ALTER TABLE courses ADD COLUMN schedule TEXT AFTER age_group_range",
    'target_age' => "ALTER TABLE courses ADD COLUMN target_age VARCHAR(100) AFTER schedule"
];

foreach ($course_columns as $column => $sql) {
    if (column_exists_lessons($conn, 'courses', $column)) {
        echo "⏭️ $column already exists<br>";
        continue;
    }
    if ($conn->query($sql)) {
        echo "✅ $sql<br>";
    } else {
        echo "⚠️ $sql - " . $conn->error . "<br>";
    }
}

$course_updates = [
    "UPDATE courses SET age_group_range = '7-12 سنة' WHERE level = 'beginner'",
    "UPDATE courses SET age_group_range = '13-15 سنة' WHERE level = 'intermediate'",
    "UPDATE courses SET age_group_range = '16-18 سنة' WHERE level = 'advanced'",
    "UPDATE courses SET target_age = 'أطفال 7-18 سنة'"
];

foreach ($course_updates as $sql) {
    if ($conn->query($sql)) {
        echo "✅ $sql<br>";
    } else {
        echo "⚠️ $sql - " . $conn->error . "<br>";
    }
}

echo "<h3>✅ تم تحديث قاعدة البيانات للتعليم الإلكتروني للأطفال 7-18</h3>
      <p><a href='../admin/lessons.php?course=1'>إدارة حصص الدورة الأولى</a></p>";
?>

