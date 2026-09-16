<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
admin_csrf_enforce_on_post();

function admin_nav_items() {
    return [
        'index.php' => ['icon' => 'fa-solid fa-gauge-high', 'label' => 'لوحة التحكم'],
        'courses.php' => ['icon' => 'fa-solid fa-book-open', 'label' => 'الدورات'],
        'learning-tracks.php' => ['icon' => 'fa-solid fa-route', 'label' => 'المسارات التعليمية'],
        'course-projects.php' => ['icon' => 'fa-solid fa-diagram-project', 'label' => 'مشاريع الدورات'],
        'course-quizzes.php' => ['icon' => 'fa-solid fa-list-check', 'label' => 'اختبارات الدورات'],
        'weekly-challenges.php' => ['icon' => 'fa-solid fa-trophy', 'label' => 'التحديات الأسبوعية'],
        'users.php' => ['icon' => 'fa-solid fa-users', 'label' => 'المستخدمون'],
        'orders.php' => ['icon' => 'fa-solid fa-cart-shopping', 'label' => 'الطلبات'],
        'enrollments.php' => ['icon' => 'fa-solid fa-graduation-cap', 'label' => 'التسجيلات'],
        'payments.php' => ['icon' => 'fa-solid fa-credit-card', 'label' => 'المدفوعات'],
        'analytics.php' => ['icon' => 'fa-solid fa-chart-line', 'label' => 'التقارير'],
        'settings.php' => ['icon' => 'fa-solid fa-gear', 'label' => 'الإعدادات'],
    ];
}

function render_admin_layout_start($title, $active = 'index.php') {
    $user = current_user();
    ?>
<!doctype html>
<html lang="<?= e(app_lang()) ?>" dir="<?= e(app_dir()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> - <?= e(APP_NAME_AR) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
  <div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
      <a href="index.php" class="admin-brand">
        <i class="fa-solid fa-seedling"></i>
        <span><?= e(APP_NAME_AR) ?></span>
      </a>
      <nav class="admin-nav">
        <?php foreach (admin_nav_items() as $href => $item): ?>
          <a href="<?= e($href) ?>" class="admin-nav-link <?= $active === $href ? 'is-active' : '' ?>">
            <i class="<?= e($item['icon']) ?>"></i>
            <span><?= e($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>

    <div class="admin-main">
      <header class="admin-topbar">
        <button type="button" class="admin-menu-btn" id="admin-menu-btn" aria-label="menu">
          <i class="fa-solid fa-bars"></i>
        </button>
        <h1 class="admin-title"><?= e($title) ?></h1>
        <div class="admin-user">
          <a href="../index.php?student_preview=1" class="btn btn-outline btn-sm">معاينة كطالب</a>
          <a href="../index.php?student_preview=0" class="btn btn-outline btn-sm">إنهاء المعاينة</a>
          <span><?= e($user['full_name'] ?? '') ?></span>
          <a href="../logout.php" class="btn btn-outline btn-sm">تسجيل الخروج</a>
        </div>
      </header>
      <main class="admin-content">
<?php
}

function render_admin_layout_end() {
    ?>
      </main>
    </div>
  </div>
<script>
(function () {
  var btn = document.getElementById('admin-menu-btn');
  var sidebar = document.getElementById('admin-sidebar');
  if (!btn || !sidebar) return;
  btn.addEventListener('click', function () {
    sidebar.classList.toggle('is-open');
  });
})();
(function () {
  var forms = document.querySelectorAll('form[method="post"], form[method="POST"]');
  if (!forms || !forms.length) return;
  var token = <?= json_encode(csrf_token(), JSON_UNESCAPED_UNICODE) ?>;
  for (var i = 0; i < forms.length; i++) {
    var f = forms[i];
    if (f.querySelector('input[name="csrf_token"]')) continue;
    var inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = 'csrf_token';
    inp.value = token;
    f.appendChild(inp);
  }
})();
</script>
</body>
</html>
<?php
}
