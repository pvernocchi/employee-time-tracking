# Employee Time Tracking System

A lightweight, vanilla PHP employee time tracking application designed for shared hosting environments (Namecheap Stellar / cPanel).

## Features

- **Clock In/Out** — One-click time tracking with break and notes support
- **Timesheet Management** — Weekly and monthly views of hours worked
- **Admin Dashboard** — Overview of workforce activity
- **Employee Self-Service** — Personal dashboard, time history
- **Leave/Absence Tracking** — Request, approve, reject leave with balance tracking
- **Reporting & Exports** — CSV export of timesheets with date/employee filters

## Requirements

- PHP 8.1+
- MySQL 5.7+
- Apache with mod_rewrite (standard on shared hosting)
- Composer (for autoloading)

## Installation

### 1. Upload Files

Upload all files to your hosting via FTP or cPanel File Manager. The `public/` directory should be your document root (or point your domain to it via cPanel).

### 2. Install Dependencies

Via cPanel Terminal or SSH (if available):
```bash
composer install --no-dev --optimize-autoloader
```

Or upload the `vendor/` folder generated locally.

### 3. Create Database

1. In cPanel → MySQL Databases, create a new database and user
2. Import `database/schema.sql` via phpMyAdmin

### 4. Configure

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` with your database credentials:
```php
'database' => [
    'host' => 'localhost',
    'name' => 'your_cpanel_prefix_timetracking',
    'user' => 'your_cpanel_prefix_dbuser',
    'pass' => 'your_password',
    'charset' => 'utf8mb4',
],
```

### 5. Set Document Root

In cPanel, point your domain to the `public/` directory. If you can't change the document root, move the contents of `public/` to your web root and update the path in `index.php`:

```php
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';
// ...
View::setPath(__DIR__ . '/src/Views');
```

### 6. Login

Default admin credentials:
- **Email:** admin@company.com
- **Password:** admin123

⚠️ **Change this immediately after first login!**

## Directory Structure

```
├── config/
│   └── config.example.php    # Configuration template
├── database/
│   └── schema.sql            # MySQL schema
├── public/                   # Document root (web-accessible)
│   ├── .htaccess             # URL rewriting
│   ├── index.php             # Front controller
│   └── assets/
│       ├── css/style.css
│       └── js/app.js
├── src/
│   ├── Core/                 # Framework (Router, DB, Auth, View)
│   ├── Controllers/          # Request handlers
│   └── Views/                # PHP templates
├── vendor/                   # Composer autoloader (generated)
├── composer.json
└── README.md
```

## User Roles

| Role | Permissions |
|------|------------|
| **Employee** | Clock in/out, view own timesheet, request leave |
| **Manager** | All employee permissions + approve/reject leave, view reports |
| **Admin** | All manager permissions + manage employees, full system access |

## Shared Hosting Notes

- No CLI access required after initial setup
- No cron jobs needed (all operations are request-driven)
- Works with standard Apache + mod_rewrite
- Minimal resource usage — no framework overhead
- Composer is only needed for PSR-4 autoloading

## Security

- CSRF protection on all forms
- Password hashing with bcrypt (PASSWORD_DEFAULT)
- Prepared statements for all database queries (SQL injection prevention)
- Session timeout after 1 hour of inactivity
- Input validation and output escaping (XSS prevention)
