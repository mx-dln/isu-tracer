# ISU-Cauayan IAT Tracer Study

**A Web-Based Tracer Study with Forecasting and Notification for Institute of Agricultural Technology (IAT) Graduates at Isabela State University – Cauayan Campus**

A modular, plain-PHP (MVC-inspired) information system for graduate tracer studies. It collects graduate employment data, job relevance, competency alignment, time-to-first-employment and curriculum feedback, then provides analytics, employment **forecasting** and automated **notifications** to support institutional decision making.

> **Academic study alignment:** employment profile, employment status, job-field relevance/congruence, competency alignment, time to first employment, curriculum feedback, graduate data collection, reporting and analytics — extended with employment forecasting and automated notifications.

---

## 1. Requirements

| Tool | Version |
| --- | --- |
| PHP | 8.2+ (built & tested on 8.5) |
| MySQL / MariaDB | MySQL 8+ or MariaDB 10.6+ |
| Composer | 2.x |
| Web server | Apache (XAMPP) or PHP built-in server |
| Browser | Modern (Chrome, Edge, Firefox) |

Extensions required: `pdo_mysql`, `mbstring`, `openssl`, `gd` (for Dompdf), `zip` (for PhpSpreadsheet).

**No frameworks are used.** The application is plain PHP with `PHPMailer`, `Dompdf` and `PhpSpreadsheet` for mail / PDF / Excel features.

---

## 2. XAMPP Setup

1. Install XAMPP and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Confirm PHP 8.2+ is active: open `http://localhost/` → phpMyAdmin → *PHP Information*, or run `php -v` in a terminal.
3. Place the project folder under `C:\xampp\htdocs\tracer` (or keep it anywhere and point a virtual host at `public/`).

---

## 3. Database Creation

Option A – via the bundled migration script (recommended):

```bash
php database/migrations/migrate.php
```

This creates the `tracer` database (if missing) and runs `database/migrations/001_create_schema.sql` (31 tables: users, roles, graduates, programs, batches, surveys, survey questions/options/responses/answers, employment profiles/history/sectors, competencies, curriculum feedback, forecast models/results, notifications/templates/rules, reports, audit logs, login logs, system settings, likert scales, password resets).

Option B – manually via phpMyAdmin: import `database/migrations/001_create_schema.sql`.

**Seed demo data (development):**

```bash
php database/seeders/seed.php           # seed only if empty
php database/seeders/seed.php --fresh   # wipe + re-seed
```

The seeder creates the admin account, sample programs, batches (2021–2026), a full default tracer survey, competencies, curriculum questions, **300 demo graduates** (239 with employment profiles), competency responses, curriculum feedback, notifications, templates, rules and system settings. All sample records are labeled **DEMO DATA** and are not real ISU data.

---

## 4. Environment Configuration

1. Copy `.env.example` to `.env`:
   ```bash
   copy .env.example .env
   ```
2. Edit `.env` for your environment:
   ```ini
   APP_ENV=local
   APP_URL=http://localhost/tracer/public   # or http://127.0.0.1:8080
   APP_DEBUG=true

   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=tracer
   DB_USERNAME=root
   DB_PASSWORD=

   MAIL_HOST=
   MAIL_PORT=587
   MAIL_USERNAME=
   MAIL_PASSWORD=
   MAIL_FROM_ADDRESS=noreply@example.com
   MAIL_FROM_NAME="ISU IAT Tracer"
   ```
   Leave `MAIL_HOST` empty to disable real SMTP (emails are logged to `storage/logs/mail-*.log` instead).

> **Never commit `.env`.** Only `.env.example` is tracked.

---

## 5. Composer Installation

```bash
composer install
```

This installs `vlucas/phpdotenv`, `phpmailer/phpmailer`, `dompdf/dompdf` and `phpoffice/phpspreadsheet`, and registers the `App\` namespace and helper functions.

---

## 6. Application Setup

```bash
# 1. Configure environment  (copy + edit .env — see section 4)
# 2. Create database + tables
php database/migrations/migrate.php
# 3. Seed development data
php database/seeders/seed.php
# 4. Ensure writable storage
#    storage/logs, storage/exports, storage/uploads, storage/forecasts
```

---

## 7. Default Login (development only)

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@example.com` | `Admin@12345` |
| IAT Graduate (demo) | any seeded graduate email | `Graduate@12345` |

These are **development-only** credentials. Change them before any production use.

---

## 8. How to Run the System

**With the PHP built-in server (quick start):**

```bash
php -S 127.0.0.1:8080 -t public
```

Then open <http://127.0.0.1:8080>.

**With XAMPP/Apache:** point the document root at the project's `public/` directory, or create a virtual host:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/tracer/public"
    ServerName tracer.local
</VirtualHost>
```

With `public/.htaccess` + `mod_rewrite`, all routes (e.g. `/admin/dashboard`) are handled by the front controller `public/index.php`.

---

## 9. Module Roadmap

| # | Module | Status |
| --- | --- | --- |
| 1 | Foundation (config, DB, router, layouts, error pages) | ✅ Phase 1 |
| 2 | Authentication (login, logout, register, reset, roles, middleware) | ✅ Phase 1 |
| 3 | Graduate management, programs, batches | ✅ Phase 3 |
| 4 | Survey engine (CRUD, question builder, responses) | ✅ Phase 4 |
| 5 | Employment profiles, job relevance, time to first employment | ✅ Phase 5 |
| 6 | Competencies & curriculum feedback (weighted means) | ✅ Phase 6 |
| 7 | Analytics dashboard (KPIs + Chart.js) | ✅ Phase 7 |
| 8 | Forecasting (linear regression engine + dashboard) | ✅ Phase 8 |
| 9 | Notifications (in-app, email, automated rules) | ✅ Phase 9 |
| 10 | Reports (PDF / Excel / CSV) | ✅ Phase 10 |
| 11 | Security & audit hardening (users, audit & login logs, settings) | ✅ Phase 11 |
| 12 | Testing & final documentation | Phase 12 |

---

## 10. Architecture

```
tracer/
├── app/
│   ├── Controllers/     # Web controllers (role-scoped)
│   ├── Models/          # Lightweight active-record models
│   ├── Services/        # Analytics, forecasting, notifications, mail, reports
│   ├── Middleware/      # Auth / Guest / Admin / Graduate guards
│   ├── Validators/      # Server-side input validation
│   ├── Helpers/         # Global helpers (functions.php)
│   └── Core/            # App, Router, Database, View, Auth, Session, Csrf, ...
├── config/              # app.php, database.php, mail.php
├── database/
│   ├── migrations/      # migrate.php + 001_create_schema.sql
│   └── seeders/         # seed.php
├── public/              # index.php (front controller) + assets
├── routes/              # web.php, api.php
├── storage/             # logs, exports, uploads, forecasts
├── views/               # layouts, auth, admin, graduate, ...
├── .env.example
└── composer.json
```

### Key design decisions
- **Routing:** a lightweight custom `Router` (no framework) with `{param}` patterns, method spoofing (`_method`) and middleware chains.
- **Persistence:** `PDO` prepared statements everywhere (never inline SQL with user input).
- **Security:** `password_hash()`/`password_verify()`, `session_regenerate_id()` on login, CSRF tokens on all state-changing forms, role middleware, login attempt throttling, output escaping via `e()`.
- **Forecasting:** simple linear regression (`y = a + bx`) over historical aggregates; results are clearly labeled *Forecast / Projected Trend / Estimated Value* and stored in `forecast_models` + `forecast_results`.
- **Notifications:** in-app center (Fetch API, no page reload) plus email via PHPMailer; thresholds are configurable through System Settings, never hardcoded.

---

## 11. Important Limitations

- The system is built for academic/research use (capstone). Forecasts are **statistical projections, not guarantees**.
- When historical data is insufficient, the system displays *"Insufficient historical observations for reliable forecasting"* and will not fabricate data.
- Demo records are clearly marked `is_demo` / DEMO DATA.

## 12. License

MIT. This is an educational project; data privacy controls are included but production deployments must comply with applicable data protection regulations.
