<?php
/**
 * صفحة البروفايل (المستخدم المسجّل)
 * ───────────────────────────────────
 * تسمح بتحديث بيانات الاتصال، كلمة المرور، والاسم الظاهر (مرة واحدة فقط بعد الترقية).
 * تعتمد على عمود users.full_name_change_used (انظر migrations/user_profile_progress_2026.php).
 */
require_once __DIR__ . '/includes/header.php';
require_login();
csrf_enforce_on_post();

$conn = db();
$uid = (int)current_user()['id'];
$stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    http_response_code(404);
    echo '<section class="container"><p>المستخدم غير موجود.</p></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$error = '';
$success = '';

// ─── معالجة النموذج ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $phone_country_code = trim((string)($_POST['phone_country_code'] ?? ''));
    $phone_number = trim((string)($_POST['phone_number'] ?? ''));
    $whatsapp = trim((string)($_POST['whatsapp'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $state = trim((string)($_POST['state'] ?? ''));
    $parent_name = trim((string)($_POST['parent_name'] ?? ''));
    $parent_phone = trim((string)($_POST['parent_phone'] ?? ''));
    $langPref = (string)($_POST['language_pref'] ?? 'ar');
    if (!in_array($langPref, ['ar', 'en'], true)) {
        $langPref = 'ar';
    }

    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $mayName = user_may_change_display_name($user);
    $nameToSave = (string)$user['full_name'];
    if ($mayName && $full_name !== '' && $full_name !== $user['full_name']) {
        $formCheck = [
            'full_name' => $full_name,
            'email' => (string)$user['email'],
            'phone_country_code' => $phone_country_code,
            'phone_number' => $phone_number,
        ];
        $errVal = validate_registration_fields($conn, $formCheck, $uid);
        if ($errVal !== '') {
            $error = $errVal;
        } else {
            $nameToSave = $full_name;
        }
    } elseif ($mayName && $full_name === '') {
        $error = 'الاسم الكامل مطلوب.';
    }

    // التحقق من الحقول عند عدم تغيير الاسم (الهاتف فقط)
    if ($error === '' && (!$mayName || $full_name === $user['full_name'] || $full_name === '')) {
        if ($phone_country_code === '' || $phone_number === '') {
            $error = 'رمز الدولة ورقم الهاتف مطلوبان.';
        } elseif (!is_local_phone_length_valid(local_phone_digits($phone_number))) {
            $error = 'رقم الهاتف غير صالح (6–12 رقماً بدون المفتاح).';
        }
    }

    $newPass = (string)($_POST['new_password'] ?? '');
    $newPass2 = (string)($_POST['new_password_confirm'] ?? '');
    $currentPass = (string)($_POST['current_password'] ?? '');

    if ($error === '' && $newPass !== '') {
        if ($newPass !== $newPass2) {
            $error = 'تأكيد كلمة المرور غير متطابق.';
        } elseif (strlen($newPass) < 8) {
            $error = 'كلمة المرور يجب ألا تقل عن 8 أحرف.';
        } elseif (!password_verify($currentPass, $user['password_hash'])) {
            $error = 'كلمة المرور الحالية غير صحيحة.';
        }
    }

    if ($error === '') {
        $nameChanged = $mayName && $nameToSave !== $user['full_name'];
        $setLock = ($nameChanged && users_table_has_column('full_name_change_used')) ? ', full_name_change_used = 1' : '';

        if ($newPass !== '') {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET full_name = ?, phone_country_code = ?, phone_number = ?, whatsapp = ?, city = ?, state = ?,
                parent_name = ?, parent_phone = ?, language_pref = ?, password_hash = ?{$setLock}
                WHERE id = ?";
            $st = $conn->prepare($sql);
            $st->bind_param(
                'ssssssssssi',
                $nameToSave,
                $phone_country_code,
                $phone_number,
                $whatsapp,
                $city,
                $state,
                $parent_name,
                $parent_phone,
                $langPref,
                $hash,
                $uid
            );
        } else {
            $sql = "UPDATE users SET full_name = ?, phone_country_code = ?, phone_number = ?, whatsapp = ?, city = ?, state = ?,
                parent_name = ?, parent_phone = ?, language_pref = ?{$setLock}
                WHERE id = ?";
            $st = $conn->prepare($sql);
            $st->bind_param(
                'sssssssssi',
                $nameToSave,
                $phone_country_code,
                $phone_number,
                $whatsapp,
                $city,
                $state,
                $parent_name,
                $parent_phone,
                $langPref,
                $uid
            );
        }

        if ($st && $st->execute()) {
            $success = t('profile_saved');
            refresh_session_user($uid);
            $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc() ?: $user;
        } else {
            $error = t('profile_save_failed');
        }
    }
}

$countries = countries_dial_sorted_for_lang(app_lang());
$mayChangeName = user_may_change_display_name($user);
?>
<section class="container profile-page">
  <h1 class="page-title"><?= e(t('my_profile')) ?></h1>
  <p class="muted"><?= e(t('profile_intro')) ?></p>

  <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

  <form method="post" class="form-wrap profile-form">
    <?= csrf_field() ?>
    <input type="hidden" name="save_profile" value="1">

    <h2 class="profile-section-title"><?= e(t('profile_identity')) ?></h2>
    <label><?= e(t('email')) ?></label>
    <input type="email" value="<?= e((string)$user['email']) ?>" disabled class="form-input is-disabled">

    <label><?= e(t('full_name')) ?></label>
    <?php if ($mayChangeName): ?>
      <input type="text" name="full_name" class="form-input" required value="<?= e((string)$user['full_name']) ?>"
        placeholder="<?= e(t('full_name_triple_hint')) ?>">
      <p class="muted small"><?= e(t('profile_name_once_hint')) ?></p>
    <?php else: ?>
      <input type="text" class="form-input is-disabled" value="<?= e((string)$user['full_name']) ?>" disabled>
      <p class="muted small"><?= e(t('profile_name_locked')) ?></p>
    <?php endif; ?>

    <label><?= e(t('profile_language')) ?></label>
    <select name="language_pref" class="form-input">
      <option value="ar" <?= ($user['language_pref'] ?? '') === 'ar' ? 'selected' : '' ?>><?= e(t('arabic')) ?></option>
      <option value="en" <?= ($user['language_pref'] ?? '') === 'en' ? 'selected' : '' ?>><?= e(t('english')) ?></option>
    </select>

    <h2 class="profile-section-title"><?= e(t('profile_contact')) ?></h2>
    <label><?= e(t('profile_country_code')) ?></label>
    <select name="phone_country_code" class="form-input" required>
      <option value="">—</option>
      <?php foreach ($countries as $c): ?>
        <option value="<?= e($c['dial']) ?>" <?= ((string)($user['phone_country_code'] ?? '') === $c['dial']) ? 'selected' : '' ?>>
          <?= e(app_lang() === 'ar' ? $c['name_ar'] : $c['name_en']) ?> (<?= e($c['dial']) ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <label><?= e(t('profile_phone')) ?></label>
    <input type="tel" name="phone_number" class="form-input" required maxlength="12" pattern="[0-9]{6,12}"
      value="<?= e((string)($user['phone_number'] ?? '')) ?>">

    <label><?= e(t('profile_whatsapp')) ?></label>
    <input type="tel" name="whatsapp" class="form-input" value="<?= e((string)($user['whatsapp'] ?? '')) ?>">

    <div class="form-row-profile">
      <div>
        <label><?= e(t('profile_city')) ?></label>
        <input type="text" name="city" class="form-input" value="<?= e((string)($user['city'] ?? '')) ?>">
      </div>
      <div>
        <label><?= e(t('profile_state')) ?></label>
        <input type="text" name="state" class="form-input" value="<?= e((string)($user['state'] ?? '')) ?>">
      </div>
    </div>

    <div class="form-row-profile">
      <div>
        <label><?= e(t('profile_parent_name')) ?></label>
        <input type="text" name="parent_name" class="form-input" value="<?= e((string)($user['parent_name'] ?? '')) ?>">
      </div>
      <div>
        <label><?= e(t('profile_parent_phone')) ?></label>
        <input type="text" name="parent_phone" class="form-input" value="<?= e((string)($user['parent_phone'] ?? '')) ?>">
      </div>
    </div>

    <h2 class="profile-section-title"><?= e(t('profile_password')) ?></h2>
    <p class="muted small"><?= e(t('profile_password_hint')) ?></p>
    <label><?= e(t('current_password')) ?></label>
    <input type="password" name="current_password" class="form-input" autocomplete="current-password">
    <label><?= e(t('new_password')) ?></label>
    <input type="password" name="new_password" class="form-input" autocomplete="new-password">
    <label><?= e(t('new_password_confirm')) ?></label>
    <input type="password" name="new_password_confirm" class="form-input" autocomplete="new-password">

    <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
  </form>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
