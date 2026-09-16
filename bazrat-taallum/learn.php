<?php
/**
 * صفحة مشاهدة الدرس: فيديو/قراءة، قائمة المنهج، إكمال الدرس، وتحديث نسبة التقدّم في التسجيل.
 */
require_once __DIR__ . '/includes/functions.php';
csrf_enforce_on_post();

$courseId = (int)($_GET['course_id'] ?? 0);
$lessonId = (int)($_GET['lesson_id'] ?? 0);

$course = fetch_course_detail($courseId);
if (!$course) {
    http_response_code(404);
    echo '<section class="container learn-page"><div class="alert alert-danger">' . e(t('course_not_found')) . '</div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$viewer = is_logged_in() ? current_user() : null;
$isAdminViewer = $viewer && ($viewer['role'] ?? '') === 'admin';
if (!(int)$course['is_published'] && !$isAdminViewer) {
    http_response_code(404);
    echo '<section class="container learn-page"><div class="alert alert-danger">' . e(t('course_not_found')) . '</div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$lessons = fetch_lessons($courseId);
if (!$lessons) {
    echo '<section class="container learn-page"><div class="alert alert-danger">' . e(t('no_lessons')) . '</div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$userId = $viewer ? (int)$viewer['id'] : 0;
$completedIds = ($userId && has_access($userId, $courseId)) ? fetch_completed_lesson_ids($userId, $courseId) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    require_login();
    $viewer = current_user();
    $userId = (int)$viewer['id'];
    $postCourse = (int)($_POST['course_id'] ?? 0);
    $postLesson = (int)($_POST['lesson_id'] ?? 0);
    $completedIds = has_access($userId, $postCourse) ? fetch_completed_lesson_ids($userId, $postCourse) : [];
    $acc = learn_lesson_access_state($viewer, $course, $postLesson, $lessons, $completedIds);
    if ($acc['ok'] && ($acc['mode'] ?? '') === 'enrolled' && $postCourse === $courseId) {
        mark_lesson_completed($userId, $postLesson, $postCourse);
    }
    header('Location: ' . APP_URL . '/learn.php?course_id=' . $courseId . '&lesson_id=' . $postLesson);
    exit;
}

$current = null;
foreach ($lessons as $l) {
    if ((int)$l['id'] === $lessonId) {
        $current = $l;
        break;
    }
}
if (!$current) {
    $current = $lessons[0];
    $lessonId = (int)$current['id'];
}

$access = learn_lesson_access_state($viewer, $course, $lessonId, $lessons, $completedIds);
if (!$access['ok']) {
    if (($access['reason'] ?? '') === 'purchase_required') {
        header('Location: ' . route_url('course/' . course_slug($course)));
        exit;
    }
    $msg = t('lesson_locked');
    if (($access['reason'] ?? '') === 'unpublished') {
        $msg = t('course_not_found');
    }
    echo '<section class="container learn-page"><div class="alert alert-danger">' . e($msg) . '</div>';
    echo '<p class="muted"><a href="' . e(route_url('course/' . course_slug($course))) . '">' . e(t('details')) . '</a></p></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz_attempt'])) {
    require_login();
    $viewer = current_user();
    $uid = (int)($viewer['id'] ?? 0);
    $postCourse = (int)($_POST['course_id'] ?? 0);
    $postQuiz = (int)($_POST['quiz_id'] ?? 0);
    if ($postCourse !== $courseId || $uid <= 0) {
        header('Location: ' . APP_URL . '/learn.php?course_id=' . $courseId . '&lesson_id=' . $lessonId . '&quiz_status=invalid');
        exit;
    }
    $completedForQuiz = has_access($uid, $courseId) ? fetch_completed_lesson_ids($uid, $courseId) : [];
    $accQuiz = learn_lesson_access_state($viewer, $course, $lessonId, $lessons, $completedForQuiz);
    if (($accQuiz['mode'] ?? '') !== 'enrolled') {
        header('Location: ' . APP_URL . '/learn.php?course_id=' . $courseId . '&lesson_id=' . $lessonId . '&quiz_status=locked');
        exit;
    }
    $cq = fetch_active_course_quiz($courseId);
    if (!$cq || (int)$cq['id'] !== $postQuiz) {
        header('Location: ' . APP_URL . '/learn.php?course_id=' . $courseId . '&lesson_id=' . $lessonId . '&quiz_status=invalid');
        exit;
    }
    $questions = json_decode((string)($cq['questions_json'] ?? '[]'), true);
    if (!is_array($questions)) {
        $questions = [];
    }
    $totalQ = count($questions);
    $conn = db();
    $maxAttempts = (int)($cq['max_attempts'] ?? 3);
    $passScore = (int)($cq['pass_score'] ?? 70);
    $qid = (int)$cq['id'];
    $cntSt = $conn->prepare('SELECT COUNT(*) AS c FROM quiz_attempts WHERE quiz_id = ? AND user_id = ?');
    $cntSt->bind_param('ii', $qid, $uid);
    $cntSt->execute();
    $attemptCount = (int)($cntSt->get_result()->fetch_assoc()['c'] ?? 0);
    if ($attemptCount >= $maxAttempts) {
        header('Location: ' . APP_URL . '/learn.php?course_id=' . $courseId . '&lesson_id=' . $lessonId . '&quiz_status=maxed');
        exit;
    }
    $correct = 0;
    $answersChosen = [];
    for ($i = 0; $i < $totalQ; $i++) {
        $qrow = $questions[$i] ?? [];
        $correctIdx = (int)($qrow['answer'] ?? -1);
        $choiceIdx = (int)($_POST['q_' . $i] ?? -1);
        $answersChosen[] = $choiceIdx;
        if ($choiceIdx === $correctIdx) {
            $correct++;
        }
    }
    $scorePct = $totalQ > 0 ? (int)round(($correct / $totalQ) * 100) : 0;
    $qStatus = $scorePct >= $passScore ? 'passed' : 'failed';
    $answersJson = json_encode($answersChosen, JSON_UNESCAPED_UNICODE);
    $ins = $conn->prepare('INSERT INTO quiz_attempts (quiz_id, user_id, score, answers_json, status) VALUES (?, ?, ?, ?, ?)');
    $ins->bind_param('iiiss', $qid, $uid, $scorePct, $answersJson, $qStatus);
    $ins->execute();
    header('Location: ' . APP_URL . '/learn.php?course_id=' . $courseId . '&lesson_id=' . $lessonId . '&quiz_status=' . $qStatus);
    exit;
}

$learnMode = $access['mode'] ?? 'preview';
$showCompleteBtn = ($learnMode === 'enrolled');
$courseProject = fetch_active_course_project($courseId);
$courseQuiz = fetch_active_course_quiz($courseId);
$lastQuizAttempt = ($courseQuiz && $userId > 0) ? latest_quiz_attempt((int)$courseQuiz['id'], $userId) : null;
$quizQuestions = [];
if ($courseQuiz) {
    $decoded = json_decode((string)($courseQuiz['questions_json'] ?? '[]'), true);
    $quizQuestions = is_array($decoded) ? $decoded : [];
}

/** نسبة الإنجاز في جدول enrollments (تُحدَّث تلقائياً عند تسجيل إكمال درس). */
$enrollmentProgressPct = ($userId > 0 && $learnMode === 'enrolled')
    ? enrollment_progress_percent($userId, $courseId)
    : 0;

$isAr = app_lang() === 'ar';
$quizStatus = trim((string)($_GET['quiz_status'] ?? ''));
$quizFeedback = '';
if ($quizStatus === 'passed') {
    $quizFeedback = $isAr ? 'تم اجتياز الاختبار بنجاح!' : 'You passed the quiz.';
} elseif ($quizStatus === 'failed') {
    $quizFeedback = $isAr ? 'لم يتم اجتياز الاختبار بعد.' : 'You did not pass the quiz.';
} elseif ($quizStatus === 'maxed') {
    $quizFeedback = $isAr ? 'تم تجاوز عدد المحاولات المسموح بها.' : 'Maximum attempts reached.';
} elseif ($quizStatus === 'locked' || $quizStatus === 'invalid') {
    $quizFeedback = $isAr ? 'تعذّر تنفيذ الطلب.' : 'Request could not be completed.';
}
$courseTitle = $isAr ? $course['title_ar'] : $course['title_en'];
$lessonTitle = $isAr ? $current['title_ar'] : $current['title_en'];
$videoUrl = trim((string)($current['video_url'] ?? ''));
$useNativeVideo = is_direct_video_file_url($videoUrl);
$embed = $useNativeVideo ? '' : video_embed_url($videoUrl);
$isEmbeddableVideo = $useNativeVideo || ($embed !== null && $embed !== '' && (
  $embed !== $videoUrl
  || preg_match('#^https://(?:www\.)?youtube\.com/embed/#i', $embed)
  || preg_match('#^https://player\.vimeo\.com/video/#i', $embed)
));
$body = $isAr ? ($current['content_ar'] ?? '') : ($current['content_en'] ?? '');
$hasVideo = $videoUrl !== '';
$hasReading = trim(strip_tags((string)$body)) !== '';
$readingOnly = !$hasVideo && $hasReading;
$timedSteps = parse_lesson_timed_transcript($current['timed_transcript_json'] ?? null);
$ytId = $hasVideo ? youtube_video_id_from_url($videoUrl) : null;
$showSyncedTranscript = count($timedSteps) > 0 && $hasVideo && ($useNativeVideo || $ytId);
require_once __DIR__ . '/includes/header.php';
?>
<section class="learn-layout">
  <div class="learn-main">
    <div class="learn-breadcrumb">
      <?php if (is_logged_in()): ?>
        <a href="<?= e(route_url('my-learning.php')) ?>"><?= e(t('my_learning')) ?></a>
        <span class="sep">/</span>
      <?php else: ?>
        <a href="<?= e(route_url('courses')) ?>"><?= e(t('courses')) ?></a>
        <span class="sep">/</span>
      <?php endif; ?>
      <a href="<?= e(route_url('course/' . course_slug($course))) ?>"><?= e($courseTitle) ?></a>
    </div>
    <?php if ($learnMode === 'preview'): ?>
      <div class="alert alert-success learn-preview-banner"><?= e(t('free_preview_banner')) ?></div>
    <?php endif; ?>

    <?php if (!$readingOnly): ?>
    <div class="video-shell<?= $showSyncedTranscript && $ytId && !$useNativeVideo ? ' video-shell--yt' : '' ?>">
      <?php if ($showSyncedTranscript && $ytId && !$useNativeVideo): ?>
        <div id="lesson-yt-player" class="video-frame video-frame--yt" data-videoid="<?= e($ytId) ?>"></div>
      <?php elseif ($useNativeVideo && $videoUrl !== ''): ?>
        <video id="lesson-sync-video" class="video-frame video-frame--native" controls playsinline preload="metadata" src="<?= e($videoUrl) ?>"></video>
      <?php elseif ($isEmbeddableVideo && $embed): ?>
        <iframe class="video-frame" src="<?= e($embed) ?>" title="<?= e($lessonTitle) ?>"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen></iframe>
      <?php elseif ($videoUrl !== ''): ?>
        <div class="video-placeholder">
          <p><?= e(app_lang() === 'ar' ? 'هذه محاضرة خارجية. افتح الرابط لمشاهدتها على المنصة الأصلية.' : 'This is an external lecture. Open the link on the original platform.') ?></p>
          <a class="btn btn-primary" href="<?= e($videoUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e(app_lang() === 'ar' ? 'فتح المحاضرة الخارجية' : 'Open external lecture') ?></a>
        </div>
      <?php else: ?>
        <div class="video-placeholder"><?= e(t('no_video')) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="learn-lesson-meta">
      <h1 class="learn-lesson-title"><?= e($lessonTitle) ?></h1>
      <?php if ($readingOnly && trim((string)($current['duration'] ?? '')) !== ''): ?>
        <p class="reading-duration-badge"><i class="fa-regular fa-clock"></i> <?= e(t('reading_time_est')) ?>: <strong><?= e($current['duration']) ?></strong></p>
      <?php endif; ?>

      <?php if ($showSyncedTranscript): ?>
        <div class="timed-transcript-wrap">
          <h2 class="timed-transcript-title"><?= e(t('transcript_follow')) ?></h2>
          <div id="timed-transcript" class="timed-transcript" dir="auto">
            <?php foreach ($timedSteps as $i => $seg): ?>
              <span class="sync-word" data-i="<?= (int)$i ?>"><?= e($seg['text']) ?></span><span class="sync-space"> </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($readingOnly && $hasReading): ?>
        <div class="learn-lesson-body prose"><?= $body ?></div>
      <?php elseif (!$readingOnly && $showSyncedTranscript && $hasReading): ?>
        <div class="learn-lesson-body prose learn-lesson-body--notes">
          <h3 class="notes-title"><?= e(t('lesson_notes')) ?></h3>
          <?= $body ?>
        </div>
      <?php elseif (!$readingOnly && !$showSyncedTranscript && $hasReading): ?>
        <div class="learn-lesson-body prose"><?= $body ?></div>
      <?php endif; ?>
      <?php if ($showCompleteBtn): ?>
        <form method="post" class="learn-complete-form">
          <?= csrf_field() ?>
          <input type="hidden" name="course_id" value="<?= $courseId ?>">
          <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">
          <button type="submit" name="mark_complete" value="1" class="btn btn-primary"><?= e(t('mark_lesson_complete')) ?></button>
          <p class="muted small"><?= e(t('mark_lesson_complete_hint')) ?></p>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <aside class="learn-sidebar">
    <h2 class="learn-sidebar-title"><?= e(t('course_content')) ?></h2>
    <p class="learn-sidebar-course"><?= e($courseTitle) ?></p>
    <?php if ($userId > 0 && $learnMode === 'enrolled'): ?>
      <div class="learn-enroll-progress" aria-label="<?= e(t('learn_course_progress_title')) ?>">
        <div class="learn-enroll-progress-label">
          <span><?= e(t('learn_course_progress_title')) ?></span>
          <strong><?= (int)$enrollmentProgressPct ?>%</strong>
        </div>
        <div class="learn-progress-track"><span class="learn-progress-fill" style="width:<?= min(100, max(0, (int)$enrollmentProgressPct)) ?>%"></span></div>
      </div>
    <?php endif; ?>
    <?php if ($courseProject || $courseQuiz): ?>
      <div class="course-block" style="margin-bottom:12px">
        <h3 style="margin:0 0 10px;font-size:1rem">المهام والتقييم</h3>
        <?php if ($courseProject): ?>
          <p class="muted small" style="margin:0 0 8px">
            <i class="fa-solid fa-diagram-project"></i>
            <?= e($isAr ? ($courseProject['title_ar'] ?? 'المشروع التطبيقي') : ($courseProject['title_en'] ?? 'Capstone project')) ?>
          </p>
        <?php endif; ?>
        <?php if ($courseQuiz): ?>
          <p class="muted small" style="margin:0">
            <i class="fa-solid fa-list-check"></i>
            <?= e($isAr ? ($courseQuiz['title_ar'] ?? 'اختبار الدورة') : ($courseQuiz['title_en'] ?? 'Course quiz')) ?>
            <?php if ($lastQuizAttempt): ?>
              — <?= e((string)$lastQuizAttempt['score']) ?>% (<?= e((string)$lastQuizAttempt['status']) ?>)
            <?php endif; ?>
          </p>
          <?php if ($learnMode === 'enrolled' && $userId > 0): ?>
            <?php if ($quizFeedback !== ''): ?>
              <div class="alert alert-info" style="margin-top:10px;padding:10px 12px;font-size:0.95rem"><?= e($quizFeedback) ?></div>
            <?php endif; ?>
            <?php if (count($quizQuestions) > 0): ?>
              <form method="post" class="course-quiz-form" style="margin-top:10px">
                <?= csrf_field() ?>
                <input type="hidden" name="course_id" value="<?= (int)$courseId ?>">
                <input type="hidden" name="quiz_id" value="<?= (int)$courseQuiz['id'] ?>">
                <div style="display:flex;flex-direction:column;gap:14px">
                  <?php foreach ($quizQuestions as $qi => $qrow):
                      $qText = (string)($qrow['q'] ?? '');
                      $choices = is_array($qrow['choices'] ?? null) ? $qrow['choices'] : [];
                      if (trim($qText) === '') {
                          continue;
                      }
                      ?>
                    <div class="quiz-question" style="border:1px solid rgba(15,118,110,0.25);border-radius:10px;padding:10px 12px">
                      <div style="font-weight:700;margin-bottom:8px"><?= e($qText) ?></div>
                      <div style="display:flex;flex-direction:column;gap:8px">
                        <?php foreach ($choices as $ci => $cval): ?>
                          <label style="display:flex;gap:10px;align-items:center;cursor:pointer">
                            <input type="radio" name="q_<?= (int)$qi ?>" value="<?= (int)$ci ?>" required>
                            <span><?= e((string)$cval) ?></span>
                          </label>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                  <button type="submit" name="submit_quiz_attempt" value="1" class="btn btn-primary"><?= e($isAr ? 'إرسال الإجابات' : 'Submit') ?></button>
                  <span class="muted small">
                    <?= e($isAr ? 'محاولات' : 'Attempts') ?>: <?= e((string)($courseQuiz['max_attempts'] ?? 3)) ?> —
                    <?= e($isAr ? 'النجاح' : 'Pass') ?>: <?= e((string)($courseQuiz['pass_score'] ?? 70)) ?>%
                  </span>
                </div>
              </form>
            <?php else: ?>
              <p class="muted small" style="margin-top:10px"><?= e($isAr ? 'لا توجد أسئلة بعد.' : 'No questions yet.') ?></p>
            <?php endif; ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <ol class="curriculum-list curriculum-list--learn">
      <?php foreach ($lessons as $i => $l):
          $lid = (int)$l['id'];
          $active = $lid === $lessonId;
          $lt = $isAr ? $l['title_ar'] : $l['title_en'];
          $rowAccess = learn_lesson_access_state($viewer, $course, $lid, $lessons, $completedIds);
          $lHasVideo = trim((string)($l['video_url'] ?? '')) !== '';
          $lHasReading = trim(strip_tags((string)($isAr ? ($l['content_ar'] ?? '') : ($l['content_en'] ?? '')))) !== '';
          ?>
        <li class="<?= $active ? 'is-active' : '' ?> <?= !$rowAccess['ok'] ? 'is-locked' : '' ?>">
          <?php if ($rowAccess['ok']): ?>
            <a href="<?= APP_URL ?>/learn.php?course_id=<?= $courseId ?>&lesson_id=<?= $lid ?>">
              <span class="curriculum-idx"><?= (int)$l['sort_order'] ?></span>
              <span class="curriculum-name"><?= e($lt) ?></span>
              <?php if ($i < FREE_PREVIEW_LESSON_COUNT && ($rowAccess['mode'] ?? '') === 'preview'): ?>
                <span class="curriculum-badge curriculum-badge--preview"><?= e(t('free_preview_short')) ?></span>
              <?php endif; ?>
              <?php if (!$lHasVideo && $lHasReading): ?>
                <span class="curriculum-badge curriculum-badge--read"><?= e(t('reading_lesson')) ?></span>
              <?php endif; ?>
              <?php if ($active): ?><span class="curriculum-playing"><?= e(t('now_playing')) ?></span><?php endif; ?>
            </a>
          <?php else: ?>
            <span class="curriculum-row-locked">
              <span class="curriculum-idx"><?= (int)$l['sort_order'] ?></span>
              <span class="curriculum-name"><?= e($lt) ?></span>
              <span class="curriculum-locked"><i class="fa-solid fa-lock"></i></span>
            </span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
    <?php if (!is_logged_in()): ?>
      <p class="learn-sidebar-cta muted small"><?= e(t('login_for_full_course')) ?></p>
      <a class="btn btn-primary btn-block" href="<?= e(route_url('login.php')) ?>"><?= e(t('login')) ?></a>
    <?php endif; ?>
  </aside>
</section>
<?php if ($showCompleteBtn): ?>
<script>
(function () {
  var form = document.querySelector('.learn-complete-form');
  var v = document.getElementById('lesson-sync-video');
  if (form && v) {
    v.addEventListener('ended', function () { form.submit(); });
  }
})();
</script>
<?php endif; ?>
<?php if ($showSyncedTranscript): ?>
<script>
(function () {
  var ytPrev = window.onYouTubeIframeAPIReady;
  var steps = <?= json_encode($timedSteps, JSON_UNESCAPED_UNICODE) ?>;
  var els = document.querySelectorAll('#timed-transcript .sync-word');
  var lastIdx = -1;
  function setActive(t) {
    var idx = -1;
    for (var i = 0; i < steps.length; i++) {
      if (t >= steps[i].start && t < steps[i].end) { idx = i; break; }
    }
    for (var j = 0; j < els.length; j++) {
      els[j].classList.toggle('is-active', j === idx);
    }
    if (idx >= 0 && idx !== lastIdx && els[idx]) {
      els[idx].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      lastIdx = idx;
    }
    if (idx < 0) { lastIdx = -1; }
  }
  window._lessonTranscriptSync = setActive;
  var v = document.getElementById('lesson-sync-video');
  if (v) {
    v.addEventListener('timeupdate', function () { setActive(v.currentTime); });
  }
  window.onYouTubeIframeAPIReady = function () {
    if (typeof ytPrev === 'function') {
      try { ytPrev(); } catch (e0) {}
    }
    var el = document.getElementById('lesson-yt-player');
    var fn = window._lessonTranscriptSync;
    if (!el || !el.dataset.videoid || typeof fn !== 'function' || typeof YT === 'undefined' || !YT.Player) {
      return;
    }
    new YT.Player('lesson-yt-player', {
      videoId: el.dataset.videoid,
      playerVars: { rel: 0, modestbranding: 1 },
      events: {
        onReady: function (e) {
          setInterval(function () {
            try { fn(e.target.getCurrentTime()); } catch (x) {}
          }, 200);
        }
      }
    });
  };
})();
</script>
<?php if ($ytId && !$useNativeVideo): ?>
<script src="https://www.youtube.com/iframe_api"></script>
<?php endif; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
