# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Hacker Experience Legacy — a browser-based hacking simulation game originally built 2012-2014. Migrated to PHP 8 + MariaDB unified stack (no Python).

## Tech Stack

- **Backend:** PHP 8.4 (no framework, no autoloader, no Composer)
- **Database:** MariaDB 11.8 via PDO (`classes/PDO.class.php` singleton, socket at `/run/mysqld/mysqld.sock`)
- **Cron jobs:** All PHP in `cron/` (see `crontab` for schedule)
- **Frontend:** Server-rendered PHP templates in `template/`, JS in `js/`, CSS in `css/`
- **Email:** Stubbed (`classes/SES.class.php` logs instead of sending — replace with real mailer)
- **HTML sanitization:** Stubbed (`classes/Purifier.class.php` uses strip_tags — replace with Composer HTMLPurifier)
- **Forum:** Stubbed (`classes/Forum.class.php` returns empty — forum was removed)

## Architecture

No MVC framework. Each top-level `.php` file is a page/route. AJAX calls go through `ajax.php` dispatching on `$_POST['func']`.

**Configuration (`config.php`):**
- `BASE_PATH` — root directory constant, used by all requires/includes
- `$gameDomain` — configurable domain (currently `minion.twentic.com:8080`)
- `$gameDomainProto`, `$forumDomain`, `$wikiPath`, `$contactEmail` — derived from domain

**Key classes (`classes/`):**
- `Database.class.php` (`LRSys`) — main game logic: registration, login, hacking, software, hardware, processes
- `Session.class.php` — session management + i18n (gettext)
- `PDO.class.php` — database connection singleton
- `Process.class.php` — game process/action system
- `Player.class.php` / `PC.class.php` — player and computer abstractions
- `UserCreator.class.php` — new user initialization (11 table inserts)
- `ProfileGenerator.class.php` — generates static HTML profile pages
- `BadgeManager.class.php` — badge award system with validation
- `NPCGenerator.class.php` — NPC initialization from JSON
- `SoftwareGenerator.class.php` / `RiddleSoftwareGenerator.class.php` — NPC software from JSON
- `NPCWebGenerator.class.php` — NPC web content template processor

**Cron scripts (`cron/`):**
- Every minute: `doomUpdater.php`, `newRoundUpdater.php`
- Every 5min: `fbiUpdate.php`
- Every 10min: `defcon2.php`, `endWar2.php`
- Every 15min: `removeExpiredLogins.php`, `restoreNPC.php`, `generateMissions.php`
- Hourly: `updateCurStats.php`, `updateRanking.php`, `rankGenerator.php`, `badgeHunterAll.php`, cleanup scripts
- Every 3h: `antivirusNPC.php`

## Running the Development Server

```bash
php -S 0.0.0.0:8080 -t /home/openclaw/he/legacy-master/ > server.log 2>&1 &
```

## Database Setup

```bash
mariadb -e "CREATE DATABASE game CHARACTER SET utf8mb4; CREATE USER 'he'@'localhost' IDENTIFIED BY 'helegacy2024'; GRANT ALL ON game.* TO 'he'@'localhost';"
mariadb game < game.sql
```

## Important Strings to Search For

- `REDACTED` — placeholders for API keys/passwords that need real values (Facebook, Twitter, Pagarme, backup S3)
- `2019:` — author comments explaining obscure logic
- `TODO:` — items marked for future work during migration

## Stubs That Need Real Implementations

- `classes/SES.class.php` — email sending (replace with PHPMailer or similar via Composer)
- `classes/Purifier.class.php` — HTML sanitization (replace with HTMLPurifier via Composer)
- `classes/Forum.class.php` — forum integration (removed, returns empty)

## Localization

Uses PHP gettext. Translation files in `locale/`. Base template: `defaults.po`.

## Game Data

JSON config in `json/` defines NPCs, software, riddles, certificates, badges. Plain-text docs in `info/`.
