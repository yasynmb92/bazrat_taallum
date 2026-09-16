<?php
/**
 * هيكل الصفحة العامة: شريط التنقل، البحث، روابط تعلّمي / البروفايل / الدورات السريعة.
 */
require_once __DIR__ . '/functions.php';
$lang = app_lang();
$user = current_user();

if ($user && ($user['role'] ?? '') === 'admin') {
    if (isset($_GET['student_preview'])) {
        set_admin_student_preview_mode((string)$_GET['student_preview'] === '1');
    }
    if (!is_admin_student_preview_mode()) {
        header('Location: ' . APP_URL . '/admin/index.php');
        exit;
    }
}

$navCourses = ($user && ($user['role'] ?? '') !== 'admin') ? fetch_user_nav_courses((int)$user['id'], 8) : [];
?>
<!doctype html>
<html lang="<?= e($lang) ?>" dir="<?= e(app_dir()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(t('platform_name')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=IBM+Plex+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
<nav class="navbar">
  <div class="container nav-inner">
    <a class="brand" href="<?= e(route_url()) ?>">
      <img src="<?= APP_URL ?>/assets/images/logo.png" alt="">
      <span><?= e(t('platform_name')) ?></span>
    </a>

    <form class="nav-search" method="get" action="<?= e(route_url('courses/search')) ?>" role="search">
      <input type="search" name="q" placeholder="<?= e(t('search_placeholder')) ?>" aria-label="<?= e(t('search')) ?>">
      <button type="submit" aria-label="<?= e(t('search')) ?>"><i class="fa-solid fa-magnifying-glass"></i></button>
    </form>

    <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="nav-menu" id="nav-toggle">
      <i class="fa-solid fa-bars"></i>
    </button>

    <div class="nav-menu" id="nav-menu">
      <div class="menu">
        <a href="<?= e(route_url()) ?>"><?= e(t('home')) ?></a>
        <a href="<?= e(route_url('courses')) ?>"><?= e(t('courses')) ?></a>
        <?php if ($user): ?>
          <a href="<?= APP_URL ?>/my-learning.php"><?= e(t('my_learning')) ?></a>
          <a href="<?= APP_URL ?>/profile.php"><?= e(t('my_profile')) ?></a>
          <?php if ($navCourses): ?>
          <div class="nav-dropdown-wrap">
            <button type="button" class="nav-dropdown-btn" id="nav-courses-btn" aria-expanded="false" aria-haspopup="true">
              <?= e(t('nav_my_courses')) ?> <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="nav-dropdown" id="nav-courses-panel" hidden>
              <?php $isArNav = app_lang() === 'ar'; ?>
              <?php foreach ($navCourses as $nc):
                  $nt = $isArNav ? $nc['title_ar'] : $nc['title_en'];
                  $href = $nc['first_lesson_id']
                    ? (APP_URL . '/learn.php?course_id=' . (int)$nc['course_id'] . '&lesson_id=' . (int)$nc['first_lesson_id'])
                    : (APP_URL . '/course.php?id=' . (int)$nc['course_id']);
                  ?>
                <a class="nav-dropdown-item" href="<?= e($href) ?>">
                  <span class="nav-dropdown-title"><?= e($nt) ?></span>
                  <span class="nav-dropdown-meta"><?= (int)$nc['progress'] ?>% <?= e(t('progress_label')) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($user && $user['role'] === 'admin'): ?>
          <a href="<?= APP_URL ?>/admin/index.php"><?= e(t('admin_panel')) ?></a>
        <?php endif; ?>
      </div>
      <div class="actions">
        <a class="btn btn-ghost" href="?lang=<?= $lang === 'ar' ? 'en' : 'ar' ?>">
          <?= $lang === 'ar' ? e(t('english')) : e(t('arabic')) ?>
        </a>
        <a class="btn btn-ghost nav-cart" href="<?= APP_URL ?>/cart.php" title="<?= e(t('cart')) ?>"><i class="fa-solid fa-cart-shopping"></i></a>
        <?php if ($user): ?>
          <a class="nav-user" href="<?= APP_URL ?>/profile.php" title="<?= e(t('my_profile')) ?>"><?= e(t('welcome')) ?>, <?= e($user['full_name']) ?></a>
          <a class="btn btn-outline btn-sm" href="<?= APP_URL ?>/logout.php"><?= e(t('logout')) ?></a>
        <?php else: ?>
          <a class="btn btn-outline btn-sm" href="<?= APP_URL ?>/login.php"><?= e(t('login')) ?></a>
          <a class="btn btn-primary btn-sm" href="<?= APP_URL ?>/register.php"><?= e(t('register')) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
</header>
<script>
(function(){
  var btn = document.getElementById('nav-toggle');
  var menu = document.getElementById('nav-menu');
  if (!btn || !menu) return;
  btn.addEventListener('click', function(){
    var open = menu.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();
(function(){
  var b = document.getElementById('nav-courses-btn');
  var p = document.getElementById('nav-courses-panel');
  if (!b || !p) return;
  b.addEventListener('click', function(e){
    e.stopPropagation();
    var open = p.hidden;
    p.hidden = !open;
    b.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  document.addEventListener('click', function(){ p.hidden = true; b.setAttribute('aria-expanded', 'false'); });
  p.addEventListener('click', function(e){ e.stopPropagation(); });
})();
</script>
