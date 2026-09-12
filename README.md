# QA Portal — Accreditation Management System

A comprehensive, web-based Quality Assurance portal for managing academic program accreditations, compliance action items, risk registers, and graduate tracking across colleges and support units.

Built with **Laravel 11** · **MySQL / SQLite** · **Vite** · **Blade** · **PhpSpreadsheet**

---

## Features

- **Accreditation Dashboard**: Real-time status cards, college breakdown, program level tracking, and expiring accreditation alerts.
- **Multi-Target Compliance Management**: Track recommendations by accrediting body (PAASCU, PACUCOA, CHED, PTC, AUN-QA), assign responsible units/departments/programs, monitor checklist progress, and attach document evidence links.
- **Risk Item Register**: Log institutional and academic risks with likelihood/impact matrix, mitigation action plans, and automated risk status monitoring.
- **Graduate Tracker**: Historical graduation tracking per program, academic year, and term.
- **Executive Full Report Export**: Single-click multi-tab styled Excel report (.xlsx) covering executive summary, program statuses, compliance logs, risk register, and graduation statistics.
- **Role-Based Scoping**: Scoped views and action permissions for QA Admins, Deans, Program Chairs, and Unit Heads.
- **Automated Alerts & Notifications**: Security headers, email notifications, and expiration monitors.

---

## Requirements

| Tool / Dependency | Recommended Version |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| MySQL or SQLite | MySQL 8.0+ or SQLite 3.x |

> **PHP Extensions required:** `pdo_mysql` or `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath`, `gd` / `zip` (for spreadsheet export).

---

## Quick Setup Guide

### 1. Clone the Repository

```bash
git clone https://github.com/AimStarry/QA-Portal-Capstone.git
cd QA-Portal-Capstone
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Frontend dependencies
npm install
```

### 3. Configure Environment

Copy the example environment file:

```bash
# Windows (PowerShell)
Copy-Item .env.example .env

# macOS / Linux
cp .env.example .env
```

Generate the application encryption key:

```bash
php artisan key:generate
```

### 4. Database Setup & Seeding

Configure your database connection in `.env` (MySQL or SQLite).

Run migrations and seed mock data:

```bash
php artisan migrate --seed
```

> **Seeded Mock Accounts (Default Password: `password`):**
> - **QA Admin**: `admin` / `admin@example.edu`
> - **QA Officer**: `qaoadmin` / `qao@example.edu`
> - **Dean (School of Computing)**: `dean_soc` / `dean.soc@example.edu`
> - **Dean (Engineering & Architecture)**: `dean_sea` / `dean.sea@example.edu`
> - **Unit Head (Campus Planning)**: `unit_cpo` / `cpo@example.edu`
> - **Unit Head (IT Services)**: `unit_itso` / `itso@example.edu`

### 5. Build Assets & Start Local Server

```bash
# Build frontend assets
npm run build

# Start Laravel development server
php artisan serve
```

The portal will be accessible at `http://127.0.0.1:8000`.

---

## Automated Testing

Run the full PHPUnit test suite:

```bash
php vendor/bin/phpunit
```

---

## License

Proprietary academic software for quality assurance and accreditation management.
