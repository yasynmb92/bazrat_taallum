<?php
/** @var array $course */
$isAr = app_lang() === 'ar';
$title = $isAr ? $course['title_ar'] : $course['title_en'];
$desc = $isAr ? ($course['description_ar'] ?? '') : ($course['description_en'] ?? '');
$cat = $isAr ? ($course['category_ar'] ?? '') : ($course['category_en'] ?? '');
$track = $isAr ? ($course['track_name_ar'] ?? '') : ($course['track_name_en'] ?? '');
$ageGroup = trim((string)($course['age_group'] ?? ''));
$thumb = !empty($course['thumbnail']) ? $course['thumbnail'] : '';
$instructor = $course['instructor_name'] ?? '';
$lessons = (int)($course['lesson_count'] ?? 0);
$rating = $course['rating_avg'] ?? null;
$rCount = (int)($course['rating_count'] ?? 0);
$students = (int)($course['students_count'] ?? 0);
$pricing = course_effective_pricing($course);
$priceList = $pricing['list_price'];
$priceFinal = $pricing['final_price'];
$level = $course['level'] ?? 'beginner';
$levelKey = 'level_' . $level;
$levelLabel = t($levelKey) !== $levelKey ? t($levelKey) : $level;
$lastUpdated = course_last_updated_label($course);
?>
<article class="course-card">
  <a class="course-card-media" href="<?= e(course_url($course)) ?>">
    <?php if ($thumb): ?>
      <img src="<?= e($thumb) ?>" alt="" loading="lazy">
    <?php else: ?>
      <div class="course-card-placeholder"></div>
    <?php endif; ?>
  </a>
  <div class="course-card-body">
    <?php if ($cat !== ''): ?><span class="course-card-badge"><?= e($cat) ?></span><?php endif; ?>
    <?php if ($track !== ''): ?><span class="course-card-badge"><?= e($track) ?></span><?php endif; ?>
    <h3 class="course-card-title">
      <a href="<?= e(course_url($course)) ?>"><?= e($title) ?></a>
    </h3>
    <?php if ($instructor !== ''): ?>
      <p class="course-card-instructor"><?= e(t('instructor')) ?>: <?= e($instructor) ?></p>
    <?php endif; ?>
    <?php
      $plain = strip_tags((string)$desc);
    $short = function_exists('mb_strimwidth')
        ? mb_strimwidth($plain, 0, 120, '…', 'UTF-8')
        : (strlen($plain) > 120 ? substr($plain, 0, 117) . '...' : $plain);
    ?>
    <p class="course-card-desc"><?= e($short) ?></p>
    <div class="course-card-meta">
      <?php if ($rating !== null && (float)$rating > 0 && $rCount > 0): ?>
        <span class="course-card-rating" title="<?= e(t('reviews')) ?>">
          <i class="fa-solid fa-star"></i> <?= e(number_format((float)$rating, 1)) ?>
          <span class="muted">(<?= (int)$rCount ?>)</span>
        </span>
      <?php else: ?>
        <span class="course-card-rating muted"><?= e(t('new_course')) ?></span>
      <?php endif; ?>
      <span class="muted"><?= (int)$lessons ?> <?= e(t('lessons_count')) ?></span>
      <?php
        $durCard = trim((string)($course['total_duration_label'] ?? ''));
      if ($durCard !== ''): ?>
        <span class="muted" title="<?= e(t('course_total_duration')) ?>"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= e($durCard) ?></span>
      <?php endif; ?>
      <?php if ($students > 0): ?>
        <span class="muted"><?= number_format($students) ?> <?= e(t('students_enrolled')) ?></span>
      <?php endif; ?>
      <?php if ($ageGroup !== ''): ?>
        <span class="muted"><?= e(t('age_group')) ?>: <?= e($ageGroup) ?></span>
      <?php endif; ?>
      <?php if ($lastUpdated !== ''): ?>
        <span class="muted" title="<?= e(t('course_last_updated')) ?>"><i class="fa-regular fa-calendar"></i> <?= e($lastUpdated) ?></span>
      <?php endif; ?>
    </div>
    <div class="course-card-footer">
      <span class="course-card-level"><?= e($levelLabel) ?></span>
      <span class="course-card-price">
        <?php if ($priceFinal <= 0.00001): ?>
          <?= e(t('free')) ?>
        <?php elseif ($pricing['has_discount'] && $priceList > $priceFinal): ?>
          <span class="price-was">$<?= number_format($priceList, 2) ?></span> $<?= number_format($priceFinal, 2) ?>
        <?php else: ?>
          $<?= number_format($priceFinal, 2) ?>
        <?php endif; ?>
      </span>
      <a class="btn btn-primary btn-sm" href="<?= e(course_url($course)) ?>"><?= e(t('details')) ?></a>
    </div>
  </div>
</article>
