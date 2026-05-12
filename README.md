# ⏱️ Employee Time Tracking

> A lightweight **vanilla PHP** time-tracking platform for teams that need simple attendance management, leave workflows, and labor-law-oriented compliance reporting.

---

## ✨ What this app does

This application helps organizations track working time with a classic server-rendered PHP stack that fits well on shared hosting.

### 🌟 Core capabilities

- 🕒 **Clock in / clock out** with break minutes and notes
- 📅 **Weekly and monthly timesheets**
- 🏖️ **Leave request workflow** for employees and managers
- 👥 **Employee administration** for admins
- 📊 **Reports and CSV exports** for management
- 🛡️ **Compliance dashboard** with audit visibility and labor inspection exports
- 🧾 **Inspector access** for read-only record review and export
- 🔐 **Role-based access control** for admin, manager, employee, and inspector users
- 🌍 **Multilingual interface** with Spanish, English, Catalan, Basque, and Galician language switch

---

## 🧩 Application overview

| Area | What it covers |
| --- | --- |
| 🏠 Dashboard | Personal stats, active status, pending leave, and manager overview cards |
| ⏱️ Time Tracking | Start and stop shifts, add breaks, save notes, see same-day entries |
| 📆 Timesheets | Weekly breakdowns and monthly summaries of worked hours |
| 🌴 Leave | Submit, review, approve, reject, and cancel leave requests |
| 📈 Reporting | Export filtered timesheet data to CSV or review it in-browser |
| ⚖️ Compliance | Monitor alerts, inspect audit activity, and prepare inspection exports |
| 🕵️ Inspector Portal | Browse employee records and export official attendance data |

---

## 👤 User roles

| Role | Access |
| --- | --- |
| 👨‍💼 **Admin** | Full access, employee management, compliance dashboard |
| 🧑‍💼 **Manager** | Reports, leave approvals, workforce overview |
| 👷 **Employee** | Clocking, timesheets, leave requests, self export |
| 🕵️ **Inspector** | Read-only inspection views and exports |

---

## ⚖️ Compliance-focused features

This project is built around **Spanish labor-law-oriented tracking**, including:

- 🇪🇸 Support for **Real Decreto-ley 8/2019** / **Art. 34.9 ET**
- ⏳ Daily hour checks
- 📆 Weekly hour checks
- ➕ Annual overtime monitoring
- 😴 Minimum rest-between-days alerts
- ☕ Break compliance alerts
- 📝 Audit log entries for time-entry creation and edits
- 📤 CSV exports for inspections and employee self-service
- 🗃️ Record retention settings in configuration

---

## 🏗️ Tech stack

| Layer | Technology |
| --- | --- |
| Backend | 🐘 PHP 8.1+ |
| Database | 🐬 MySQL 5.7+ |
| Frontend | 🎨 Server-rendered PHP views, CSS, small JavaScript helpers |
| Routing | 🛣️ Custom router |
| Auth | 🔐 Session-based authentication |
| Deployment | 🚀 Shared hosting / FTP-friendly |

---

## 📁 Project structure

```text
employee-time-tracking/
├── config/                 # App and compliance configuration
├── database/               # SQL schema and seed data
├── public/                 # Web root and static assets
│   ├── assets/css/         # Styling
│   └── assets/js/          # Frontend helpers
├── src/
│   ├── Controllers/        # Request handlers
│   ├── Core/               # Router, DB, auth, views, compliance service
│   └── Views/              # UI templates
├── .github/workflows/      # FTP deployment workflow
├── composer.json
├── README.md
```

---

## 🚀 Quick start

### 1️⃣ Requirements

- PHP 8.1+
- MySQL 5.7+
- Apache with `mod_rewrite`
- Composer

### 2️⃣ Install dependencies

```bash
composer install --no-dev --optimize-autoloader
```

For standalone operational guides, see:
- [`install.md`](install.md)
- [`update.md`](update.md)

### 3️⃣ Configure the app

Copy the example config:

```bash
cp config/config.example.php config/config.php
```

Then update:

- 🌐 App URL
- 🕓 Timezone
- 🗄️ Database credentials
- ⚖️ Compliance settings
- 🔒 Session settings

### 4️⃣ Import the database

Load the schema from:

```text
database/schema.sql
```

### 5️⃣ Point the web server to `public/`

The front controller lives here:

```text
public/index.php
```

---

## 🔑 Default login

> ⚠️ Change these credentials immediately after first login.

- **Email:** `admin@company.com`
- **Password:** `admin123`

---

## 🛣️ Main routes

| Route group | Purpose |
| --- | --- |
| `/login`, `/logout` | Authentication |
| `/dashboard` | Main user dashboard |
| `/clock` | Clock-in / clock-out workflow |
| `/timesheet` | Weekly and monthly timesheets |
| `/leave` | Employee leave requests |
| `/admin/*` | Employee admin, leave review, reports |
| `/compliance/*` | Compliance dashboard, audit, inspection export |
| `/inspector/*` | Inspector-facing records and exports |

---

## 📤 Export options

The application includes multiple CSV export paths:

- 📄 Management timesheet reports
- 👤 Employee self-export of own records
- 🏛️ Inspection-ready attendance export
- 🧾 Compliance export with edit metadata

---

## 🔐 Security notes

- CSRF tokens on forms
- Password hashing with `PASSWORD_DEFAULT`
- Prepared database queries through the DB layer
- Session timeout handling
- Output escaping in views

---

## ☁️ Deployment note

The repository includes a GitHub Actions workflow that installs Composer dependencies and deploys over **FTPS**, making it friendly for shared-hosting environments.

---

## 🎯 Best fit for

- Small and mid-sized teams
- Shared-hosting PHP deployments
- Attendance tracking projects
- Organizations that need audit visibility and compliance exports

---

## 💙 Final impression

If you want a **simple, framework-free, role-aware time tracking system** with compliance-oriented reporting, this project already provides the core building blocks in a straightforward PHP structure.
