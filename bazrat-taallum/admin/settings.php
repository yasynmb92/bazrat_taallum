<?php
require_once __DIR__ . '/_layout.php';

$conn = db();
$message = '';
$error = '';

function save_setting(mysqli $conn, string $key, string $value): void {
  if (in_array($key, ['smtp_password', 'facebook_app_secret'], true)) {
    $value = encrypt_app_secret($value);
  }
    $stmt = $conn->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
}

$textKeys = [
    'platform_name_ar', 'platform_name_en', 'platform_logo', 'email_tagline',
    'facebook_app_id',
    'email_sig_team', 'email_sig_admin', 'email_sig_phone', 'email_sig_email',
    'email_verify_subject_ar', 'email_verify_subject_en',
    'email_verify_body_html_ar', 'email_verify_body_html_en',
    'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_user',
    'mail_from_email', 'mail_from_name', 'notification_email',
    'currency', 'service_fee_percent',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_test_email'])) {
        $testTo = trim((string)($_POST['test_email_to'] ?? ''));
        if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            $error = 'أدخل بريداً صالحاً لاختبار الإرسال.';
        } else {
            $ok = send_verification_email($testTo, '000000', 'Test-Temp-123', 'اختبار');
            if ($ok) {
                $message = 'تم إرسال بريد الاختبار (تحقق من صندوق الوارد أو السبام).';
            } else {
                $error = 'فشل الإرسال. اضبط SMTP أعلاه (مضيف، منفذ، tls، المستخدم وكلمة مرور التطبيق) ثم أعد المحاولة.';
            }
        }
    } else {
        foreach ($textKeys as $k) {
            $postKey = 'setting_' . $k;
            if (!array_key_exists($postKey, $_POST)) {
                continue;
            }
            save_setting($conn, $k, (string)$_POST[$postKey]);
        }
        if (isset($_POST['setting_smtp_password']) && trim((string)$_POST['setting_smtp_password']) !== '') {
            save_setting($conn, 'smtp_password', trim((string)$_POST['setting_smtp_password']));
        }
        if (isset($_POST['setting_facebook_app_secret']) && trim((string)$_POST['setting_facebook_app_secret']) !== '') {
            save_setting($conn, 'facebook_app_secret', trim((string)$_POST['setting_facebook_app_secret']));
        }
        save_setting($conn, 'smtp_verify_peer', isset($_POST['setting_smtp_verify_peer']) ? '1' : '0');
        save_setting($conn, 'allow_php_mail_fallback', isset($_POST['setting_allow_php_mail_fallback']) ? '1' : '0');
        clear_system_settings_cache();
        $message = 'تم حفظ الإعدادات.';
    }
}

$settings_map = [];
$res = $conn->query('SELECT setting_key, setting_value FROM settings');
while ($res && $row = $res->fetch_assoc()) {
    $settings_map[$row['setting_key']] = $row['setting_value'];
}

$tplAr = $settings_map['email_verify_body_html_ar'] ?? '';
$tplEn = $settings_map['email_verify_body_html_en'] ?? '';
if ($tplAr === '') {
    $tplAr = default_verify_email_template_ar();
}
if ($tplEn === '') {
    $tplEn = default_verify_email_template_en();
}

render_admin_layout_start('إعدادات النظام والبريد', 'settings.php');
?>
<?php if ($message): ?><div class="alert" style="background:#ecfdf5;color:#166534;padding:12px;border-radius:8px;margin-bottom:16px"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert" style="background:#fef2f2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="settings-form-admin">
  <div class="form-section">
    <h3>معلومات المنصة والشعار</h3>
    <div class="form-row">
      <div class="form-group">
        <label>اسم المنصة (عربي)</label>
        <input type="text" name="setting_platform_name_ar" value="<?= e($settings_map['platform_name_ar'] ?? APP_NAME_AR) ?>" class="form-input">
      </div>
      <div class="form-group">
        <label>اسم المنصة (إنجليزي)</label>
        <input type="text" name="setting_platform_name_en" value="<?= e($settings_map['platform_name_en'] ?? APP_NAME_EN) ?>" class="form-input">
      </div>
    </div>
    <div class="form-group">
      <label>مسار أو رابط الشعار (يُعرض في البريد)</label>
      <input type="text" name="setting_platform_logo" value="<?= e($settings_map['platform_logo'] ?? '') ?>" class="form-input" placeholder="assets/images/logo.png أو https://...">
    </div>
    <div class="form-group">
      <label>سطر تحت الشعار في البريد (شعار فرعي)</label>
      <input type="text" name="setting_email_tagline" value="<?= e($settings_map['email_tagline'] ?? 'منصة تعليم إلكتروني') ?>" class="form-input">
    </div>
  </div>

  <div class="form-section">
    <h3>توقيع البريد (يُدمج في القالب)</h3>
    <div class="form-row">
      <div class="form-group">
        <label>فريق الدعم (السطر الأول)</label>
        <input type="text" name="setting_email_sig_team" value="<?= e($settings_map['email_sig_team'] ?? 'فريق الدعم الفني') ?>" class="form-input">
      </div>
      <div class="form-group">
        <label>المسؤول / الإدارة</label>
        <input type="text" name="setting_email_sig_admin" value="<?= e($settings_map['email_sig_admin'] ?? '') ?>" class="form-input" placeholder="إدارة المنصة">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>هاتف الدعم</label>
        <input type="text" name="setting_email_sig_phone" value="<?= e($settings_map['email_sig_phone'] ?? '') ?>" class="form-input">
      </div>
      <div class="form-group">
        <label>بريد الدعم (يظهر في التوقيع)</label>
        <input type="email" name="setting_email_sig_email" value="<?= e($settings_map['email_sig_email'] ?? '') ?>" class="form-input">
      </div>
    </div>
  </div>

  <div class="form-section">
    <h3>قوالب بريد التحقق من التسجيل</h3>
    <p class="muted small">في <strong>محتوى HTML</strong> يمكنك استخدام: <code>{{user_name}}</code> <code>{{email}}</code> <code>{{verification_code}}</code> <code>{{temp_password}}</code> <code>{{site_name}}</code> <code>{{site_url}}</code> <code>{{logo_html}}</code> <code>{{tagline}}</code> <code>{{sig_team}}</code> <code>{{sig_admin}}</code> <code>{{sig_phone}}</code> <code>{{sig_email}}</code> <code>{{footer_powered}}</code>. في <strong>عنوان الرسالة</strong> تُستبدل العناصر النائبة كنص عادي (مثل <code>{{site_name}}</code> و <code>{{user_name}}</code>)؛ يُفضّل عدم وضع <code>{{temp_password}}</code> أو <code>{{verification_code}}</code> في العنوان لأسباب أمنية.</p>
    <div class="form-group">
      <label>عنوان الرسالة (عربي)</label>
      <input type="text" name="setting_email_verify_subject_ar" value="<?= e($settings_map['email_verify_subject_ar'] ?? '') ?>" class="form-input" placeholder="تم تسجيلك بنجاح في منصة {{site_name}}">
    </div>
    <div class="form-group">
      <label>محتوى HTML (عربي)</label>
      <textarea name="setting_email_verify_body_html_ar" class="form-input" rows="14" style="font-family:monospace;font-size:12px"><?= e($tplAr) ?></textarea>
    </div>
    <div class="form-group">
      <label>عنوان الرسالة (إنجليزي)</label>
      <input type="text" name="setting_email_verify_subject_en" value="<?= e($settings_map['email_verify_subject_en'] ?? '') ?>" class="form-input" placeholder="Welcome — you are registered on {{site_name}}">
    </div>
    <div class="form-group">
      <label>محتوى HTML (إنجليزي)</label>
      <textarea name="setting_email_verify_body_html_en" class="form-input" rows="14" style="font-family:monospace;font-size:12px"><?= e($tplEn) ?></textarea>
    </div>
  </div>

  <div class="form-section">
    <h3>تسجيل الدخول عبر فيسبوك</h3>
    <p class="muted small">أنشئ تطبيقاً من <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">Facebook Developers</a> من نوع «Consumer»، وأضف منتج «Facebook Login». في إعدادات التطبيق ← Facebook Login ← إعدادات: أضف <strong>Valid OAuth Redirect URIs</strong> بالضبط:
      <code><?= e(rtrim(APP_URL, '/') . '/auth/facebook-callback.php') ?></code>
      ثم نفّذ ترقية قاعدة البيانات <code>database/migrations/oauth_facebook_2026.php</code> مرة واحدة إن لم يكن عمود <code>facebook_id</code> موجوداً.</p>
    <div class="form-row">
      <div class="form-group">
        <label>Facebook App ID</label>
        <input type="text" name="setting_facebook_app_id" value="<?= e($settings_map['facebook_app_id'] ?? '') ?>" class="form-input" autocomplete="off">
      </div>
      <div class="form-group">
        <label>Facebook App Secret</label>
        <input type="password" name="setting_facebook_app_secret" value="" class="form-input" autocomplete="new-password" placeholder="اتركه فارغاً للإبقاء على السر المحفوظ">
      </div>
    </div>
  </div>

  <div class="form-section">
    <h3>إعدادات SMTP (إلزامي على XAMPP لتفادي خطأ localhost:25)</h3>
    <p class="muted small">مثال Gmail: المضيف <code>smtp.gmail.com</code>، المنفذ <code>587</code>، التشفير <code>tls</code>، واسم المستخدم = بريدك، وكلمة المرور = <strong>كلمة مرور التطبيق</strong> وليست كلمة حسابك العادية.</p>
    <div class="form-row">
      <div class="form-group">
        <label>SMTP Host</label>
        <input type="text" name="setting_smtp_host" value="<?= e($settings_map['smtp_host'] ?? '') ?>" class="form-input" placeholder="smtp.gmail.com">
      </div>
      <div class="form-group">
        <label>المنفذ</label>
        <input type="text" name="setting_smtp_port" value="<?= e($settings_map['smtp_port'] ?? '587') ?>" class="form-input">
      </div>
      <div class="form-group">
        <label>التشفير</label>
        <select name="setting_smtp_encryption" class="form-input">
          <?php $enc = $settings_map['smtp_encryption'] ?? 'tls'; ?>
          <option value="tls" <?= $enc === 'tls' ? 'selected' : '' ?>>TLS (STARTTLS) — موصى به للمنفذ 587</option>
          <option value="ssl" <?= $enc === 'ssl' ? 'selected' : '' ?>>SSL — للمنفذ 465</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>اسم مستخدم SMTP</label>
        <input type="text" name="setting_smtp_user" value="<?= e($settings_map['smtp_user'] ?? '') ?>" class="form-input" autocomplete="off">
      </div>
      <div class="form-group">
        <label>كلمة مرور SMTP</label>
        <input type="password" name="setting_smtp_password" value="" class="form-input" autocomplete="new-password" placeholder="اتركه فارغاً للإبقاء على كلمة المرور المحفوظة">
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>بريد المرسل (From) — يجب أن يطابق غالباً حساب SMTP</label>
        <input type="email" name="setting_mail_from_email" value="<?= e($settings_map['mail_from_email'] ?? '') ?>" class="form-input">
      </div>
      <div class="form-group">
        <label>اسم المرسل</label>
        <input type="text" name="setting_mail_from_name" value="<?= e($settings_map['mail_from_name'] ?? '') ?>" class="form-input" placeholder="بذرة تعلم">
      </div>
    </div>
    <div class="form-group">
      <label>بريد الإشعارات (احتياطي إن لم يُضبط From)</label>
      <input type="email" name="setting_notification_email" value="<?= e($settings_map['notification_email'] ?? '') ?>" class="form-input">
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="setting_smtp_verify_peer" value="1" <?= ($settings_map['smtp_verify_peer'] ?? '1') === '1' ? 'checked' : '' ?>> التحقق من شهادة SSL (عطّلها فقط للتجربة المحلية)</label>
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="setting_allow_php_mail_fallback" value="1" <?= ($settings_map['allow_php_mail_fallback'] ?? '0') === '1' ? 'checked' : '' ?>> السماح بـ PHP mail() احتياطياً إن فشل SMTP (قد يظهر تحذير على XAMPP)</label>
    </div>
  </div>

  <div class="form-section">
    <h3>الدفع (سابق)</h3>
    <div class="form-row">
      <div class="form-group">
        <label>العملة</label>
        <select name="setting_currency" class="form-input">
          <option value="SAR" <?= ($settings_map['currency'] ?? '') === 'SAR' ? 'selected' : '' ?>>ريال سعودي</option>
          <option value="USD" <?= ($settings_map['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>دولار</option>
        </select>
      </div>
      <div class="form-group">
        <label>رسوم الخدمة (%)</label>
        <input type="number" step="0.01" name="setting_service_fee_percent" value="<?= e($settings_map['service_fee_percent'] ?? '0') ?>" class="form-input">
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary btn-large">حفظ جميع الإعدادات</button>
</form>

<hr style="margin:32px 0;border:none;border-top:1px solid #e5e7eb">

<form method="post" class="settings-form-admin">
  <h3>إرسال بريد تجريبي</h3>
  <p class="muted small">يُرسل نفس قالب التحقق برمز وهمي لاختبار SMTP.</p>
  <div class="form-row" style="align-items:flex-end;gap:12px;flex-wrap:wrap">
    <div class="form-group" style="flex:1;min-width:220px">
      <label>البريد المستلم</label>
      <input type="email" name="test_email_to" class="form-input" required placeholder="you@example.com">
    </div>
    <button type="submit" name="send_test_email" value="1" class="btn btn-outline">إرسال اختبار</button>
  </div>
</form>

<style>
.settings-form-admin { max-width: 920px; }
.form-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb; }
.form-section h3 { margin: 0 0 1rem; font-size: 1.1rem; }
.form-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
.btn-large { padding: 0.75rem 1.5rem; }
</style>
<?php render_admin_layout_end(); ?>
