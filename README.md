# QA Portal — Accreditation Management System

A web-based Quality Assurance portal for managing program accreditations, compliance records, risk items, and graduate tracking across colleges and academic units.

Built with **Laravel 11** · **MySQL** · **Vite** · **Blade**

---

## Requirements

Make sure the following are installed before setting up the project:

| Tool | Minimum Version |
|------|----------------|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| MySQL | 8.0+ |

> **PHP Extensions required:** `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `bcmath`

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

### 4. Create the MySQL Database

Log in to your MySQL server and create the database:

```sql
-- Log in to MySQL (use your credentials)
mysql -u root -p

-- Inside the MySQL shell:
CREATE DATABASE accreditation_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Optional: create a dedicated user instead of using root
CREATE USER 'qa_user'@'localhost' IDENTIFIED BY 'your_password_here';
GRANT ALL PRIVILEGES ON accreditation_db.* TO 'qa_user'@'localhost';
FLUSH PRIVILEGES;

EXIT;
```

> If you are using **phpMyAdmin**, you can create the database through the GUI:
> 1. Open phpMyAdmin → click **New**
> 2. Database name: `accreditation_db`
> 3. Collation: `utf8mb4_unicode_ci`
> 4. Click **Create**

### 5. Configure Environment

Copy the example environment file and generate your application key:

```bash
# Windows (PowerShell)
Copy-Item .env.example .env

# macOS / Linux
cp .env.example .env

php artisan key:generate
```

Open `.env` and update the database and app settings:

```env
APP_NAME="QA Portal"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=accreditation_db
DB_USERNAME=root
DB_PASSWORD=your_mysql_password_here
```

> **Leave `DB_PASSWORD` empty if your local MySQL root has no password.**

#### Mail Configuration (Optional for local dev)

If you want email features (OTP, notifications) to work, configure SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password_here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="QA Portal"
```

> For Gmail, use an **App Password** (not your account password).
> Go to: Google Account → Security → 2-Step Verification → App Passwords

> To skip email entirely during development, set `MAIL_MAILER=log` — all emails will be written to `storage/logs/laravel.log` instead.

### 6. Run Migrations

This creates all tables in your MySQL database:

```bash
php artisan migrate
```

> If you ever need to reset to a completely clean database:
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

> No default admin account is seeded. Create one manually via `php artisan tinker`:
> ```php
> \App\Models\User::create([
>     'name'     => 'Admin',
>     'email'    => 'admin@example.com',
>     'password' => bcrypt('password'),
>     'usertype' => 'QA Admin',
> ]);
> ```

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
# Run all pending migrations
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

## Troubleshooting

**`SQLSTATE[HY000] [1049] Unknown database`**
→ The `accreditation_db` database does not exist yet. Run Step 4 to create it.

**`Access denied for user 'root'@'localhost'`**
→ Wrong MySQL username or password in `.env`. Double-check `DB_USERNAME` and `DB_PASSWORD`.

**`php_network_getaddresses: getaddrinfo failed`**
→ MySQL is not running. Start it via XAMPP, Laragon, or `net start mysql`.

**`Class "PDO" not found`**
→ The `pdo_mysql` PHP extension is not enabled. Enable it in your `php.ini` by uncommenting `extension=pdo_mysql`.

---

## License

This project is proprietary software developed for internal academic accreditation management.
All data handled by this system may be subject to confidentiality agreements.
