# Changelog

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and versioned with [SemVer](https://semver.org/).

## [Unreleased]

## [2.0.0] - 2026-10-14

### Added
- **Sales support:** the "New investment" button is split into **Buy** and **Sell**. Sales subtract from invested capital across all totals, breakdowns and charts.
- **New chart "Sales and gains by month"** (sales, gains = sales − fee, and cumulative gains) and new KPI cards "Total sold" and "Gains".
- "Sold" and "Gains" columns in Portfolio and in the asset detail view; filter by operation type (purchases / sales).
- **Scheduled purchases (DCA):** new section to configure recurring buy tasks with six frequencies:
  - Weekly (every Monday)
  - Monthly — first business day of the month
  - Monthly — first business day on or after the 15th
  - Twice a month (both of the above)
  - Quarterly — first business day of the month
  - Quarterly — first business day on or after the 15th
- Each scheduled task stores the start date, optional end date, amount, commission and notes.
- Occurrences are generated lazily on each visit to `/scheduled` or the dashboard; once past their expected date they appear in a **"Awaiting confirmation"** queue where the user enters the exact purchase date before the investment is recorded.
- Pending-count badge on the "Scheduled" nav item and an alert widget on the dashboard with the next 5 upcoming purchases.
- Tasks can be paused/resumed independently. History of confirmed and skipped occurrences per task.
- Migration `004_scheduled_purchases.sql` — adds tables `scheduled_purchases` and `scheduled_purchase_occurrences`; run `scripts/migrate.sh`.

### Changed
- "Total invested" and "Invested capital" are now net values (purchases − sales).
- "Monthly contribution" and "Cumulative capital" are net; months where sales exceed purchases are shown in red.

## [1.0.0] - 2026-10-08

### Added
- Dashboard with invested capital, fees, total cost, number of investments and assets.
- Distribution by asset type and by asset (tables and charts), cumulative capital and monthly contribution.
- Portfolio, Investments, Assets (with detail view) and Asset Types pages, with combinable filters and pagination.
- Multi-user: registration and login, data isolated per user, login attempt rate limiting.
- Account settings: Spanish/English language and password change; `scripts/reset_password.sh` for forgotten passwords.
- Responsive interface with glass-morphism design; stacked tables on mobile; favicon.
- Docker Compose (web + MySQL 8.4 with persistent volume) and local phpMyAdmin.
- Backup, restore, migration and demo-data scripts.
- GitHub Actions CI: PHP syntax check, translation check and Docker validation.
