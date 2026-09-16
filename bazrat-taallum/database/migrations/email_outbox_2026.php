<?php
/**
 * Run once على السيرفر بعد الرفع:
 * php database/migrations/email_outbox_2026.php
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();

$conn->query("
CREATE TABLE IF NOT EXISTS email_outbox (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email_to VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  verification_code VARCHAR(16) NOT NULL,
  html_preview MEDIUMTEXT NULL,
  sent_ok TINYINT(1) NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_email_outbox_to_created (email_to, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

echo "OK: email_outbox_2026 migration\n";
