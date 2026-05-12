# ⏱️ Employee Time Tracking

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-0.0.2-brightgreen.svg)](VERSION)

A lightweight **vanilla PHP** time-tracking platform for teams that need simple attendance management, leave workflows, and compliance reporting aligned with Spanish labor law.

---

## Why This Project?

Most time-tracking tools are either overkill SaaS platforms or complex self-hosted apps that demand containerized infrastructure. This project takes a different approach:

- **Zero framework overhead** — pure PHP 8.1+ with PSR-4 autoloading, no heavy framework to learn
- **Shared-hosting friendly** — runs on any LAMP stack with Apache, PHP, and MySQL
- **Compliance-ready** — built around **Real Decreto-ley 8/2019 / Art. 34.9 ET** with daily/weekly hour checks, overtime monitoring, rest-period alerts, audit logs, and inspection-ready CSV exports
- **Role-based access** — four distinct roles (Admin, Manager, Employee, Inspector) with appropriate permissions
- **Multi-language UI** — supports Spanish, English, Catalan, Basque, and Galician
- **MFA support** — TOTP and WebAuthn/FIDO2 (YubiKey, Windows Hello) for secure authentication

---

## Features

| Area | What it covers |
| --- | --- |
| **Dashboard** | Personal stats, active status, pending leave, manager overview |
| **Time Tracking** | Clock in/out, break tracking, shift notes |
| **Timesheets** | Weekly breakdowns and monthly summaries |
| **Leave Management** | Submit, review, approve/reject, and cancel requests |
| **Reporting** | Filtered reports with CSV export |
| **Compliance** | Alerts dashboard, audit log, inspection exports |
| **Inspector Portal** | Read-only employee record browsing and official exports |
| **Security** | CSRF protection, MFA (TOTP + WebAuthn), session management |
| **Admin Settings** | Employee management, SMTP configuration, security policies |

---

## Quick Start

### Prerequisites

- PHP 8.1+
- MySQL 5.7+
- Apache with `mod_rewrite` enabled
- [Composer](https://getcomposer.org/)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/pvernocchi/employee-time-tracking.git
cd employee-time-tracking

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Point your web server document root to the public/ directory
#    e.g. /path/to/employee-time-tracking/public

# 4. Open the app URL in your browser
#    The web installer will launch automatically on first visit
```

The built-in web installer at `/install` will guide you through:

1. Database connection setup
2. Application configuration (name, URL, timezone)
3. Admin account creation
4. Schema migration

For detailed deployment instructions, see the [Installation Guide](install.md).
For upgrading existing deployments, see the [Update Guide](update.md).

### Manual Configuration (Alternative)

If you prefer to configure manually instead of using the web installer:

```bash
cp config/config.example.php config/config.php
```

Edit `config/config.php` to set your database credentials, app URL, timezone, and compliance thresholds. Then import the schema:

```bash
mysql -u your_user -p your_database < database/schema.sql
```

---

## User Roles

| Role | Access |
| --- | --- |
| **Admin** | Full access — employee management, compliance dashboard, security settings |
| **Manager** | Team reports, leave approvals, workforce overview |
| **Employee** | Clock in/out, timesheets, leave requests, self-service export |
| **Inspector** | Read-only inspection views and official attendance exports |

---

## Project Structure

```text
employee-time-tracking/
├── config/                  # App configuration (config.example.php)
│
├── database/
│   ├── schema.sql           # Full database schema
│   └── migrations/          # Incremental schema migrations
│
├── public/                  # Web root (point your server here)
│   ├── index.php            # Front controller
│   └── assets/              # CSS, JS, and flag icons
│
├── src/
│   ├── Controllers/         # Request handlers
│   ├── Core/                # Router, Database, Auth, I18n, Services
│   ├── Lang/                # Translation files (es, en, ca, eu, gl)
│   └── Views/               # Server-rendered PHP templates
│
├── .github/workflows/       # CI/CD (FTP deployment)
├── composer.json
├── install.md               # Installation guide
└── update.md                # Upgrade guide
```

---

## Deployment

The repository includes a GitHub Actions workflow that runs `composer install` and deploys over **FTPS**, making it suitable for shared-hosting environments. See `.github/workflows/` for the workflow configuration.

---

## Security

- **CSRF tokens** on all forms
- **Password hashing** with `PASSWORD_DEFAULT` (bcrypt)
- **Prepared statements** for all database queries
- **Session timeout** handling with configurable lifetime
- **Output escaping** in all views
- **Multi-factor authentication** — TOTP authenticator apps and WebAuthn/FIDO2 hardware keys
- **Admin-configurable security policies** — enforce MFA, manage user credentials

---

## Getting Help

- **Issues** — [Open an issue](https://github.com/pvernocchi/employee-time-tracking/issues) for bug reports or feature requests
- **Installation problems** — Review the [Installation Guide](install.md) troubleshooting section
- **Configuration reference** — See [`config/config.example.php`](config/config.example.php) for all available settings and their defaults

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on how to submit changes, report bugs, and suggest improvements.

---

## License

This project is licensed under the **GNU General Public License v3.0** — see the [LICENSE](LICENSE) file for details.

---

## Maintainers

This project is maintained by [@pvernocchi](https://github.com/pvernocchi).
