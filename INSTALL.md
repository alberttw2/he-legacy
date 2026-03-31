# Installation Guide

## Requirements

- **PHP 8.1+** with extensions: pdo, pdo_mysql, mysqli, mbstring, xml, gd, gettext, intl, curl
- **MariaDB 10.6+** or **MySQL 8.0+**
- **OS:** Linux (tested on Debian 13)

## Quick Start

### 1. Install dependencies

**Debian/Ubuntu:**
```bash
sudo apt-get update
sudo apt-get install -y php php-cli php-pdo php-mysql php-mbstring php-xml php-gd php-intl php-curl gettext mariadb-server
```

**RHEL/Fedora:**
```bash
sudo dnf install php php-cli php-pdo php-mysqlnd php-mbstring php-xml php-gd php-intl php-curl gettext mariadb-server
```

### 2. Start MariaDB

```bash
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### 3. Create database and user

```bash
sudo mariadb -e "
  CREATE DATABASE IF NOT EXISTS game CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
  CREATE USER IF NOT EXISTS 'he'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';
  GRANT ALL PRIVILEGES ON game.* TO 'he'@'localhost';
  FLUSH PRIVILEGES;
"
```

### 4. Import schema

```bash
sudo mariadb game < game.sql
```

### 5. Configure the application

Edit `config.php`:
```php
$gameDomain = 'yourdomain.com';           // Your domain
$gameDomainProto = 'https://' . $gameDomain;  // Protocol + domain
```

Edit `classes/PDO.class.php`:
- Update the socket path if different from `/run/mysqld/mysqld.sock`
- Update the password to match what you set in step 3

Alternatively, find the correct socket:
```bash
mariadb -e "SELECT @@socket;"
```

### 6. Run seed script

This creates the initial game data: first round, admin account, test player, NPCs, and mission templates.

```bash
php scripts/seed.php
```

Output should show:
- 1 round created
- 1 admin account (user: `admin`, pass: `admin123`)
- 1 test player (user: `testplayer`, pass: `test123`)
- 103 NPCs generated
- 629 software entries
- 8 mission seeds

**Change the default passwords immediately after setup.**

### 7. Start the development server

```bash
php -S 0.0.0.0:8080 -t /path/to/legacy-master/
```

Visit `http://localhost:8080/` to see the login page.

### 8. Log in

- **Player login:** `testplayer` / `test123`
- **Admin panel:** `admin` / `admin123`

## Production Deployment

For production, use a proper web server instead of PHP's built-in server:

### Nginx + PHP-FPM

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/legacy-master;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Cron Jobs

Install the crontab for background game operations:

```bash
# Edit the GAME_PATH and PHP variables in crontab first, then:
crontab crontab
```

## REDACTED Values

Search for `REDACTED` in the codebase to find placeholders that need real values:

```bash
grep -rn 'REDACTED' --include='*.php'
```

These include:
- Facebook/Twitter OAuth keys (optional, social login)
- Pagarme payment gateway key (optional, premium features)
- S3 backup credentials (optional, in `cron/backup_game.php`)

## Stubs

The following features are stubbed and need real implementations for full functionality:

| Stub | File | Purpose | Replacement |
|------|------|---------|-------------|
| Email | `classes/SES.class.php` | Email sending (verification, notifications) | PHPMailer via Composer |
| HTML Purifier | `classes/Purifier.class.php` | HTML sanitization for user input | HTMLPurifier via Composer |
| Forum | `classes/Forum.class.php` | Forum integration | phpBB, Discourse, or custom |

## Directory Structure

```
legacy-master/
├── config.php          # Game constants, BASE_PATH, domain config
├── connect.php         # DB connection params (legacy, PDO.class.php is primary)
├── game.sql            # Database schema
├── scripts/seed.php    # Initial data seeding
├── crontab             # Cron schedule for all background jobs
├── classes/            # PHP classes (game logic, generators, etc.)
├── cron/               # Background job scripts
├── template/           # Page templates
├── json/               # Game data (NPCs, software, badges, riddles)
├── js/                 # JavaScript
├── css/                # Stylesheets
├── locale/             # Translation files (gettext)
├── info/               # Plain-text game mechanics documentation
└── html/               # Generated static pages (profiles, rankings, fame)
```
