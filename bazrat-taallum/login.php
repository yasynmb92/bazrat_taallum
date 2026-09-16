<?php
require_once __DIR__ . '/includes/functions.php';
csrf_enforce_on_post();
$error = '';

$fbErrs = [
    'off' => 'تسجيل الدخول عبر فيسبوك غير مفعّل. اضبط التطبيق في لوحة التحكم ← الإعدادات.',
    'state' => 'انتهت صلاحية الجلسة، أعد المحاولة.',
    'cancel' => 'تم إلغاء تسجيل الدخول من فيسبوك.',
    'token' => 'تعذر التحقق من فيسبوك. تحقق من المفتاح السري وعنوان إعادة التوجيه في تطبيق فيسبوك.',
    'profile' => 'تعذر قراءة بيانات الحساب من فيسبوك.',
    'linked' => 'هذا البريد مرتبط بحساب فيسبوك آخر.',
    'migrate' => 'يجب تشغيل ترقية قاعدة البيانات أولاً (ملف migrations/oauth_facebook_2026.php).',
    'create' => 'تعذر إنشاء الحساب عبر فيسبوك.',
    'inactive' => 'هذا الحساب معطّل.',
];
$fbKey = (string)($_GET['fb'] ?? '');
if ($fbKey !== '' && isset($fbErrs[$fbKey])) {
    $error = $fbErrs[$fbKey];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    $rl = login_rate_limit_check($email, $ip);
    if (!$rl['ok']) {
        $error = 'محاولات كثيرة. أعد المحاولة بعد ' . (int)$rl['wait_seconds'] . ' ثانية.';
    } elseif (login_user($email, $password)) {
        login_rate_limit_clear($email, $ip);
        header('Location: ' . route_url('index.php'));
        exit;
    } else {
        login_rate_limit_record_failure($email, $ip);
        $error = 'بيانات الدخول غير صحيحة';
    }
}

$fbOk = facebook_app_configured() && users_table_has_facebook_id();
require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
  <div class="form-wrap">
    <h2><?= e(t('login')) ?></h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

    <?php if ($fbOk): ?>
    <div class="oauth-buttons" style="margin-bottom:1.25rem">
      <a class="btn btn-facebook" href="<?= e(facebook_oauth_start_url()) ?>">
        <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
        المتابعة بحساب فيسبوك
      </a>
    </div>
    <p class="muted small" style="text-align:center;margin:0 0 1rem">أو سجّل الدخول بالبريد</p>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?>
      <label><?= e(t('email')) ?></label>
      <input type="email" name="email" required autocomplete="username">
      <label><?= e(t('password')) ?></label>
      <input type="password" name="password" required autocomplete="current-password">
      <button class="btn btn-primary" type="submit"><?= e(t('login')) ?></button>
    </form>
    <p><?= e(t('no_account')) ?> <a href="<?= e(route_url('register.php')) ?>"><?= e(t('register')) ?></a></p>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
