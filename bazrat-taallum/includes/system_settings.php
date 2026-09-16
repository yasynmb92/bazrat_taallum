<?php
/**
 * إعدادات من جدول settings مع تخزين مؤقت لكل طلب.
 */
function get_system_setting(string $key, string $default = ''): string {
    if (!isset($GLOBALS['_sys_settings_map']) || !is_array($GLOBALS['_sys_settings_map'])) {
        $GLOBALS['_sys_settings_map'] = [];
        $conn = db();
        $res = $conn->query('SELECT setting_key, setting_value FROM settings');
        while ($res && $row = $res->fetch_assoc()) {
            $GLOBALS['_sys_settings_map'][$row['setting_key']] = (string)$row['setting_value'];
        }
    }
    $value = $GLOBALS['_sys_settings_map'][$key] ?? $default;
    if (in_array($key, ['smtp_password', 'facebook_app_secret'], true)) {
        return decrypt_app_secret((string)$value);
    }
    return (string)$value;
}

function clear_system_settings_cache(): void {
    unset($GLOBALS['_sys_settings_map']);
}

function platform_display_name(): string {
    $lang = app_lang();
    $ar = get_system_setting('platform_name_ar', APP_NAME_AR);
    $en = get_system_setting('platform_name_en', APP_NAME_EN);
    return $lang === 'ar' ? ($ar !== '' ? $ar : APP_NAME_AR) : ($en !== '' ? $en : APP_NAME_EN);
}

/** رابط مطلق للشعار في البريد */
function platform_logo_absolute_url(): string {
    $path = trim(get_system_setting('platform_logo', ''));
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function apply_email_placeholders(string $template, array $vars): string {
    $out = $template;
    foreach ($vars as $k => $v) {
        $out = str_replace('{{' . $k . '}}', $v, $out);
    }
    return $out;
}

function default_verify_email_template_ar(): string {
    return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0;background:#f4f4f5;font-family:Tahoma,'Segoe UI',Arial,sans-serif;font-size:15px;color:#333;line-height:1.75;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
<tr><td style="padding:20px 24px;border-bottom:1px solid #e5e7eb;text-align:right;direction:rtl;">
{{logo_html}}
<div style="font-size:12px;color:#6b7280;margin-top:8px;">{{tagline}}</div>
</td></tr>
<tr><td style="padding:28px 24px;text-align:right;direction:rtl;">
<p style="margin:0 0 18px;">مرحبًا <strong>{{user_name}}</strong>،</p>
<p style="margin:0 0 18px;">نود إعلامك بأنه تم تسجيلك بنجاح في منصة <strong>{{site_name}}</strong> باستخدام البيانات التالية:</p>
<table role="presentation" style="width:100%;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:18px;margin:0 0 22px;">
<tr><td style="padding:6px 0;"><strong>اسم المستخدم:</strong></td></tr>
<tr><td style="padding:4px 0 14px;word-break:break-all;">{{email}}</td></tr>
<tr><td style="padding:6px 0;"><strong>كلمة المرور:</strong></td></tr>
<tr><td style="padding:4px 0 14px;"><code style="background:#fff;padding:6px 10px;border-radius:6px;border:1px solid #e5e7eb;font-size:14px;">{{temp_password}}</code></td></tr>
<tr><td style="padding:6px 0;"><strong>رمز التحقق (لتفعيل البريد):</strong></td></tr>
<tr><td style="padding:4px 0;font-size:24px;font-weight:700;letter-spacing:6px;color:#111;">{{verification_code}}</td></tr>
</table>
<p style="margin:0 0 14px;">يمكنك الآن تسجيل الدخول إلى حسابك من خلال الرابط التالي:</p>
<p style="margin:0 0 22px;word-break:break-all;"><a href="{{site_url}}" style="color:#1d4ed8;font-weight:600;">{{site_url}}</a></p>
<p style="margin:0 0 18px;">شكرًا لانضمامك إلينا، ونتمنى لك تجربة تعليمية مميزة تناسب عمرك ومستواك.</p>
<p style="margin:0 0 22px;color:#4b5563;font-size:14px;">لأمان حسابك، غيّر كلمة المرور المؤقتة بعد أول تسجيل دخول ولا تشاركها مع أي شخص.</p>
<p style="margin:0 0 8px;">في حال واجهت أي مشكلة، لا تتردد في التواصل معنا عبر:</p>
<p style="margin:0 0 4px;"><a href="mailto:{{sig_email}}" style="color:#1d4ed8;">{{sig_email}}</a></p>
<p style="margin:0 0 22px;color:#4b5563;">{{sig_phone}}</p>
<p style="margin:0;">مع أطيب التحيات،<br><strong>{{sig_team}}</strong><br>{{sig_admin}}<br>{{site_name}}</p>
</td></tr>
<tr><td style="padding:20px 24px;background:#f9fafb;border-top:1px solid #e5e7eb;text-align:right;direction:rtl;">
<p style="margin:0;font-size:13px;color:#6b7280;"><strong>ملاحظة:</strong> هذه رسالة تلقائية، يرجى عدم الرد عليها.</p>
</td></tr>
</table>
<p style="max-width:600px;margin:16px auto 0;font-size:11px;color:#9ca3af;text-align:center;">{{footer_powered}}</p>
</td></tr>
</table>
HTML;
}

function default_verify_email_template_en(): string {
    return <<<'HTML'
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#333;line-height:1.75;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" style="max-width:600px;width:100%;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
<tr><td style="padding:20px 24px;border-bottom:1px solid #e5e7eb;text-align:left;">
{{logo_html}}
<div style="font-size:12px;color:#6b7280;margin-top:8px;">{{tagline}}</div>
</td></tr>
<tr><td style="padding:28px 24px;text-align:left;">
<p style="margin:0 0 18px;">Hello <strong>{{user_name}}</strong>,</p>
<p style="margin:0 0 18px;">You have been successfully registered on <strong>{{site_name}}</strong> with the following credentials:</p>
<table role="presentation" style="width:100%;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:18px;margin:0 0 22px;">
<tr><td style="padding:6px 0;"><strong>Username:</strong></td></tr>
<tr><td style="padding:4px 0 14px;word-break:break-all;">{{email}}</td></tr>
<tr><td style="padding:6px 0;"><strong>Password:</strong></td></tr>
<tr><td style="padding:4px 0 14px;"><code style="background:#fff;padding:6px 10px;border-radius:6px;border:1px solid #e5e7eb;font-size:14px;">{{temp_password}}</code></td></tr>
<tr><td style="padding:6px 0;"><strong>Verification code:</strong></td></tr>
<tr><td style="padding:4px 0;font-size:24px;font-weight:700;letter-spacing:6px;color:#111;">{{verification_code}}</td></tr>
</table>
<p style="margin:0 0 14px;">You can sign in using this link:</p>
<p style="margin:0 0 22px;word-break:break-all;"><a href="{{site_url}}" style="color:#1d4ed8;font-weight:600;">{{site_url}}</a></p>
<p style="margin:0 0 18px;">Thank you for joining us — we wish you a learning experience suited to your age and level.</p>
<p style="margin:0 0 22px;color:#4b5563;font-size:14px;">For your security, change the temporary password after your first sign-in and never share it.</p>
<p style="margin:0 0 8px;">If you need help, contact us at:</p>
<p style="margin:0 0 4px;"><a href="mailto:{{sig_email}}" style="color:#1d4ed8;">{{sig_email}}</a></p>
<p style="margin:0 0 22px;color:#4b5563;">{{sig_phone}}</p>
<p style="margin:0;">Best regards,<br><strong>{{sig_team}}</strong><br>{{sig_admin}}<br>{{site_name}}</p>
</td></tr>
<tr><td style="padding:20px 24px;background:#f9fafb;border-top:1px solid #e5e7eb;text-align:left;">
<p style="margin:0;font-size:13px;color:#6b7280;"><strong>Note:</strong> This is an automated message. Please do not reply.</p>
</td></tr>
</table>
<p style="max-width:600px;margin:16px auto 0;font-size:11px;color:#9ca3af;text-align:center;">{{footer_powered}}</p>
</td></tr>
</table>
HTML;
}

/**
 * @return array{html: array<string,string>, plain: array<string,string>}
 */
function build_verification_email_vars(string $email, string $code, string $password, string $fullName): array {
    $lang = app_lang();
    $siteName = $lang === 'ar'
        ? get_system_setting('platform_name_ar', APP_NAME_AR)
        : get_system_setting('platform_name_en', APP_NAME_EN);
    if ($siteName === '') {
        $siteName = $lang === 'ar' ? APP_NAME_AR : APP_NAME_EN;
    }
    $tagline = get_system_setting('email_tagline', $lang === 'ar' ? 'منصة تعليم إلكتروني' : 'E-Learning Platform');
    $logoUrl = platform_logo_absolute_url();
    $logoHtml = $logoUrl !== ''
        ? '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '" style="max-height:52px;display:block;">'
        : '<span style="font-size:22px;font-weight:700;color:#1d4ed8;">' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</span>';

    $year = (string)date('Y');
    $footer = $lang === 'ar'
        ? ('مدعوم من ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' © ' . $year)
        : ('Powered by ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' © ' . $year);

    $uname = $fullName !== '' ? $fullName : $email;
    $siteUrl = rtrim(APP_URL, '/');

    $sigTeam = get_system_setting('email_sig_team', $lang === 'ar' ? 'فريق الدعم الفني' : 'Support team');
    $sigAdmin = get_system_setting('email_sig_admin', $lang === 'ar' ? ('إدارة ' . $siteName) : ('Administrator, ' . $siteName));
    $sigPhoneRaw = trim(get_system_setting('email_sig_phone', ''));
    $sigPhone = $sigPhoneRaw !== '' ? $sigPhoneRaw : ($lang === 'ar' ? '—' : '—');
    $sigEmailRaw = trim(get_system_setting('email_sig_email', ''));
    if ($sigEmailRaw === '') {
        $sigEmailRaw = trim(get_system_setting('notification_email', ''));
    }
    if ($sigEmailRaw === '') {
        $sigEmailRaw = 'support@example.com';
    }

    $plain = [
        'user_name' => $uname,
        'email' => $email,
        'verification_code' => $code,
        'temp_password' => $password,
        'site_name' => $siteName,
        'site_url' => $siteUrl,
        'year' => $year,
        'tagline' => $tagline,
        'sig_team' => $sigTeam,
        'sig_admin' => $sigAdmin,
        'sig_phone' => $sigPhone,
        'sig_email' => $sigEmailRaw,
    ];

    $html = [
        'user_name' => htmlspecialchars($uname, ENT_QUOTES, 'UTF-8'),
        'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
        'verification_code' => htmlspecialchars($code, ENT_QUOTES, 'UTF-8'),
        'temp_password' => htmlspecialchars($password, ENT_QUOTES, 'UTF-8'),
        'site_name' => htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'),
        'site_url' => htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'),
        'year' => $year,
        'logo_html' => $logoHtml,
        'tagline' => htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8'),
        'sig_team' => htmlspecialchars($sigTeam, ENT_QUOTES, 'UTF-8'),
        'sig_admin' => htmlspecialchars($sigAdmin, ENT_QUOTES, 'UTF-8'),
        'sig_phone' => htmlspecialchars($sigPhone, ENT_QUOTES, 'UTF-8'),
        'sig_email' => htmlspecialchars($sigEmailRaw, ENT_QUOTES, 'UTF-8'),
        'footer_powered' => $footer,
    ];

    return ['html' => $html, 'plain' => $plain];
}
