# TR Tracking

**Web application for investment tracking and management.**

TR Tracking is a self-hosted PHP web application that lets you centralise your investment portfolio using your own interface, with data stored in MySQL and deployed via Docker.

<p align="center">
  <img src="docs/images/logo.png" alt="TR Tracking" width="180">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white" alt="MySQL 8.4">
  <img src="https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white" alt="Docker Compose">
  <img src="https://img.shields.io/badge/License-TBD-lightgrey" alt="Licence TBD">
</p>

---

## Screenshots

A selection of screenshots showing the interface and main features.

### Dashboard

Main overview of the application.

![TR Tracking Dashboard](docs/images/dashboard.png)

### Investment portfolio

View and track your recorded investments.

![Investment portfolio](docs/images/portfolio.png)

### Investment management

Interface for recording and managing buy and sell operations.

![Investment management](docs/images/investments.png)

### Asset management

Browse and administer your registered assets.

![Asset management](docs/images/assets.png)
![Asset management](docs/images/assets-type.png)

### Settings

Account configuration options.

![TR Tracking Settings](docs/images/settings.png)

---

## Features

- **Dashboard:** summary of invested capital, sales, gains, fees, total cost and operation count, with charts (distribution by type and by asset, cumulative capital, monthly contribution, monthly sales and gains).
- **Portfolio:** filterable breakdown of your portfolio by asset type and by individual asset, including invested capital, sold amounts and gains.
- **Investment management:** record and manage buy and sell operations with filters (type, asset, operation, date range, free-text search) and pagination.
- **Scheduled purchases (DCA):** configure recurring buy tasks at six frequencies (weekly, monthly, twice a month, quarterly) with start date, optional end date, amount and commission. Occurrences appear in a confirmation queue — you enter the exact purchase date before the investment is recorded.
- **Asset management:** create and edit assets with ticker, ISIN, currency and description; asset detail with full investment history.
- **Asset types:** organise assets into custom categories with automatic colour coding.
- **Settings:** switch between Spanish and English (also changes amount and date formats); change your password.
- **Security:** per-user data isolation, CSRF protection, brute-force login throttling, bcrypt passwords, PDO prepared statements.
- **Version check:** displays the installed version and checks for new releases on GitHub.

---

## Tech stack

| Technology | Role |
|---|---|
| PHP 8.3 | Application logic (custom MVC, no framework) |
| Apache | Web server (mod_rewrite, single entry point) |
| MySQL 8.4 | Data persistence |
| Docker | Containerisation |
| Docker Compose | Service orchestration |
| Bootstrap 5.3 | UI (CDN) |
| Chart.js 4.4 | Charts (CDN) |
| GitHub Actions | CI (PHP lint, translation check, Docker build) |

---

## Requirements

- Docker Engine
- Docker Compose v2 (`docker compose`)
- A browser with internet access (Bootstrap and Chart.js are loaded from jsDelivr CDN)

No PHP or MySQL installation required on the host. The application also runs on ARM devices (e.g. Raspberry Pi) as long as the Docker image is compatible.

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/ForgeRGX/TR-Tracking.git
cd TR-Tracking
```

### 2. Configure environment variables

```bash
cp .env.example .env
```

Edit `.env` and set at minimum the database credentials and the web port. **Never commit `.env` or real credentials to the repository.**

| Variable | Purpose |
|---|---|
| `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` | Database and application user |
| `MYSQL_ROOT_PASSWORD` | MySQL root password (used by backup/migration scripts) |
| `WEB_PORT` | Exposed web port (default `8080`) |
| `DB_HOST_PORT` | Local MySQL port for SQL clients (default `3307`, bound to `127.0.0.1` only) |
| `ALLOW_REGISTRATION` | `true` to allow sign-ups at `/register`; set to `false` once your users are created |
| `APP_DEBUG` | `true` shows detailed errors (development only) |
| `TZ` | Timezone (default `Europe/Madrid`) |

### 3. Build and start the services

```bash
docker compose build
docker compose up -d
```

Check container status:

```bash
docker compose ps
```

View logs if you need to diagnose a problem:

```bash
docker compose logs --tail=100
```

### 4. Access the application

Open the address configured in your deployment in a browser.

On a fresh install the database schema is applied automatically from `database/schema.sql`. The first user who registers always succeeds (even if `ALLOW_REGISTRATION=false`) and gets four default asset types created automatically (Brokerage, Private Markets, Fixed Income, Crypto Wallet).

If you use a reverse proxy (e.g. Nginx), make sure the domain and routing point to the web service correctly.

---

## Upgrading from a previous version

### From v1.0.0 → v2.0.0

1. **Back up your data first:**

```bash
scripts/backup.sh
```

2. **Pull the latest code:**

```bash
git pull origin main
```

3. **Rebuild the Docker image:**

```bash
docker compose up -d --build
```

4. **Run the database migrations** (idempotent — safe to run multiple times):

```bash
scripts/migrate.sh
```

This applies only the pending migrations:
- `003_operation_type.sql` — adds `operation_type` to `investments` (existing records become purchases)
- `004_scheduled_purchases.sql` — adds `scheduled_purchases` and `scheduled_purchase_occurrences` tables

---

## Common operations

```bash
docker compose ps                   # show service status
docker compose logs -f              # live logs (Ctrl+C to exit)
docker compose restart              # restart containers
docker compose down                 # stop and remove containers (data is preserved)
docker compose up -d --build        # rebuild after code or Dockerfile changes
docker compose down -v              # WARNING: also deletes the mysql_data volume (all data lost)
```

---

## Backup and restore

```bash
scripts/backup.sh                                   # creates backups/investments_YYYY-MM-DD.sql
scripts/restore.sh backups/investments_2026-10-14.sql
```

`backup.sh` never overwrites: if a file for the current day already exists, it appends the time to the filename. On Windows, use Git Bash / WSL or run the commands manually:

```bash
# Backup
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -u root --single-transaction --no-tablespaces "$MYSQL_DATABASE"' > backups/investments_2026-10-14.sql
# Restore
docker compose exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -u root "$MYSQL_DATABASE"' < backups/investments_2026-10-14.sql
```

Keep copies of `backups/` outside your machine — they are not tracked by git.

---

## Project structure

```text
TR-Tracking/
├── config/          # App configuration and route definitions
├── controllers/     # MVC controllers
├── core/            # Framework kernel (Router, Database, Auth, I18n, View, helpers)
├── database/
│   ├── schema.sql           # Full schema (auto-applied on first run)
│   └── migrations/          # Incremental migrations for existing installs
├── lang/            # Translations (en.php — keys are Spanish strings)
├── models/          # Data models (static classes, PDO queries)
├── public/          # Front controller (index.php) and static assets
│   └── static/      # CSS, JavaScript, images
├── scripts/         # backup.sh, restore.sh, migrate.sh, reset_password.sh, lint.sh
├── views/           # PHP view templates
│   ├── dashboard/
│   ├── portfolio/
│   ├── investments/
│   ├── scheduled/   # Scheduled purchases (DCA)
│   ├── assets/
│   ├── types/
│   ├── partials/
│   └── errors/
├── docs/
│   └── images/      # Screenshots and visual assets
├── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## Security

- All queries use PDO prepared statements — no SQL injection.
- All output is escaped with `htmlspecialchars` — no XSS.
- CSRF token validated on every POST request.
- Passwords stored as bcrypt hashes; session regenerated on login, logout and password change.
- Brute-force protection: 5 failures per user+IP or 15 per IP in 15 minutes temporarily blocks login.
- Data is strictly isolated per user — opening another user's URL returns 404.
- Apache headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Options -Indexes`.
- Database is only accessible from `127.0.0.1` on the host. Do not expose it directly to the internet.

---

<<<<<<< HEAD
## Licencia
=======
## Versions and updates

TR Tracking uses [SemVer](https://semver.org/) (`MAJOR.MINOR.PATCH`) and publishes stable releases on GitHub.

The application displays the installed version and checks for newer releases automatically.

**[Releases → github.com/ForgeRGX/TR-Tracking/releases](https://github.com/ForgeRGX/TR-Tracking/releases)**

---

## Licence
>>>>>>> a7320ac (feat: add scheduled purchases (DCA) and v2.0.0 release)

The project licence is pending definition.

<<<<<<< HEAD
Hasta que se incluya un archivo `LICENSE` con los términos correspondientes, no se debe asumir que el código está disponible para su reutilización o redistribución sin restricciones.
=======
Until a `LICENSE` file with the applicable terms is included, no assumption of free reuse or redistribution should be made.
>>>>>>> a7320ac (feat: add scheduled purchases (DCA) and v2.0.0 release)

---

<p align="center">
  <strong>TR Tracking</strong><br>
<<<<<<< HEAD
  Seguimiento de inversiones en un entorno propio, flexible y autogestionado.
=======
  Investment tracking on your own infrastructure — flexible, private and self-managed.
>>>>>>> a7320ac (feat: add scheduled purchases (DCA) and v2.0.0 release)
</p>
