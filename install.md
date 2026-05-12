# Installation Guide

This guide covers first-time setup of Employee Time Tracking.

## 1) Upload project files

- Upload the full project to your hosting account.
- Point your domain/subdomain document root to:
  - `/home/runner/work/employee-time-tracking/employee-time-tracking/public` (in this repository layout)
  - or the `public/` folder path on your server.

## 2) Install PHP dependencies

Run:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

## 3) Create database credentials

In cPanel (or your hosting panel):

- Create a MySQL database
- Create a MySQL user
- Assign the user to the database with full privileges

## 4) Open the web installer

- Visit your site URL.
- You will be redirected automatically to `/install` if setup is not complete.
- Fill in:
  - App name, app URL, timezone
  - DB host/name/user/password/charset
  - First admin name/email/password

When submitted, the installer will:

1. Create `config/config.php`
2. Run pending migrations from `database/migrations/`
3. Create the first admin account (if no users exist)

## 5) Log in

- After setup, you are redirected to `/login`.
- Sign in with the admin account you created in the installer.

## Troubleshooting

- **Installer cannot write config file**  
  Ensure `config/` (or `config/config.php` if it exists) is writable by PHP.

- **Database connection failed**  
  Re-check host/db/user/password and verify the DB user has privileges.

- **Stuck on install page**  
  Verify web root points to `public/` and that PHP can read project files.
