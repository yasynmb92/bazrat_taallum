<?php
require_once __DIR__ . '/includes/header.php';
csrf_enforce_on_post();

$error = '';
$success = '';
$form = [
    'full_name' => '',
    'email' => '',
    'birth_year' => '',
    'birth_month' => '',
    'phone_country_code' => '',
    'phone_number' => '',
    'whatsapp' => '',
    'city' => '',
    'state' => '',
    'parent_name' => '',
    'parent_phone' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $v) {
        $form[$k] = trim((string)($_POST[$k] ?? ''));
    }

    if ($form['full_name'] === '') {
        $error = 'الاسم الكامل مطلوب.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح.';
    } else {
        $birthYear = (int)$form['birth_year'];
        $birthMonth = (int)$form['birth_month'];
        $currentYear = (int)date('Y');
        if ($birthYear < ($currentYear - 18) || $birthYear > ($currentYear - 7) || $birthMonth < 1 || $birthMonth > 12) {
            $error = 'تاريخ الميلاد غير مناسب للفئة العمرية (7-18 سنة).';
        }
    }

    if ($error === '') {
        $conn = db();
        $error = validate_registration_fields($conn, $form, 0);
    }

    if ($error === '') {
        $conn = db();
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $form['email']);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $error = 'هذا البريد مسجل مسبقاً.';
        } else {
            $payload = [
                'full_name' => $form['full_name'],
                'email' => $form['email'],
                'birth_year' => (int)$form['birth_year'],
                'birth_month' => (int)$form['birth_month'],
                'phone_country_code' => $form['phone_country_code'],
                'phone_number' => $form['phone_number'],
                'whatsapp' => $form['whatsapp'],
                'city' => $form['city'],
                'state' => $form['state'],
                'parent_name' => $form['parent_name'],
                'parent_phone' => $form['parent_phone'],
            ];
            $reg = register_user_v2($payload);
            if ($reg !== false) {
                $success = 'تم إنشاء الحساب بنجاح. تحقق من بريدك الإلكتروني لتأكيد الحساب.';
            } else {
                $error = 'تعذر إنشاء الحساب حالياً، حاول لاحقاً.';
            }
        }
    }
}

$countries = countries_dial_sorted_for_lang(app_lang());
$fbOk = facebook_app_configured() && users_table_has_facebook_id();
?>
<section class="container">
  <div class="form-wrap">
    <h2><?= e(t('register')) ?></h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= e($success) ?></div>
      <div class="verify-box">
        <p>تم إرسال رمز التفعيل إلى: <strong><?= e($form['email']) ?></strong></p>
        <a class="btn btn-primary" href="<?= e(route_url('verify-email.php?email=' . urlencode($form['email']))) ?>">تأكيد البريد الإلكتروني</a>
      </div>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

      <?php if ($fbOk): ?>
      <div class="oauth-buttons" style="margin-bottom:1.25rem">
        <a class="btn btn-facebook" href="<?= e(facebook_oauth_start_url()) ?>">
          <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
          التسجيل أو الدخول بحساب فيسبوك
        </a>
      </div>
      <p class="muted small" style="text-align:center;margin:0 0 1rem">أو أكمل النموذج أدناه</p>
      <?php endif; ?>

      <form method="post" id="register-form">
        <?= csrf_field() ?>
        <label><?= e(t('full_name')) ?> <span class="muted small">(ثلاث كلمات على الأقل)</span></label>
        <input type="text" name="full_name" value="<?= e($form['full_name']) ?>" required placeholder="الاسم الأول الأب الجد">

        <label>سنة الميلاد</label>
        <select name="birth_year" required>
          <option value="">اختر</option>
          <?php for ($y = (int)date('Y') - 7; $y >= (int)date('Y') - 18; $y--): ?>
            <option value="<?= $y ?>" <?= $form['birth_year'] === (string)$y ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>

        <label>شهر الميلاد</label>
        <select name="birth_month" required>
          <option value="">اختر</option>
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $form['birth_month'] === (string)$m ? 'selected' : '' ?>><?= $m ?></option>
          <?php endfor; ?>
        </select>

        <label><?= e(t('email')) ?></label>
        <input type="email" name="email" value="<?= e($form['email']) ?>" required autocomplete="email">

        <label>الدولة ورمز الاتصال</label>
        <input type="search" id="country-filter" class="form-country-filter" placeholder="ابحث باسم الدولة (عربي أو إنجليزي)..." autocomplete="off" aria-label="تصفية الدول">
        <select name="phone_country_code" id="phone_country_code" required size="8" class="form-country-select">
          <option value="">اختر الدولة</option>
          <?php foreach ($countries as $c): ?>
            <?php
              $label = app_lang() === 'ar'
                ? ($c['name_ar'] . ' (' . $c['dial'] . ')')
                : ($c['name_en'] . ' (' . $c['dial'] . ')');
              $search = mb_strtolower($c['name_ar'] . ' ' . $c['name_en'] . ' ' . $c['dial'] . ' ' . $c['code'], 'UTF-8');
            ?>
            <option value="<?= e($c['dial']) ?>"
              <?= $form['phone_country_code'] === $c['dial'] ? 'selected' : '' ?>
              data-search="<?= e($search) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>

        <label>رقم الهاتف <span class="muted small">(بدون مفتاح الدولة، 6–12 رقماً — يُسمح بتكرار الرقم لدى مستخدمين مختلفين)</span></label>
        <input type="tel" name="phone_number" id="phone_number" value="<?= e($form['phone_number']) ?>"
          required inputmode="numeric" autocomplete="tel-national" maxlength="12"
          pattern="[0-9]{6,12}" title="6 إلى 12 رقماً">

        <label>واتساب (اختياري)</label>
        <input type="tel" name="whatsapp" value="<?= e($form['whatsapp']) ?>">

        <label>المدينة</label>
        <input type="text" name="city" value="<?= e($form['city']) ?>">

        <label>المنطقة</label>
        <input type="text" name="state" value="<?= e($form['state']) ?>">

        <label>اسم ولي الأمر</label>
        <input type="text" name="parent_name" value="<?= e($form['parent_name']) ?>">

        <label>هاتف ولي الأمر</label>
        <input type="tel" name="parent_phone" value="<?= e($form['parent_phone']) ?>">

        <button class="btn btn-primary" type="submit"><?= e(t('register')) ?></button>
      </form>
    <?php endif; ?>

    <p><?= e(t('have_account')) ?> <a href="<?= e(route_url('login.php')) ?>"><?= e(t('login')) ?></a></p>
  </div>
</section>
<script>
(function () {
  var filter = document.getElementById('country-filter');
  var sel = document.getElementById('phone_country_code');
  if (!filter || !sel) return;
  filter.addEventListener('input', function () {
    var q = filter.value.trim().toLowerCase();
    var opts = sel.querySelectorAll('option');
    for (var i = 0; i < opts.length; i++) {
      var o = opts[i];
      if (!o.value) { o.hidden = false; continue; }
      var s = (o.getAttribute('data-search') || o.textContent || '').toLowerCase();
      o.hidden = q !== '' && s.indexOf(q) === -1;
    }
  });
})();
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
