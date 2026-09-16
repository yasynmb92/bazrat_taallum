<?php
/**
 * Run once:
 * php database/migrations/security_hardening_2026.php
 */
require_once __DIR__ . '/../../config/db.php';

$conn = db();

foreach ([
  'verification_expires_at DATETIME NULL',
  'verification_sent_at DATETIME NULL'
] as $definition) {
  [$column] = explode(' ', $definition, 2);
  $safeColumn = $conn->real_escape_string($column);
  $exists = $conn->query("SHOW COLUMNS FROM users LIKE '$safeColumn'");
  if (!$exists || $exists->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN $definition");
  }
}

$conn->query("UPDATE users SET temp_password = NULL WHERE temp_password IS NOT NULL");

$outbox = $conn->query("SHOW COLUMNS FROM email_outbox LIKE 'verification_code'");
if ($outbox && $outbox->num_rows > 0) {
  $conn->query("ALTER TABLE email_outbox MODIFY verification_code VARCHAR(16) NULL");
  $conn->query("UPDATE email_outbox SET verification_code = NULL WHERE verification_code IS NOT NULL");
}

$conn->query("
CREATE TABLE IF NOT EXISTS auth_login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_auth_attempt (email, ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("ALTER TABLE auth_login_attempts MODIFY id INT NOT NULL AUTO_INCREMENT");

$conn->query("
CREATE TABLE IF NOT EXISTS admin_audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT NOT NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id INT NOT NULL DEFAULT 0,
  meta_json MEDIUMTEXT NULL,
  ip_address VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_admin_audit_created (created_at),
  KEY idx_admin_audit_actor (admin_user_id),
  FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$conn->query("ALTER TABLE admin_audit_logs MODIFY id INT NOT NULL AUTO_INCREMENT");

echo "OK: security_hardening_2026 migration\n";

