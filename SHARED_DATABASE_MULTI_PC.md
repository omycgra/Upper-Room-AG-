# Shared Database Multi-PC Guide

This guide explains how to:

- use this app on another PC with the same Supabase database
- package the project for transfer
- open the app from other PCs on the same Wi-Fi or LAN

## Option 1: Install The App On Another PC Using The Same Database

Use this when each PC should have its own local copy of the app, but all of them should read and write to the same Supabase database.

### What You Need On The Other PC

- XAMPP installed in `C:\xampp`
- this project copied into `C:\xampp\htdocs\AG`
- the same `.env` database settings used on the main PC
- PHP PostgreSQL extensions enabled in XAMPP

### Step-By-Step

1. Install XAMPP in:

```text
C:\xampp
```

2. Copy the project folder into:

```text
C:\xampp\htdocs\AG
```

3. Copy the same `.env` file from the main PC into the project root.

4. Open:

```text
C:\xampp\php\php.ini
```

5. Make sure these two lines are enabled:

```ini
extension=pdo_pgsql
extension=pgsql
```

6. Start Apache from XAMPP.

7. Open:

```text
http://localhost/AG/login
```

### Required Supabase Configuration

Your `.env` should keep the same shared-database connection values:

```env
APP_ENV=production
APP_DEBUG=false
APP_BASE_URL=/AG

DB_DRIVER=pgsql
DB_HOST=aws-0-eu-west-1.pooler.supabase.com
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres.cdjiwaokffktyocemhya
DB_PASS=YOUR_SUPABASE_PASSWORD
DB_SCHEMA=public
DB_SSLMODE=require
DB_CHARSET=utf8
```

## Important Shared-Database Notes

- All PCs will see the same members, visitors, attendance, finance, reports, settings, and users.
- Any change made on one PC is visible on the others immediately.
- Do not point one PC to a different database unless you want separate data.
- The installer is safe for branding verification, but all PCs should still use the same `.env` connection if they must share records.

## Option 2: Use One PC As The Host And Open It From Other PCs On The Same Network

Use this when the app files stay on one main PC, and other PCs open it in a browser through the local network.

### On The Host PC

1. Keep the project in:

```text
C:\xampp\htdocs\AG
```

2. Start Apache from XAMPP.

3. Check the host PC's local IP address.

You can use the helper script:

```powershell
powershell -ExecutionPolicy Bypass -File .\show-lan-url.ps1
```

4. Make sure Windows Firewall allows Apache or TCP port `80` on private networks.

5. On another PC connected to the same Wi-Fi or LAN, open:

```text
http://HOST-PC-IP/AG/
```

Example:

```text
http://192.168.1.25/AG/
```

### Notes For LAN Access

- `APP_BASE_URL=/AG` is still correct when the app is opened through the host PC IP.
- The other PCs do not need a copied project if they are only accessing the host PC over the network.
- The host PC must remain on and Apache must be running.

## Transfer Package

To create a ready-to-copy zip file of the app, run:

```powershell
powershell -ExecutionPolicy Bypass -File .\package-shared-database-app.ps1
```

This creates a zip inside:

```text
installer\output
```

The package includes the app files needed for another PC, including the current `.env` and uploaded assets, while skipping bulky or regenerated folders such as `.git` and previous package outputs.

## One-Click Second-PC Setup

On the second PC, after extracting the project into `C:\xampp\htdocs\AG`, you can run:

```text
second-pc-shared-db-setup.bat
```

This helper:

- checks that XAMPP PHP exists
- creates `.env` from `.env.supabase.example` if `.env` is missing
- reminds you to update the Supabase password if you are using the template
- checks whether `pdo_pgsql` and `pgsql` are enabled in `php.ini`
- starts Apache
- creates the desktop shortcut
- opens the local login page

## Recommended Flow

### Best For Multiple Staff PCs

- install XAMPP on each PC
- copy this app to each PC
- use the same `.env`
- keep one shared Supabase database

### Best For Quick Browser Access On The Same Network

- keep the app on one main PC
- start Apache on that PC
- open `http://HOST-PC-IP/AG/` from the other PCs

## Quick Checklist

- same project files
- same `.env`
- same Supabase database
- `pdo_pgsql` enabled
- `pgsql` enabled
- Apache running
- use `http://localhost/AG/` on local installs
- use `http://HOST-PC-IP/AG/` for LAN access
