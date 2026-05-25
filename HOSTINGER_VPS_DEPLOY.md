# Hostinger VPS Deployment Guide

This project is now ready to use environment-based configuration on a Hostinger VPS.

## 1. Server Stack

Recommended stack:

- Ubuntu 22.04
- Nginx
- PHP 8.2
- MySQL 8

## 2. Upload Project

Copy the project to your VPS, for example:

```bash
/var/www/ag
```

## 3. Create Environment File

Inside the project root, create:

```bash
.env
```

Use this as a starting point:

```env
APP_ENV=production
APP_DEBUG=false
APP_BASE_URL=

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=church_management
DB_USER=church_user
DB_PASS=your_strong_password
DB_CHARSET=utf8mb4
```

Notes:

- Leave `APP_BASE_URL` empty if the app is hosted at the domain root.
- If you host it inside a subfolder, set `APP_BASE_URL=/subfolder-name`.

## 4. Create Database

```sql
CREATE DATABASE church_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'church_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON church_management.* TO 'church_user'@'localhost';
FLUSH PRIVILEGES;
```

## 5. Import Existing Database

Export your local database from XAMPP/phpMyAdmin and import it on the VPS:

```bash
mysql -u church_user -p church_management < church_management.sql
```

## 6. Nginx Example

```nginx
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;

    root /var/www/ag;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

## 7. Permissions

Make sure the web server can write to upload folders:

```bash
sudo chown -R www-data:www-data /var/www/ag
sudo find /var/www/ag -type d -exec chmod 755 {} \;
sudo find /var/www/ag -type f -exec chmod 644 {} \;
```

## 8. Production Behavior

With the new environment config:

- Database credentials come from `.env`
- Production errors are hidden when `APP_DEBUG=false`
- Local development still works without a `.env` file by falling back to the old defaults

## 9. Setup Script

If you want to use the setup script on the VPS:

```bash
php setup.php
```

It now reads database settings from `.env`.
