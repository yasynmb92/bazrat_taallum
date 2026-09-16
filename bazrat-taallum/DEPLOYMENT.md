# Deployment checklist

## 1. Server packages

Ubuntu/Apache example:

```bash
sudo apt update
sudo apt install apache2 mysql-server php8.3 libapache2-mod-php8.3 php8.3-mysql php8.3-mbstring php8.3-curl php8.3-xml php8.3-fileinfo unzip
sudo a2enmod rewrite headers ssl
sudo systemctl restart apache2
```

## 2. Database

Create a dedicated database user. Do not use `root` from the web application.

```sql
CREATE DATABASE bazrat_taallum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bazrat_app'@'localhost' IDENTIFIED BY 'GENERATE_A_LONG_RANDOM_PASSWORD';
GRANT ALL PRIVILEGES ON bazrat_taallum.* TO 'bazrat_app'@'localhost';
FLUSH PRIVILEGES;
```

Copy `.env.example` to `.env`, set `APP_URL`, `DB_PASS`, and SMTP values, then run:

```bash
php database/setup.php
```

Verify that the selected CLI PHP has the MySQL extension before running setup:

```bash
php -m | grep mysqli
```

If the command returns nothing and the server PHP is installed at `/usr/bin/php`, run:

```bash
/usr/bin/php database/setup.php
```

The setup script is CLI-only. It applies `schema.sql`, seeds an empty database, and runs migrations. Do not expose `database/` through the web server.

For production, set `APP_ENV=production` before running setup. This disables the seeded demo administrator (`admin@seed-learning.com`). Create a new administrator explicitly afterward:

```bash
APP_ENV=production ADMIN_NAME='Site Administrator' ADMIN_EMAIL='admin@your-domain.example' ADMIN_PASSWORD="$(openssl rand -base64 24)" /usr/bin/php create-admin.php
```

Use a password manager to save the generated password. Do not use `admin123` on a live server.

## 3. Admin account

Local seed accounts use `admin@seed-learning.com` with password `admin123` only on a new seeded database. Change it immediately.

For local or production accounts, create a new password without storing it in Git:

```bash
ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD='use-a-long-unique-password' php create-admin.php
```

The online server has no predefined password. Generate a unique one and store it in a password manager.

## 4. HTTPS and Apache

Set `APP_URL=https://your-domain.example` in `.env`. Point the Apache virtual host document root to this project directory and allow overrides:

```apache
<Directory /var/www/bazrat-taallum>
    AllowOverride All
    Require all granted
</Directory>
```

Install a certificate, for example with Certbot:

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d your-domain.example
```

## 5. Upload permissions

The web user needs write permission only for the upload directory:

```bash
sudo chown -R www-data:www-data assets/uploads
sudo chmod -R 0750 assets/uploads
```

The upload `.htaccess` blocks PHP execution. For large production video libraries, use object storage instead of local disk.

## 6. SMTP

Set these values in `.env` or configure them from the admin settings page:

```text
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=mailer@example.com
SMTP_PASSWORD=secret
SMTP_ENCRYPTION=tls
MAIL_FROM_EMAIL=noreply@your-domain.example
MAIL_FROM_NAME=Learning Seeds
```

Use a sender on the same domain and configure SPF, DKIM, and DMARC.

## 7. Backups

Make the script executable and schedule it daily:

```bash
chmod 750 backup.sh
crontab -e
0 3 * * * cd /var/www/bazrat-taallum && ./.env-cron && ./backup.sh >> /var/log/bazrat-backup.log 2>&1
```

`.env-cron` must be readable only by the cron owner and contain `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, and `BACKUP_DIR`. Keep backups outside the public document root.

## 8. Validation

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
curl -I https://your-domain.example/login.php
curl -I https://your-domain.example/admin/courses.php
```

Never commit `.env`, passwords, SQL dumps, logs, or uploaded videos.
