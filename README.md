# QA Portal — Accreditation Management System

A web-based Quality Assurance portal for managing program accreditations, compliance records, risk items, and graduate tracking across colleges and academic units.

Built with **Laravel 11** · **SQLite** · **Vite** · **Blade**

---

## Requirements

Make sure the following are installed before setting up the project:

| Tool | Minimum Version |
|------|----------------|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| SQLite | 3.x (usually bundled with PHP) |

> **PHP Extensions required:** `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath`

---

## Local Setup

### 1. Clone the Repository

```bash
git clone https://github.com/AimStarry/QA-Portal-Capstone.git
cd QA-Portal-Capstone
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node Dependencies

```bash
npm install
```

### 4. Configure Environment

Copy the example environment file and generate your application key:

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and update the app name (the rest of the defaults work for local SQLite):

```env
APP_NAME="QA Portal"
APP_URL=http://localhost:8000
```

> **Mail:** By default, mail is set to `log` driver — emails are written to `storage/logs/laravel.log` instead of being sent. No mail server is needed for local development.

### 5. Create the SQLite Database File

```bash
# Windows (PowerShell)
New-Item -ItemType File -Path database\database.sqlite

# macOS / Linux
touch database/database.sqlite
```

### 6. Run Migrations

This creates all tables in the fresh SQLite database:

```bash
php artisan migrate
```

> If you ever need to reset to a clean slate:
> ```bash
> php artisan migrate:fresh
> ```

### 7. Create the Storage Symlink

Required for serving uploaded files (logos, documents, etc.):

```bash
php artisan storage:link
```

### 8. Build Frontend Assets

```bash
# Development (one-time build)
npm run build

# — OR — watch for changes during development
npm run dev
```

### 9. Start the Development Server

Open a **separate terminal** (if `npm run dev` is already running) and start Laravel:

```bash
php artisan serve
```

The application will be available at **http://localhost:8000**

---

## Default User Roles

| Role | Description |
|------|-------------|
| `QA Admin` | Full access — manages all accreditations, compliance, and users |
| `Dean` | College-level view of programs and compliance records |
| `Program Chair` | Program-level compliance and accreditation tracking |
| `Responsible Unit` | Submits and tracks compliance documents for assigned items |

> Users are created through the registration page or seeded manually. No default admin account is seeded — create one via `php artisan tinker` if needed.

---

## Project Structure

```
app/
├── Http/Controllers/     # Route controllers
├── Models/               # Eloquent models
├── Observers/            # Model event observers
database/
├── migrations/           # All table schema definitions
├── seeders/              # Optional data seeders
resources/
├── views/                # Blade templates
routes/
└── web.php               # Application routes
```

---

## Common Artisan Commands

```bash
# Run all migrations
php artisan migrate

# Reset and re-run all migrations (wipes all data)
php artisan migrate:fresh

# Clear all caches
php artisan optimize:clear

# List all registered routes
php artisan route:list

# Open interactive PHP shell
php artisan tinker
```

---

## License

This project is proprietary software developed for internal academic accreditation management.
All data handled by this system may be subject to confidentiality agreements.
