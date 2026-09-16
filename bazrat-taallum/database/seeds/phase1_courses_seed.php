<?php
/**
 * Run after roadmap migration:
 * php database/seeds/phase1_courses_seed.php
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();
$conn->set_charset('utf8mb4');

function id_by_name(mysqli $conn, string $table, string $col, string $value): int {
    $stmt = $conn->prepare("SELECT id FROM {$table} WHERE {$col} = ? LIMIT 1");
    $stmt->bind_param('s', $value);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return (int)($r['id'] ?? 0);
}

function ensure_category(mysqli $conn, string $ar, string $en): int {
    $id = id_by_name($conn, 'categories', 'name_ar', $ar);
    if ($id > 0) {
        return $id;
    }
    $stmt = $conn->prepare('INSERT INTO categories (name_ar, name_en) VALUES (?, ?)');
    $stmt->bind_param('ss', $ar, $en);
    $stmt->execute();
    return (int)$conn->insert_id;
}

function table_exists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SHOW TABLES LIKE '{$t}'");
    return (bool)($r && $r->num_rows > 0);
}

function table_has_column(mysqli $conn, string $table, string $column): bool {
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $r = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return (bool)($r && $r->num_rows > 0);
}

function ensure_course(mysqli $conn, array $c): int {
    $hasThumb = table_has_column($conn, 'courses', 'thumbnail');
    $stmt = $conn->prepare('SELECT id, thumbnail FROM courses WHERE title_ar = ? LIMIT 1');
    $stmt->bind_param('s', $c['title_ar']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $cid = (int)($row['id'] ?? 0);
        if ($hasThumb) {
            $existingThumb = trim((string)($row['thumbnail'] ?? ''));
            $newThumb = trim((string)($c['thumbnail'] ?? ''));
            if ($cid > 0 && $newThumb !== '' && $existingThumb === '') {
                $up = $conn->prepare('UPDATE courses SET thumbnail = ? WHERE id = ?');
                $up->bind_param('si', $newThumb, $cid);
                $up->execute();
            }
        }
        return $cid;
    }

    if ($hasThumb) {
        $sql = "INSERT INTO courses
                (category_id, track_id, title_ar, title_en, description_ar, description_en, level, age_group, thumbnail, price, is_free, is_published)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $st = $conn->prepare($sql);
        $st->bind_param(
            'iisssssssdii',
            $c['category_id'],
            $c['track_id'],
            $c['title_ar'],
            $c['title_en'],
            $c['description_ar'],
            $c['description_en'],
            $c['level'],
            $c['age_group'],
            $c['thumbnail'],
            $c['price'],
            $c['is_free'],
            $c['is_published']
        );
        $st->execute();
        return (int)$conn->insert_id;
    }

    $sql = "INSERT INTO courses
            (category_id, track_id, title_ar, title_en, description_ar, description_en, level, age_group, price, is_free, is_published)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $st = $conn->prepare($sql);
    $st->bind_param(
        'iissssssdii',
        $c['category_id'],
        $c['track_id'],
        $c['title_ar'],
        $c['title_en'],
        $c['description_ar'],
        $c['description_en'],
        $c['level'],
        $c['age_group'],
        $c['price'],
        $c['is_free'],
        $c['is_published']
    );
    $st->execute();
    return (int)$conn->insert_id;
}

$tracks = [];
$res = $conn->query('SELECT id, slug FROM learning_tracks');
while ($res && $r = $res->fetch_assoc()) {
    $tracks[(string)$r['slug']] = (int)$r['id'];
}

$catProg = ensure_category($conn, 'البرمجة', 'Programming');
$catAI = ensure_category($conn, 'الذكاء الاصطناعي', 'AI');
$catCyber = ensure_category($conn, 'الأمن السيبراني', 'Cybersecurity');
$catData = ensure_category($conn, 'تحليل البيانات', 'Data Analysis');
$catRob = ensure_category($conn, 'الروبوتات', 'Robotics');
$catMkt = ensure_category($conn, 'التسويق الإلكتروني', 'Digital Marketing');

$courses = [
    ['title_ar' => 'سكراتش للمبتدئين', 'title_en' => 'Scratch for Beginners', 'category_id' => $catProg, 'track_id' => $tracks['programming-dev'] ?? 0, 'level' => 'beginner', 'age_group' => '8-12', 'price' => 0, 'is_free' => 1, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6'],
    ['title_ar' => 'بايثون للمراهقين', 'title_en' => 'Python for Teens', 'category_id' => $catProg, 'track_id' => $tracks['programming-dev'] ?? 0, 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 29, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995'],
    ['title_ar' => 'تطوير الويب الكامل', 'title_en' => 'Full Web Development', 'category_id' => $catProg, 'track_id' => $tracks['programming-dev'] ?? 0, 'level' => 'advanced', 'age_group' => '16-18', 'price' => 49, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1552664730-d307ca884978'],
    ['title_ar' => 'مدخل الذكاء الاصطناعي', 'title_en' => 'AI Foundations', 'category_id' => $catAI, 'track_id' => $tracks['ai-ml'] ?? 0, 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 39, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1518770660439-4636190af475'],
    ['title_ar' => 'تعلم الآلة العملي', 'title_en' => 'Applied Machine Learning', 'category_id' => $catAI, 'track_id' => $tracks['ai-ml'] ?? 0, 'level' => 'advanced', 'age_group' => '16-18', 'price' => 59, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c'],
    ['title_ar' => 'أمن الإنترنت للمبتدئين', 'title_en' => 'Internet Safety Basics', 'category_id' => $catCyber, 'track_id' => $tracks['cyber-security'] ?? 0, 'level' => 'beginner', 'age_group' => '8-12', 'price' => 0, 'is_free' => 1, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa'],
    ['title_ar' => 'أساسيات الأمن السيبراني', 'title_en' => 'Cybersecurity Fundamentals', 'category_id' => $catCyber, 'track_id' => $tracks['cyber-security'] ?? 0, 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 35, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4'],
    ['title_ar' => 'تحليل البيانات بالبايثون', 'title_en' => 'Data Analysis with Python', 'category_id' => $catData, 'track_id' => $tracks['data-analytics'] ?? 0, 'level' => 'advanced', 'age_group' => '16-18', 'price' => 45, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71'],
    ['title_ar' => 'الروبوتات للصغار', 'title_en' => 'Robotics for Kids', 'category_id' => $catRob, 'track_id' => $tracks['robotics-iot'] ?? 0, 'level' => 'beginner', 'age_group' => '8-12', 'price' => 25, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1581092445129-4fe8f6c9b6a8'],
    ['title_ar' => 'Arduino وإنترنت الأشياء', 'title_en' => 'Arduino and IoT', 'category_id' => $catRob, 'track_id' => $tracks['robotics-iot'] ?? 0, 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 35, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1518779578993-ec3579fee39f'],
    ['title_ar' => 'التسويق الرقمي للشباب', 'title_en' => 'Digital Marketing for Youth', 'category_id' => $catMkt, 'track_id' => $tracks['digital-marketing'] ?? 0, 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 30, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d'],
    ['title_ar' => 'استراتيجية نمو المشاريع الرقمية', 'title_en' => 'Digital Growth Strategy', 'category_id' => $catMkt, 'track_id' => $tracks['digital-marketing'] ?? 0, 'level' => 'advanced', 'age_group' => '16-18', 'price' => 42, 'is_free' => 0, 'is_published' => 1, 'thumbnail' => 'https://images.unsplash.com/photo-1551632436-cbf08324fb5b'],
];

$courseIds = [];
foreach ($courses as $c) {
    $c['description_ar'] = 'دورة عملية ضمن خطة التحول الرقمي للمشروع، مع أنشطة تطبيقية أسبوعية.';
    $c['description_en'] = 'A hands-on course aligned with the project roadmap, including weekly practical activities.';
    $cid = ensure_course($conn, $c);
    if ($cid > 0) {
        $courseIds[$c['title_ar']] = $cid;
    }
}

if (table_exists($conn, 'lessons')) {
    foreach ($courseIds as $courseId) {
        $st = $conn->prepare('SELECT id FROM lessons WHERE course_id = ? LIMIT 1');
        $st->bind_param('i', $courseId);
        $st->execute();
        if ($st->get_result()->fetch_assoc()) {
            continue;
        }
        $ins = $conn->prepare('INSERT INTO lessons (course_id, title_ar, title_en, content_ar, content_en, video_url, duration, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $titleAr = 'درس تمهيدي';
        $titleEn = 'Intro Lesson';
        $contentAr = 'نرحب بك في هذه الدورة! هذه حصة تمهيدية قصيرة كبداية للتجربة.';
        $contentEn = 'Welcome to this course! This is a short intro lesson to start your experience.';
        $videoUrl = '';
        $duration = '15 دقيقة';
        $sortOrder = 1;
        $ins->bind_param('issssssi', $courseId, $titleAr, $titleEn, $contentAr, $contentEn, $videoUrl, $duration, $sortOrder);
        $ins->execute();
    }
}

if (table_exists($conn, 'course_quizzes')) {
    $questions = [
        ['q' => 'اختبر فهمك: اختر الإجابة الصحيحة للسؤال الأول', 'choices' => ['أ', 'ب', 'ج'], 'answer' => 0],
        ['q' => 'اختبر فهمك: اختر الإجابة الصحيحة للسؤال الثاني', 'choices' => ['أ', 'ب', 'ج'], 'answer' => 0],
    ];
    $qJson = json_encode($questions, JSON_UNESCAPED_UNICODE);
    foreach ($courseIds as $courseId) {
        $st = $conn->prepare('SELECT id FROM course_quizzes WHERE course_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1');
        $st->bind_param('i', $courseId);
        $st->execute();
        if ($st->get_result()->fetch_assoc()) {
            continue;
        }
        $titleAr = 'اختبار الدورة';
        $titleEn = 'Course Quiz';
        $passScore = 70;
        $maxAttempts = 3;
        $isActive = 1;
        $ins = $conn->prepare('INSERT INTO course_quizzes (course_id, title_ar, title_en, questions_json, pass_score, max_attempts, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ins->bind_param('isssiii', $courseId, $titleAr, $titleEn, $qJson, $passScore, $maxAttempts, $isActive);
        $ins->execute();
    }
}

if (table_exists($conn, 'course_projects')) {
    $rubric = json_encode([
        'instructions' => 'قم بإرسال رابط المشروع + شرح مختصر لما أنجزته.',
        'criteria' => [
            ['label' => 'الرابط', 'weight' => 40],
            ['label' => 'الشرح', 'weight' => 60],
        ],
    ], JSON_UNESCAPED_UNICODE);
    foreach ($courseIds as $courseId) {
        $st = $conn->prepare('SELECT id FROM course_projects WHERE course_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1');
        $st->bind_param('i', $courseId);
        $st->execute();
        if ($st->get_result()->fetch_assoc()) {
            continue;
        }
        $titleAr = 'المشروع التطبيقي';
        $titleEn = 'Capstone Project';
        $descAr = 'نفّذ مشروعًا بسيطًا يطبق ما تعلمته في الدورة.';
        $descEn = 'Create a simple project applying what you learned in this course.';
        $passScore = 60;
        $isActive = 1;
        $ins = $conn->prepare('INSERT INTO course_projects (course_id, title_ar, title_en, description_ar, description_en, rubric_json, pass_score, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $ins->bind_param('isssssii', $courseId, $titleAr, $titleEn, $descAr, $descEn, $rubric, $passScore, $isActive);
        $ins->execute();
    }
}

echo "OK: phase1 courses seed (thumbnails + lessons + quizzes + projects)\n";
