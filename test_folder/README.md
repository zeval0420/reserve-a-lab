# Academic Competition / Quiz Battle System

A reusable competition engine for live academic quiz events (Sci-Math
Battle, Science Quiz Bee, etc.) — event configuration is entirely
database-driven, nothing is hard-coded per event.

Stack: **PHP 8.3 (plain, no framework) + PDO + MariaDB**, matching the
existing HTML/CSS/JS/PHP + MariaDB stack this project builds on.

## Requirements

- PHP 8.1+ with these extensions: `pdo_mysql`, `gd`, `fileinfo`, `mbstring`, `json`, `session`
  (gd + fileinfo are required for secure image upload handling)
- MariaDB 10.6+ (or MySQL 8+)
- A web server that can point its document root at `public/` (Apache,
  Nginx, or PHP's own built-in server for local dev)

## Setup

1. **Create the database and a dedicated app user:**

   ```sql
   CREATE DATABASE academic_competition CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'acs_user'@'localhost' IDENTIFIED BY 'dev_password_change_me';
   GRANT ALL PRIVILEGES ON academic_competition.* TO 'acs_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Configure connection settings** via environment variables (or edit the
   fallback defaults directly in `config/database.php` for local dev):

   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=academic_competition
   DB_USERNAME=acs_user
   DB_PASSWORD=dev_password_change_me
   ```

   And admin login credentials (also env-driven, see `config/app.php`):

   ```
   ADMIN_USERNAME=admin
   ADMIN_PASSWORD_HASH=<output of: php -r "echo password_hash('yourpassword', PASSWORD_BCRYPT);">
   ```

   Without these set, local dev falls back to `admin` / `admin123` — fine
   for testing, **never use the fallback in a real deployment**.

3. **Run migrations** (creates all tables; safe to re-run — only new files
   in `database/migrations/` get applied):

   ```
   php database/migrate.php
   php database/migrate.php --status   # check what's applied
   ```

4. **(Optional) Load sample data** — one fake demo event with categories,
   contestants, and questions, so there's something to click through:

   ```
   php database/seed.php
   ```

5. **Point your web server's document root at `public/`.** For local dev,
   PHP's built-in server works fine:

   ```
   php -S 127.0.0.1:8000 -t public
   ```

   For a real Apache/Nginx deployment, set the vhost's document root to the
   `public/` folder specifically — not the project root. Everything outside
   `public/` (config, src, database) should not be web-accessible at all.

## There is no single index.php — three separate front ends

This app has three distinct interfaces, each with its own entry point
under `public/`, plus a couple of small JSON APIs. There's nothing to visit
at the bare domain root (`/`) — go directly to one of these:

| URL | What it is | Auth |
|---|---|---|
| `/admin/login.php` | Admin: create/configure events, categories, contestants, questions, settings | Login required |
| `/admin/index.php` | Admin: list of events | Login required |
| `/operator/index.php` | Operator: pick an event to run live | Login required (same account as admin) |
| `/operator/event.php?id=N` | Operator: the live control panel (timer, scoring, navigation) | Login required |
| `/display/event.php?id=N` | **The projector/public screen.** Full-screen, no login, no admin controls. | None — safe to open on a venue projector with no account |
| `/api/runtime.php` | JSON API backing the operator panel | Login required |
| `/api/display.php` | JSON API backing the public display (read-only) | None |

**Typical event-day setup:** operator's laptop opens
`/operator/event.php?id=N` (logged in); the projector/screen opens
`/display/event.php?id=N` in another browser/device (no login needed) —
they can be on completely different machines, the display just polls the
same server state every second.

### First-time walkthrough
1. Log in at `/admin/login.php`.
2. `/admin/index.php` → create an event, fill in branding/date.
3. On the event's page: add categories, contestants, and questions
   (upload slide images, set points/time — leave blank to use event
   defaults). Configure scoring/timing/presentation in the Settings tab.
4. Once it has at least one contestant and one question, mark the event
   **Ready**.
5. Go to `/operator/index.php`, open the event, click **Start Event**.
6. Open `/display/event.php?id=N` on the projector.
7. Run the competition from the operator panel — the public display
   updates on its own.

## Project structure

```
config/
  database.php   — DB connection settings (env-driven)
  app.php        — admin credentials + upload size/type limits (env-driven)
  constants.php  — EventStatus / AnswerResult / DisplayState / RankingOrder enums
database/
  migrations/    — one .sql file per schema change, numbered in order
  migrate.php    — migration runner (idempotent)
  seeders/, seed.php — sample dev data
src/
  Database.php   — PDO singleton
  bootstrap.php  — shared include: session start + all models/support/services
  Models/        — Event, EventSetting, Category, Contestant, Question,
                   ScoreEntry, ScoreAdjustment, CompetitionSession
  Support/       — Auth, Csrf, Flash, Validator, Uploader, EventValidator
  Services/
    CompetitionRuntime.php — the competition engine: timer, navigation,
      scoring, ranking. Single source of truth; both the operator API and
      the public display API call this, never each other.
public/
  admin/         — admin UI (event/category/contestant/question CRUD)
  operator/      — live control panel
  display/       — public/projector screen
  api/
    runtime.php  — JSON API for the operator panel (authenticated)
    display.php  — JSON API for the public display (unauthenticated, read-only)
  assets/        — admin.css/js, operator.css/js, display.css/js
  uploads/       — validated, re-encoded images land here (logos, covers,
                   question slides, contestant logos), namespaced by event id
```

## Uploads

Handled by `src/Support/Uploader.php`: validates the real MIME type (via
`fileinfo`, not the client-supplied header), enforces a size limit,
confirms the file actually decodes as an image, then **re-encodes it via
GD** — which strips any non-pixel payload a malicious file might carry —
and writes it under a randomly generated filename. The original filename
and bytes are never trusted or stored as-is.

## Verifying the install

```
php database/migrate.php --status   # all migrations applied
php database/seed.php               # loads a sample event (id 1)
php -S 127.0.0.1:8000 -t public
```

Then visit `http://127.0.0.1:8000/admin/login.php` and log in. You should
see the seeded sample event in the events list.
