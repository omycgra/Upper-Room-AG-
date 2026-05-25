# Local Windows Software Guide

This project can run as a local Windows software package using XAMPP.

## Recommended Local Structure

Install XAMPP in the default folder:

```text
C:\xampp
```

Place this project here:

```text
C:\xampp\htdocs\AG
```

## Files Added For Local Usage

- `setup-local-windows.bat` - first-time setup for local Windows PCs
- `start-local-app.bat` - starts Apache/MySQL and opens the app
- `stop-local-app.bat` - stops the local services
- `create-local-shortcut.ps1` - creates a desktop shortcut
- `open-local-app-window.ps1` - opens the system in an app-style Edge/Chrome window when available
- `.env.local-software.example` - local `.env` template

## First-Time Setup On A Church PC

1. Install XAMPP in `C:\xampp`
2. Copy the `AG` folder into `C:\xampp\htdocs\`
3. Double-click:

```text
setup-local-windows.bat
```

This will:

- create `.env` if missing
- start MySQL and Apache
- run `setup.php`
- create a desktop shortcut

## Daily Usage

To open the app, use either:

- the desktop shortcut
- `start-local-app.bat`

The launcher tries to open the system in a cleaner app-style window first.
If Edge or Chrome is not installed, it falls back to the default browser.

To stop the local server, run:

- `stop-local-app.bat`

## Local URL

The app will open at:

```text
http://localhost/AG/
```

## Notes

- This works best on a single main office computer.
- Each computer keeps its own separate local database.
- For backup, export the MySQL database from phpMyAdmin regularly.

## Shared Supabase Database Across Multiple PCs

If you want several PCs to use the same live church data:

- install XAMPP on each PC
- copy this project to each PC
- use the same Supabase `.env` settings on each PC
- enable `pdo_pgsql` and `pgsql` in each PC's `C:\xampp\php\php.ini`
- run `second-pc-shared-db-setup.bat` on the second PC for the quick shared-database flow

If you want one PC to host the app for the others on the same Wi-Fi or LAN:

- keep the project on one main PC
- start Apache on that PC
- run `show-lan-url.ps1`
- open the printed `http://HOST-IP/AG/` URL from the other PCs

For the full guide, see:

```text
SHARED_DATABASE_MULTI_PC.md
```

## Optional Installer

An Inno Setup script template is included at:

```text
installer\ChurchManagementLocal.iss
```

It is already branded for:

- `Upper Room Assembly Mampong`
- a custom installer icon
- a branded wizard image
- a more professional installer name

You can compile it with Inno Setup to produce a Windows installer `.exe`.

Generated installer branding assets are stored in:

```text
installer\assets
```
