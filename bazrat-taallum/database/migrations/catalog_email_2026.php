<?php
/**
 * Keeps the catalog and first-registration email template available after setup.
 */
require_once __DIR__ . '/../seeds/bazrat_dump_courses_seed.php';

$conn = db();
$settings = [
    'email_verify_subject_ar' => 'مرحبًا {{user_name}}، أكمل تفعيل حسابك في {{site_name}}',
    'email_verify_subject_en' => 'Welcome {{user_name}} — verify your {{site_name}} account',
    'email_sig_team' => 'فريق بذرة تعلم',
    'email_sig_admin' => 'إدارة بذرة تعلم',
    'email_sig_email' => 'info@learning-seeds.org',
    'mail_from_name' => 'بذرة تعلم',
    'mail_from_email' => 'info@learning-seeds.org',
    'notification_email' => 'info@learning-seeds.org',
];
$stmt = $conn->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
foreach ($settings as $key => $value) {
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
}
