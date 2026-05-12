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

In cPanel → MySQL Databases, create a new database and user.

### 4. Run the Web Installer

1. Browse to your site after uploading the files
2. Fill in the installer form with your application URL, database credentials, and first admin account
3. The installer will:
   - create `config/config.php`
   - run all pending database migrations
   - create the first administrator if the database has no users yet

If you are connecting this release to an older database, the installer/upgrader will detect the existing tables and register/apply the required database updates automatically.

### 5. Set Document Root

In cPanel, point your domain to the `public/` directory. If you can't change the document root, move the contents of `public/` to your web root and update the path in `index.php`:

```php
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';
// ...
View::setPath(__DIR__ . '/src/Views');
```

### 6. Login

Use the administrator account created during setup.

## Directory Structure

```
├── config/
│   └── config.example.php    # Configuration template
├── database/
│   ├── schema.sql            # MySQL schema snapshot
│   └── migrations/           # Versioned database upgrades
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
- Database upgrades run automatically through the browser when a release includes new migrations

## Security

- CSRF protection on all forms
- Password hashing with bcrypt (PASSWORD_DEFAULT)
- Prepared statements for all database queries (SQL injection prevention)
- Session timeout after 1 hour of inactivity
- Input validation and output escaping (XSS prevention)
