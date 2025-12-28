# Project Context

> Repository note: public-facing documents for the website stay under `docs/`; internal developer references live here in `documentation/` to avoid collisions.

## Overview

- Public-facing site for Scouting Veghel with static HTML and JavaScript under `index.html`, `calendar.html`, and `js/`.
- LESS sources in `css/*.less` compile to the published CSS (`style.min.css`, `calendar.min.css`).
- PHP backend in `php/` handles rental requests, calendar data, and confirmation workflows for the blokhut (clubhouse) reservations.
- Backend communicates with a MySQL database via stored procedures to manage huurders (tenants), reservations, and rental agreements.

## Runtime Stack

- PHP 8 (no Composer usage today; dependencies are committed locally under `php/subm/`).
- MariaDB 10.6 (schema exports now stored under `database/`).
- PHPMailer bundled under `php/subm/PHPMailer/` for SMTP delivery via Outlook 365.

## Configuration

- Secrets are not tracked in Git; production servers supply the PHP files below:
  - `php/DB2.php` defines a `databaseMYSQLi()` helper returning a configured `mysqli` instance. Example snippet is documented in `README.md`.
  - `php/MAIL2.php` exposes SMTP settings (`$SMTP_SERVER`, `$SMTP_PORT`, `$SMTP_USER`, `$SMTP_PASSWORD`, `$SMTP_MAIL_FROM`). These feed `PhpMailerProxy.php`.
- No environment variables or `.env` file today; configuration is PHP-constant based.
- Local development provides templates `php/DB2.example.php` and `php/MAIL2.example.php`; copy them to the real filenames or rely on the Docker-provided environment variables (`SV_DB_*`, `SV_SMTP_*`).

## Database

- Schema dump files live in `database/` (tables for huurders, reserveringen, mutaties, notes, status, and verhuring records). `extra.sql` holds index and auto-increment statements.
- Application relies on stored procedures such as `GetHuurder`, `InsertReservering`, `GetReservations`, `GetVerhuringFromConfirm`, etc.; production definitions are still pending export, so local builds use simplified stand-ins.
- For local development we ship simplified stand-ins for the stored procedures in `database/stored_procedures.sql`; these mimic production behaviour with basic pass-through logic until the real routines can be exported.
- Data model links:
  - `verhuur_huurder` stores renter contact details.
  - `verhuur_reservering` tracks reservation windows and status.
  - `verhuur_verhuring` associates huurders with reservations and confirmation hashes.
  - Supporting tables manage notes, status history, and internal SV rental groups.

## Mail

- Outbound mail flows through `PhpMailerProxy.php`, using STARTTLS over SMTP (port comes from `MAIL2.php`, typically 587 for Outlook 365).
- Business emails originate from `website@scoutingveghel.nl`; reply-to defaults to the same unless overridden.

## Frontend Build

- Stylesheets are compiled with the `lessc` CLI (no dedicated npm script yet). Build scripts under `build/` primarily rewrite production references and should remain untouched until tests exist.
- JavaScript is plain ES5/ES6; minified copies (`*.origineel.js`) indicate prior manual builds.

## Local Environment Plan

- Docker Compose (`docker-compose.yml`) orchestrates:
  - **web**: PHP 8.2 + Apache (built from `docker/php/Dockerfile`) mounting the repository; uses env vars to resolve DB + SMTP.
  - **db**: MariaDB 10.6 seeded automatically from `database/` via the init directory; replace stubbed procedures or add `seed_data.sql` as needed.
  - **mailhog**: captures SMTP traffic on port `1025` with a web UI at `http://localhost:18025` that lists outgoing messages.
- Workflow: copy the example config files (or export the `SV_DB_*`/`SV_SMTP_*` variables), then run `docker compose up --build` to start the stack. The site is available on `http://localhost:8080`; MariaDB is exposed on `localhost:13306` for external clients.
- Next steps: provide optional seed data for realistic fixtures and script helpers to run LESS compilation or PHPUnit inside the containers.

## Testing Strategy

- PHPUnit (configured via `composer.json` and `phpunit.xml.dist`) covers utility helpers, database access layers (`db_layer.php`), reservation functions, template rendering, and mail delivery (via MailHog). Run with `docker compose run --rm web ./vendor/bin/phpunit` after `composer install`.

## Open Questions / TODOs

- Replace the simplified stored procedures with exports from production once they are available.
- Capture seed data (e.g., example huurders/reservations) for repeatable tests.
- Decide on directory for developer-specific secret files (`php/DB2.local.php`?) and how Docker compose will mount them.
- Document Outlook 365 SMTP specifics (ports, auth requirements) once verified.
- Consider lightweight JavaScript testing (e.g., Vitest/Jest) after PHP coverage stabilizes.
