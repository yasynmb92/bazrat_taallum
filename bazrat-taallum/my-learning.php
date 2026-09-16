<?php
/** صفحة «تعلّمي»: قائمة التسجيلات النشطة مع نسبة التقدّم ورابط متابعة التعلّم. */
require_once __DIR__ . '/includes/header.php';
require_login();
$enrollments = my_enrollments(current_user()['id']);
$isAr = app_lang() === 'ar';
?>
<section class="container my-learning-page">
  <h1 class="page-title"><?= e(t('my_learning')) ?></h1>
  <?php if (!$enrollments): ?>
    <div class="empty-learning">
      <p class="muted"><?= e(t('no_enrollments')) ?></p>
      <a class="btn btn-primary" href="<?= APP_URL ?>/courses.php"><?= e(t('explore_courses')) ?></a>
    </div>
  <?php else: ?>
    <div class="learning-grid">
      <?php foreach ($enrollments as $en):
          $cid = (int)$en['course_id'];
          $first = get_first_lesson_id($cid);
          $title = $isAr ? $en['title_ar'] : $en['title_en'];
          $thumb = $en['thumbnail'] ?? '';
          ?>
        <article class="learning-card">
          <a class="learning-card-media" href="<?= APP_URL ?>/course.php?id=<?= $cid ?>">
            <?php if ($thumb): ?>
              <img src="<?= e($thumb) ?>" alt="" loading="lazy">
            <?php else: ?>
              <div class="course-card-placeholder"></div>
            <?php endif; ?>
          </a>
          <div class="learning-card-body">
            <h2 class="learning-card-title"><a href="<?= APP_URL ?>/course.php?id=<?= $cid ?>"><?= e($title) ?></a></h2>
            <div class="learning-card-meta">
              <span class="badge badge-soft"><?= e($en['status']) ?></span>
              <span class="muted"><?= (int)$en['progress'] ?>% <?= e(t('progress_label')) ?></span>
            </div>
            <?php if ($first): ?>
              <a class="btn btn-success btn-block" href="<?= APP_URL ?>/learn.php?course_id=<?= $cid ?>&lesson_id=<?= $first ?>">
                <i class="fa-solid fa-play"></i> <?= e(t('continue_learning')) ?>
              </a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
