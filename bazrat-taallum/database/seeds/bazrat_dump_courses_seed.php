<?php
/**
 * Synchronize the courses represented in bazrat_taallum.sql.
 * Run with: C:\xampp\php\php.exe database/seeds/bazrat_dump_courses_seed.php
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();
$conn->set_charset('utf8mb4');

function seed_course_id(mysqli $conn, string $titleAr): int {
    $stmt = $conn->prepare('SELECT id FROM courses WHERE title_ar = ? LIMIT 1');
    $stmt->bind_param('s', $titleAr);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)($row['id'] ?? 0);
}

function seed_category_id(mysqli $conn, string $nameAr, string $nameEn): int {
    $stmt = $conn->prepare('SELECT id FROM categories WHERE name_ar = ? LIMIT 1');
    $stmt->bind_param('s', $nameAr);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        return (int)$row['id'];
    }
    $stmt = $conn->prepare('INSERT INTO categories (name_ar, name_en) VALUES (?, ?)');
    $stmt->bind_param('ss', $nameAr, $nameEn);
    $stmt->execute();
    return (int)$conn->insert_id;
}

function seed_admin_id(mysqli $conn): int {
    $email = 'admin@seed-learning.com';
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    return (int)(($stmt->get_result()->fetch_assoc()['id'] ?? 0));
}

function seed_course(mysqli $conn, array $course, int $adminId): int {
    $id = seed_course_id($conn, $course['title_ar']);
    if ($id > 0) {
        $isFree = $course['price'] <= 0 ? 1 : 0;
        $stmt = $conn->prepare('UPDATE courses SET category_id=?, title_en=?, description_ar=?, description_en=?, thumbnail=?, instructor_id=?, level=?, age_group=?, price=?, is_free=?, is_published=1 WHERE id=?');
        $stmt->bind_param('issssissdii', $course['category_id'], $course['title_en'], $course['description_ar'], $course['description_en'], $course['thumbnail'], $adminId, $course['level'], $course['age_group'], $course['price'], $isFree, $id);
        $stmt->execute();
        return $id;
    }
    $isFree = $course['price'] <= 0 ? 1 : 0;
    $trackId = null;
    $stmt = $conn->prepare('INSERT INTO courses (category_id, track_id, title_ar, title_en, description_ar, description_en, thumbnail, instructor_id, level, age_group, price, is_free, is_published) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
    $stmt->bind_param('iisssssissdi', $course['category_id'], $trackId, $course['title_ar'], $course['title_en'], $course['description_ar'], $course['description_en'], $course['thumbnail'], $adminId, $course['level'], $course['age_group'], $course['price'], $isFree);
    $stmt->execute();
    return (int)$conn->insert_id;
}

function seed_content(mysqli $conn, int $courseId, array $course): void {
    $existingIds = [];
    $list = $conn->prepare('SELECT id FROM lessons WHERE course_id = ? ORDER BY sort_order, id');
    $list->bind_param('i', $courseId);
    $list->execute();
    $rows = $list->get_result();
    while ($row = $rows->fetch_assoc()) {
        $existingIds[] = (int)$row['id'];
    }
    foreach ($course['lessons'] as $index => $lesson) {
        $order = $index + 1;
        $existingId = $existingIds[$index] ?? 0;
        if ($existingId > 0) {
            $up = $conn->prepare('UPDATE lessons SET title_ar=?, title_en=?, content_ar=?, content_en=?, video_url=?, duration=?, sort_order=? WHERE id=?');
            $up->bind_param('ssssssii', $lesson['title_ar'], $lesson['title_en'], $lesson['content_ar'], $lesson['content_en'], $lesson['video_url'], $lesson['duration'], $order, $existingId);
            $up->execute();
        } else {
            $ins = $conn->prepare('INSERT INTO lessons (course_id, title_ar, title_en, content_ar, content_en, video_url, duration, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $ins->bind_param('issssssi', $courseId, $lesson['title_ar'], $lesson['title_en'], $lesson['content_ar'], $lesson['content_en'], $lesson['video_url'], $lesson['duration'], $order);
            $ins->execute();
        }
    }
    if (count($existingIds) > count($course['lessons'])) {
        $deleteIds = array_slice($existingIds, count($course['lessons']));
        $delete = $conn->prepare('DELETE FROM lessons WHERE id = ? AND course_id = ?');
        foreach ($deleteIds as $deleteId) {
            $delete->bind_param('ii', $deleteId, $courseId);
            $delete->execute();
        }
    }

    $questions = json_encode($course['questions'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = $conn->prepare('SELECT id FROM course_quizzes WHERE course_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $quiz = $stmt->get_result()->fetch_assoc();
    if ($quiz) {
        $up = $conn->prepare('UPDATE course_quizzes SET title_ar=?, title_en=?, questions_json=?, pass_score=70, max_attempts=3, is_active=1 WHERE id=?');
        $up->bind_param('sssi', $course['quiz_ar'], $course['quiz_en'], $questions, $quiz['id']);
        $up->execute();
    } else {
        $ins = $conn->prepare('INSERT INTO course_quizzes (course_id, title_ar, title_en, questions_json, pass_score, max_attempts, is_active) VALUES (?, ?, ?, ?, 70, 3, 1)');
        $ins->bind_param('isss', $courseId, $course['quiz_ar'], $course['quiz_en'], $questions);
        $ins->execute();
    }

    $rubric = json_encode(['criteria' => ['الفهم والتطبيق' => 40, 'جودة التنفيذ' => 40, 'التوثيق' => 20]], JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare('SELECT id FROM course_projects WHERE course_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    if ($project) {
        $up = $conn->prepare('UPDATE course_projects SET title_ar=?, title_en=?, description_ar=?, description_en=?, rubric_json=?, pass_score=60, is_active=1 WHERE id=?');
        $up->bind_param('sssssi', $course['project_ar'], $course['project_en'], $course['project_desc_ar'], $course['project_desc_en'], $rubric, $project['id']);
        $up->execute();
    } else {
        $ins = $conn->prepare('INSERT INTO course_projects (course_id, title_ar, title_en, description_ar, description_en, rubric_json, pass_score, is_active) VALUES (?, ?, ?, ?, ?, ?, 60, 1)');
        $ins->bind_param('isssss', $courseId, $course['project_ar'], $course['project_en'], $course['project_desc_ar'], $course['project_desc_en'], $rubric);
        $ins->execute();
    }
}

$adminId = seed_admin_id($conn);
if ($adminId <= 0) {
    throw new RuntimeException('The seeded main administrator was not found.');
}

$categories = [
    'البرمجة' => ['Programming'],
    'الذكاء الاصطناعي' => ['Artificial Intelligence'],
    'الأعمال' => ['Business'],
    'الأمن السيبراني' => ['Cybersecurity'],
    'تحليل البيانات' => ['Data Analysis'],
    'الروبوتات' => ['Robotics'],
    'التسويق الإلكتروني' => ['Digital Marketing'],
];
$categoryIds = [];
foreach ($categories as $ar => $names) {
    $categoryIds[$ar] = seed_category_id($conn, $ar, $names[0]);
}

$ageLinks = [
    '8-12' => ['assets/video/Python.mp4', 'assets/video/Python.mp4', 'assets/video/Python.mp4'],
    '13-15' => ['assets/video/Python.mp4', 'assets/video/Python.mp4', 'assets/video/Python.mp4'],
    '16-18' => ['assets/video/Python.mp4', 'assets/video/Python.mp4', 'assets/video/Python.mp4'],
];

function seed_lessons_for_age(string $ageGroup, array $links): array {
    return [
        ['title_ar' => 'مقدمة', 'title_en' => 'Introduction', 'content_ar' => 'تعريف مبسط بالمجال وأهداف الدورة.', 'content_en' => 'A clear introduction to the field and course goals.', 'video_url' => $links[$ageGroup][0], 'duration' => '20 دقيقة'],
        ['title_ar' => 'محاضرة تطبيقية', 'title_en' => 'Guided Practice', 'content_ar' => 'تطبيق عملي مناسب للفئة العمرية المحددة.', 'content_en' => 'Age-appropriate guided practice.', 'video_url' => $links[$ageGroup][1], 'duration' => '30 دقيقة'],
        ['title_ar' => 'مراجعة واستعداد للاختبار', 'title_en' => 'Review and Exam Prep', 'content_ar' => 'مراجعة المفاهيم الأساسية قبل الاختبار النهائي.', 'content_en' => 'Review the key concepts before the final assessment.', 'video_url' => $links[$ageGroup][2], 'duration' => '15 دقيقة'],
    ];
}

$courseSpecs = [
    ['title_ar' => 'مدخل إلى تطوير الويب', 'title_en' => 'Introduction to Web Development', 'category' => 'البرمجة', 'level' => 'beginner', 'age_group' => '8-12', 'price' => 0, 'description_ar' => 'تعريف الأطفال بأساسيات صفحات الويب بطريقة عملية وآمنة.', 'description_en' => 'A safe, practical introduction to web pages for young learners.', 'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6', 'quiz_ar' => 'الاختبار النهائي الشامل لتطوير الويب', 'quiz_en' => 'Comprehensive Web Development Final Exam', 'project_ar' => 'صفحة ويب تعليمية بسيطة', 'project_en' => 'A Simple Educational Web Page', 'project_desc_ar' => 'أنشئ صفحة تعريفية بسيطة باستخدام HTML وCSS.', 'project_desc_en' => 'Build a simple profile page using HTML and CSS.'],
    ['title_ar' => 'مقدمة في تعلم الآلة', 'title_en' => 'Machine Learning Fundamentals', 'category' => 'الذكاء الاصطناعي', 'level' => 'intermediate', 'age_group' => '16-18', 'price' => 79.99, 'description_ar' => 'فهم مبادئ البيانات والنماذج والتنبؤ مع أمثلة مناسبة للطلاب.', 'description_en' => 'Understand data, models, and prediction through student-friendly examples.', 'thumbnail' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995', 'quiz_ar' => 'الاختبار النهائي الشامل لتعلم الآلة', 'quiz_en' => 'Comprehensive Machine Learning Final Exam', 'project_ar' => 'نموذج تنبؤ تعليمي', 'project_en' => 'An Educational Prediction Model', 'project_desc_ar' => 'وثّق فكرة نموذج تنبؤ بسيط ومدخلاته ومخرجاته.', 'project_desc_en' => 'Document a simple prediction model with its inputs and outputs.'],
    ['title_ar' => 'سكراتش للمبتدئين', 'title_en' => 'Scratch for Beginners', 'category' => 'البرمجة', 'level' => 'beginner', 'age_group' => '8-12', 'price' => 0, 'description_ar' => 'تعلم التفكير البرمجي وصناعة قصة أو لعبة تفاعلية باستخدام Scratch.', 'description_en' => 'Learn computational thinking by creating an interactive Scratch story or game.', 'thumbnail' => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6', 'quiz_ar' => 'الاختبار النهائي الشامل لسكراتش', 'quiz_en' => 'Comprehensive Scratch Final Exam', 'project_ar' => 'لعبة سكراتش تفاعلية', 'project_en' => 'An Interactive Scratch Game', 'project_desc_ar' => 'أنشئ لعبة قصيرة فيها شخصية وتعليمات ونتيجة.', 'project_desc_en' => 'Create a short game with a character, instructions, and a score.'],
    ['title_ar' => 'بايثون للمراهقين', 'title_en' => 'Python for Teens', 'category' => 'البرمجة', 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 29, 'description_ar' => 'أساسيات بايثون من المتغيرات إلى البرامج الصغيرة للمراهقين.', 'description_en' => 'Python fundamentals from variables to small programs for teens.', 'thumbnail' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995', 'quiz_ar' => 'الاختبار النهائي الشامل لبايثون', 'quiz_en' => 'Comprehensive Python Final Exam', 'project_ar' => 'برنامج بايثون صغير', 'project_en' => 'A Small Python Program', 'project_desc_ar' => 'اكتب برنامجًا بسيطًا يستقبل بيانات ويعرض نتيجة مفيدة.', 'project_desc_en' => 'Write a small program that accepts input and returns a useful result.'],
    ['title_ar' => 'تطوير الويب الكامل', 'title_en' => 'Full Web Development', 'category' => 'البرمجة', 'level' => 'advanced', 'age_group' => '16-18', 'price' => 49, 'description_ar' => 'مسار متقدم لبناء واجهات ويب منظمة وربطها بمنطق تفاعلي.', 'description_en' => 'An advanced path for building structured, interactive web experiences.', 'thumbnail' => 'https://images.unsplash.com/photo-1552664730-d307ca884978', 'quiz_ar' => 'الاختبار النهائي الشامل لتطوير الويب', 'quiz_en' => 'Comprehensive Full Web Development Final Exam', 'project_ar' => 'موقع ويب متكامل', 'project_en' => 'A Complete Web Project', 'project_desc_ar' => 'نفّذ موقعًا صغيرًا متعدد الصفحات مع نموذج تفاعلي.', 'project_desc_en' => 'Build a small multi-page site with an interactive form.'],
    ['title_ar' => 'مدخل الذكاء الاصطناعي', 'title_en' => 'AI Foundations', 'category' => 'الذكاء الاصطناعي', 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 39, 'description_ar' => 'تعرف على الذكاء الاصطناعي وتطبيقاته وحدوده ومسؤوليته.', 'description_en' => 'Explore AI, its applications, limitations, and responsible use.', 'thumbnail' => 'https://images.unsplash.com/photo-1518770660439-4636190af475', 'quiz_ar' => 'الاختبار النهائي الشامل للذكاء الاصطناعي', 'quiz_en' => 'Comprehensive AI Foundations Final Exam', 'project_ar' => 'فكرة تطبيق ذكاء اصطناعي', 'project_en' => 'An AI Application Concept', 'project_desc_ar' => 'صمّم فكرة تطبيق ذكاء اصطناعي واشرح فائدته وحدوده.', 'project_desc_en' => 'Design an AI application concept and explain its value and limits.'],
    ['title_ar' => 'تعلم الآلة العملي', 'title_en' => 'Applied Machine Learning', 'category' => 'الذكاء الاصطناعي', 'level' => 'advanced', 'age_group' => '16-18', 'price' => 59, 'description_ar' => 'تطبيق مبادئ تعلم الآلة على بيانات بسيطة مع تقييم النتائج.', 'description_en' => 'Apply machine learning concepts to simple data and evaluate results.', 'thumbnail' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c', 'quiz_ar' => 'الاختبار النهائي الشامل لتعلم الآلة التطبيقي', 'quiz_en' => 'Comprehensive Applied Machine Learning Final Exam', 'project_ar' => 'تحليل بيانات تنبؤي', 'project_en' => 'A Predictive Data Analysis', 'project_desc_ar' => 'أنجز تحليلًا مبسطًا وفسّر دقة النموذج.', 'project_desc_en' => 'Complete a simple analysis and explain model accuracy.'],
    ['title_ar' => 'أمن الإنترنت للمبتدئين', 'title_en' => 'Internet Safety Basics', 'category' => 'الأمن السيبراني', 'level' => 'beginner', 'age_group' => '8-12', 'price' => 0, 'description_ar' => 'عادات آمنة لكلمات المرور والخصوصية والتعامل مع الإنترنت.', 'description_en' => 'Safe habits for passwords, privacy, and everyday internet use.', 'thumbnail' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa', 'quiz_ar' => 'الاختبار النهائي الشامل لأمن الإنترنت', 'quiz_en' => 'Comprehensive Internet Safety Final Exam', 'project_ar' => 'ملصق السلامة الرقمية', 'project_en' => 'A Digital Safety Poster', 'project_desc_ar' => 'صمّم ملصقًا يشرح ثلاث عادات للسلامة الرقمية.', 'project_desc_en' => 'Create a poster explaining three digital safety habits.'],
    ['title_ar' => 'أساسيات الأمن السيبراني', 'title_en' => 'Cybersecurity Fundamentals', 'category' => 'الأمن السيبراني', 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 35, 'description_ar' => 'فهم التهديدات الرقمية وطرق الوقاية والاستجابة بشكل مسؤول.', 'description_en' => 'Understand digital threats, prevention, and responsible response.', 'thumbnail' => 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4', 'quiz_ar' => 'الاختبار النهائي الشامل للأمن السيبراني', 'quiz_en' => 'Comprehensive Cybersecurity Final Exam', 'project_ar' => 'خطة حماية حساب', 'project_en' => 'An Account Protection Plan', 'project_desc_ar' => 'اكتب خطة عملية لحماية حساب طالب.', 'project_desc_en' => 'Write a practical plan for protecting a student account.'],
    ['title_ar' => 'تحليل البيانات بالبايثون', 'title_en' => 'Data Analysis with Python', 'category' => 'تحليل البيانات', 'level' => 'advanced', 'age_group' => '16-18', 'price' => 45, 'description_ar' => 'تنظيف البيانات وعرضها واستخلاص استنتاجات واضحة ببايثون.', 'description_en' => 'Clean, visualize, and interpret data with Python.', 'thumbnail' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71', 'quiz_ar' => 'الاختبار النهائي الشامل لتحليل البيانات', 'quiz_en' => 'Comprehensive Data Analysis Final Exam', 'project_ar' => 'تقرير بيانات مصور', 'project_en' => 'A Visual Data Report', 'project_desc_ar' => 'قدّم تقريرًا مصورًا عن مجموعة بيانات صغيرة.', 'project_desc_en' => 'Present a visual report about a small dataset.'],
    ['title_ar' => 'الروبوتات للصغار', 'title_en' => 'Robotics for Kids', 'category' => 'الروبوتات', 'level' => 'beginner', 'age_group' => '8-12', 'price' => 25, 'description_ar' => 'استكشاف الروبوتات والحساسات من خلال أنشطة آمنة وممتعة.', 'description_en' => 'Explore robots and sensors through safe, fun activities.', 'thumbnail' => 'https://images.unsplash.com/photo-1581092445129-4fe8f6c9b6a8', 'quiz_ar' => 'الاختبار النهائي الشامل للروبوتات', 'quiz_en' => 'Comprehensive Robotics Final Exam', 'project_ar' => 'تصميم روبوت ورقي', 'project_en' => 'A Paper Robot Design', 'project_desc_ar' => 'صمّم روبوتًا ورقيًا واشرح وظيفة كل جزء.', 'project_desc_en' => 'Design a paper robot and explain each part.'],
    ['title_ar' => 'Arduino وإنترنت الأشياء', 'title_en' => 'Arduino and IoT', 'category' => 'الروبوتات', 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 35, 'description_ar' => 'بناء نماذج Arduino وربط الحساسات بمشكلات يومية.', 'description_en' => 'Build Arduino prototypes and connect sensors to everyday problems.', 'thumbnail' => 'https://images.unsplash.com/photo-1518779578993-ec3579fee39f', 'quiz_ar' => 'الاختبار النهائي الشامل لأردوينو وإنترنت الأشياء', 'quiz_en' => 'Comprehensive Arduino and IoT Final Exam', 'project_ar' => 'نظام حساس بسيط', 'project_en' => 'A Simple Sensor System', 'project_desc_ar' => 'صمّم فكرة نظام حساس واشرح المدخلات والمخرجات.', 'project_desc_en' => 'Design a sensor system and explain its inputs and outputs.'],
    ['title_ar' => 'التسويق الرقمي للشباب', 'title_en' => 'Digital Marketing for Youth', 'category' => 'التسويق الإلكتروني', 'level' => 'intermediate', 'age_group' => '13-15', 'price' => 30, 'description_ar' => 'أساسيات المحتوى والجمهور والرسائل التسويقية بطريقة أخلاقية.', 'description_en' => 'Learn audiences, content, and ethical marketing messages.', 'thumbnail' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d', 'quiz_ar' => 'الاختبار النهائي الشامل للتسويق الرقمي', 'quiz_en' => 'Comprehensive Digital Marketing Final Exam', 'project_ar' => 'حملة توعوية رقمية', 'project_en' => 'A Digital Awareness Campaign', 'project_desc_ar' => 'خطط حملة توعوية من ثلاث منشورات لجمهور محدد.', 'project_desc_en' => 'Plan a three-post awareness campaign for a defined audience.'],
    ['title_ar' => 'استراتيجية نمو المشاريع الرقمية', 'title_en' => 'Digital Growth Strategy', 'category' => 'التسويق الإلكتروني', 'level' => 'advanced', 'age_group' => '16-18', 'price' => 42, 'description_ar' => 'تحليل الجمهور والقنوات وقياس نمو مشروع رقمي صغير.', 'description_en' => 'Analyze audiences, channels, and growth metrics for a digital project.', 'thumbnail' => 'https://images.unsplash.com/photo-1551632436-cbf08324fb5b', 'quiz_ar' => 'الاختبار النهائي الشامل لاستراتيجية النمو', 'quiz_en' => 'Comprehensive Digital Growth Strategy Final Exam', 'project_ar' => 'خطة نمو رقمية', 'project_en' => 'A Digital Growth Plan', 'project_desc_ar' => 'أنشئ خطة نمو بمؤشرات قياس واضحة لمدة شهر.', 'project_desc_en' => 'Create a one-month growth plan with measurable indicators.'],
];

$questions = [
    ['q' => 'ما الهدف الأساسي من تعلم هذا المجال؟', 'choices' => ['فهم المفاهيم وتطبيقها', 'حفظ كلمات بلا تطبيق', 'تجاوز قواعد السلامة'], 'answer' => 0],
    ['q' => 'ما الممارسة الأفضل للطالب؟', 'choices' => ['التجربة الآمنة والتوثيق', 'مشاركة كلمات المرور', 'نسخ المشاريع دون فهم'], 'answer' => 0],
    ['q' => 'متى يستخدم الطالب المصدر الخارجي؟', 'choices' => ['للتعلم والمراجعة بإشراف مناسب', 'لتجاوز الاختبار', 'لنشر بيانات الآخرين'], 'answer' => 0],
    ['q' => 'ما علامة الفهم الجيد؟', 'choices' => ['شرح الفكرة وبناء تطبيق بسيط', 'حفظ العنوان فقط', 'تجاهل الأخطاء'], 'answer' => 0],
    ['q' => 'ما الخطوة الأخيرة في الدورة؟', 'choices' => ['المراجعة ثم الاختبار والمشروع', 'حذف العمل', 'عدم التحقق من النتيجة'], 'answer' => 0],
];

foreach ($courseSpecs as $course) {
    $course['category_id'] = $categoryIds[$course['category']];
    $course['lessons'] = seed_lessons_for_age($course['age_group'], $ageLinks);
    $course['questions'] = $questions;
    $course['questions'][0]['q'] = 'ما الهدف الأساسي من تعلم ' . $course['title_ar'] . '؟';
    seed_content($conn, seed_course($conn, $course, $adminId), $course);
}

echo "OK: synchronized " . count($courseSpecs) . " courses with age-appropriate lessons, final quizzes, projects, and admin ownership.\n";
