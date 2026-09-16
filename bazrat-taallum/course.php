<?php
/** صفحة تفاصيل الدورة: شراء، معاينة، منهج، وتقييمات. */
require_once __DIR__ . '/includes/functions.php';
csrf_enforce_on_post();
$id = (int)($_GET['id'] ?? 0);
$slug = trim((string)($_GET['slug'] ?? ''));
$course = $slug !== '' ? fetch_course_by_slug($slug) : fetch_course_detail($id);

if (!$course) {
    echo '<section class="container"><div class="alert alert-danger">' . e(t('course_not_found')) . '</div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$isAdmin = is_logged_in() && (current_user()['role'] ?? '') === 'admin';
if (!(int)$course['is_published'] && !$isAdmin) {
    echo '<section class="container"><div class="alert alert-danger">' . e(t('course_not_found')) . '</div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$id = (int)$course['id'];
$lessons = fetch_lessons($id);
$uid = is_logged_in() ? (int)current_user()['id'] : 0;
$enrolled = $uid && has_access($uid, $id);
$completedLessonIds = ($enrolled && $uid) ? fetch_completed_lesson_ids($uid, $id) : [];
$reviews = fetch_course_reviews($id, 8);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in() && !$enrolled) {
    $uid = (int)current_user()['id'];
    if (isset($_POST['enroll_free'])) {
        $pr = course_effective_pricing($course);
        if ($pr['final_price'] <= 0.00001) {
            enroll_user_in_course($uid, $id);
            $fl = get_first_lesson_id($id);
            if ($fl) {
                header('Location: ' . APP_URL . '/learn.php?course_id=' . $id . '&lesson_id=' . $fl);
            } else {
                header('Location: ' . route_url('course/' . course_slug($course) . '?enrolled=1'));
            }
            exit;
        }
    }
    add_to_cart($uid, $id);
    header('Location: ' . route_url('course/' . course_slug($course) . '?cart=1'));
    exit;
}

$isAr = app_lang() === 'ar';
$title = $isAr ? $course['title_ar'] : $course['title_en'];
$description = $isAr ? $course['description_ar'] : $course['description_en'];
$cat = $isAr ? ($course['category_ar'] ?? '') : ($course['category_en'] ?? '');
$track = $isAr ? ($course['track_name_ar'] ?? '') : ($course['track_name_en'] ?? '');
$ageGroup = trim((string)($course['age_group'] ?? ''));
$instructor = $course['instructor_name'] ?? '';
$thumb = $course['thumbnail'] ?? '';
$pricing = course_effective_pricing($course);
$price = $pricing['list_price'];
$finalPrice = $pricing['final_price'];
$lessonCount = (int)($course['lesson_count'] ?? 0);
$studentsCount = (int)($course['students_count'] ?? 0);
$ratingAvg = $course['rating_avg'] ?? null;
$ratingCount = (int)($course['rating_count'] ?? 0);
$levelKey = 'level_' . ($course['level'] ?? 'beginner');
$levelLabel = t($levelKey) !== $levelKey ? t($levelKey) : ($course['level'] ?? '');
$firstLessonId = get_first_lesson_id($id);
$courseTotalDurationMin = course_total_duration_minutes_from_lessons($lessons);
$courseDurationLabel = format_course_duration_display($courseTotalDurationMin, $isAr);
$courseLastUpdated = course_last_updated_label($course);
require_once __DIR__ . '/includes/header.php';
?>
<section class="course-hero"<?= $thumb ? ' style="--course-hero-img:url(\'' . e($thumb) . '\')"' : '' ?>>
  <div class="container course-hero-inner">
    <nav class="course-breadcrumb">
      <a href="<?= e(route_url('courses')) ?>"><?= e(t('courses')) ?></a>
      <?php if ($cat !== ''): ?>
        <span class="sep">/</span><span><?= e($cat) ?></span>
      <?php endif; ?>
      <?php if ($track !== ''): ?>
        <span class="sep">/</span><span><?= e($track) ?></span>
      <?php endif; ?>
    </nav>
    <h1 class="course-hero-title"><?= e($title) ?></h1>
    <p class="course-hero-sub"><?= e(strip_tags((string)$description)) ?></p>
    <div class="course-hero-stats">
      <?php if ($ratingAvg !== null && (float)$ratingAvg > 0 && $ratingCount > 0): ?>
        <span><i class="fa-solid fa-star"></i> <?= e(number_format((float)$ratingAvg, 1)) ?> (<?= $ratingCount ?>)</span>
      <?php endif; ?>
      <?php if ($studentsCount > 0): ?>
        <span><?= number_format($studentsCount) ?> <?= e(t('students_enrolled')) ?></span>
      <?php endif; ?>
      <span><?= $lessonCount ?> <?= e(t('lessons_count')) ?></span>
      <?php if ($ageGroup !== ''): ?>
        <span><i class="fa-solid fa-user-graduate"></i> <?= e(t('age_group')) ?>: <?= e($ageGroup) ?></span>
      <?php endif; ?>
      <?php if ($courseDurationLabel !== ''): ?>
        <span title="<?= e(t('course_total_duration')) ?>"><i class="fa-regular fa-clock" aria-hidden="true"></i> <?= e($courseDurationLabel) ?></span>
      <?php endif; ?>
      <?php if ($courseLastUpdated !== ''): ?>
        <span title="<?= e(t('course_last_updated')) ?>"><i class="fa-regular fa-calendar"></i> <?= e($courseLastUpdated) ?></span>
      <?php endif; ?>
      <span><?= e($levelLabel) ?></span>
    </div>
  </div>
</section>

<div class="container course-detail-grid">
  <main class="course-detail-main">
    <?php if (isset($_GET['cart'])): ?>
      <div class="alert alert-success"><?= e(t('added_to_cart')) ?></div>
    <?php endif; ?>

    <section class="course-block">
      <h2><?= e(t('what_you_learn')) ?></h2>
      <ul class="check-list">
        <?php
          $lines = preg_split('/\r\n|\r|\n/', strip_tags((string)$description));
        $shown = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            echo '<li>' . e($line) . '</li>';
            $shown++;
            if ($shown >= 6) {
                break;
            }
        }
        if ($shown === 0) {
            echo '<li>' . e(t('fallback_learn_1')) . '</li>';
            echo '<li>' . e(t('fallback_learn_2')) . '</li>';
            echo '<li>' . e(t('fallback_learn_3')) . '</li>';
        }
        ?>
      </ul>
    </section>

    <section class="course-block" id="curriculum">
      <h2><?= e(t('course_content')) ?></h2>
      <p class="muted small"><?= e(t('free_preview_curriculum_note')) ?></p>
      <ol class="curriculum-list curriculum-list--detail">
        <?php foreach ($lessons as $i => $l):
            $lt = $isAr ? $l['title_ar'] : $l['title_en'];
            $hasVideo = trim((string)($l['video_url'] ?? '')) !== '';
            $hasReading = trim(strip_tags((string)($isAr ? ($l['content_ar'] ?? '') : ($l['content_en'] ?? '')))) !== '';
            $lid = (int)$l['id'];
            $isPreview = $i < FREE_PREVIEW_LESSON_COUNT;
            $unlocked = $enrolled
                ? enrolled_lesson_unlocked($lessons, $i, $completedLessonIds)
                : $isPreview;
            ?>
          <li class="<?= !$unlocked ? 'curriculum-item--locked' : '' ?>">
            <span class="curriculum-idx"><?= (int)$l['sort_order'] ?></span>
            <span class="curriculum-name"><?= e($lt) ?></span>
            <?php if ($isPreview && !$enrolled): ?>
              <span class="curriculum-badge curriculum-badge--preview"><?= e(t('free_preview_short')) ?></span>
            <?php endif; ?>
            <?php if (!$hasVideo && $hasReading): ?>
              <span class="curriculum-badge curriculum-badge--read"><?= e(t('reading_lesson')) ?></span>
            <?php endif; ?>
            <?php if ($unlocked): ?>
              <a class="curriculum-watch" href="<?= APP_URL ?>/learn.php?course_id=<?= $id ?>&lesson_id=<?= $lid ?>">
                <?php if ($hasVideo): ?>
                  <?= e(t('watch')) ?> <i class="fa-solid fa-play"></i>
                <?php else: ?>
                  <?= e(t('open_lesson')) ?> <i class="fa-solid fa-book-open"></i>
                <?php endif; ?>
              </a>
            <?php elseif ($enrolled): ?>
              <span class="curriculum-locked"><i class="fa-solid fa-lock"></i> <?= e(t('complete_previous_lesson')) ?></span>
            <?php else: ?>
              <span class="curriculum-locked"><i class="fa-solid fa-lock"></i> <?= e(t('enroll_to_watch')) ?></span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
      <?php if (!$lessons): ?>
        <p class="muted"><?= e(t('no_lessons')) ?></p>
      <?php endif; ?>
    </section>

    <?php if ($instructor !== ''): ?>
      <section class="course-block instructor-block">
        <h2><?= e(t('instructor')) ?></h2>
        <div class="instructor-row">
          <div class="instructor-avatar"><?= e(function_exists('mb_substr') ? mb_substr($instructor, 0, 1, 'UTF-8') : substr($instructor, 0, 1)) ?></div>
          <div>
            <strong><?= e($instructor) ?></strong>
            <p class="muted"><?= e(t('instructor_bio_placeholder')) ?></p>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($reviews): ?>
      <section class="course-block">
        <h2><?= e(t('reviews')) ?></h2>
        <ul class="review-list">
          <?php foreach ($reviews as $r):
              $cmt = $isAr ? ($r['comment_ar'] ?? '') : ($r['comment_en'] ?? '');
              ?>
            <li>
              <div class="review-head">
                <strong><?= e($r['reviewer_name'] ?? '') ?></strong>
                <span class="review-stars"><?php
                  $stars = min(5, max(0, (int)$r['rating']));
            echo str_repeat('★', $stars) . str_repeat('☆', 5 - $stars);
            ?></span>
              </div>
              <?php if (trim(strip_tags((string)$cmt)) !== ''): ?>
                <p><?= e($cmt) ?></p>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>
  </main>

  <aside class="course-sidebar">
    <div class="course-buy-card">
      <?php if ($thumb): ?>
        <img class="course-buy-thumb" src="<?= e($thumb) ?>" alt="">
      <?php endif; ?>
      <div class="course-buy-inner">
        <p class="course-buy-price">
          <?php if ($finalPrice <= 0.00001): ?>
            <?= e(t('free')) ?>
          <?php else: ?>
            <?php if ($pricing['has_discount'] && $price > $finalPrice): ?>
              <span class="price-was">$<?= number_format($price, 2) ?></span>
              <span class="price-now">$<?= number_format($finalPrice, 2) ?></span>
            <?php else: ?>
              $<?= number_format($finalPrice, 2) ?>
            <?php endif; ?>
          <?php endif; ?>
        </p>
        <ul class="course-buy-perks">
          <?php if ($courseDurationLabel !== ''): ?>
            <li><i class="fa-solid fa-check"></i> <?= e(t('course_total_duration')) ?>: <?= e($courseDurationLabel) ?></li>
          <?php endif; ?>
          <?php if ($courseLastUpdated !== ''): ?>
            <li><i class="fa-solid fa-check"></i> <?= e(t('course_last_updated')) ?>: <?= e($courseLastUpdated) ?></li>
          <?php endif; ?>
          <li><i class="fa-solid fa-check"></i> <?= e(t('full_lifetime_access')) ?></li>
          <li><i class="fa-solid fa-check"></i> <?= e(t('learn_at_pace')) ?></li>
          <li><i class="fa-solid fa-check"></i> <?= e(t('mobile_tv')) ?></li>
        </ul>
        <?php if ($enrolled && $firstLessonId): ?>
          <a class="btn btn-success btn-block btn-lg" href="<?= APP_URL ?>/learn.php?course_id=<?= $id ?>&lesson_id=<?= $firstLessonId ?>">
            <i class="fa-solid fa-play"></i> <?= e(t('start_learning')) ?>
          </a>
        <?php elseif (is_logged_in()): ?>
          <?php if ($finalPrice <= 0.00001): ?>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="enroll_free" value="1">
              <button class="btn btn-success btn-block btn-lg" type="submit"><?= e(t('enroll_free')) ?></button>
            </form>
          <?php else: ?>
            <form method="post">
              <?= csrf_field() ?>
              <button class="btn btn-primary btn-block btn-lg" type="submit"><?= e(t('add_to_cart')) ?></button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <a class="btn btn-primary btn-block btn-lg" href="<?= APP_URL ?>/login.php"><?= e(t('login')) ?></a>
          <p class="muted small"><?= e(t('enroll_hint')) ?></p>
        <?php endif; ?>
        <a class="btn btn-outline btn-block" href="<?= e(route_url('courses')) ?>"><?= e(t('view_all_courses')) ?></a>
      </div>
    </div>
  </aside>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
