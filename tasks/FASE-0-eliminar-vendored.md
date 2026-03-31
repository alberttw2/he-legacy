# Fase 0: Eliminar dependencias vendorizadas

## Objetivo
Eliminar todo el código de terceros que no se va a migrar y crear stubs mínimos para evitar errores fatales.

## Tareas

### Borrar directorios
- [ ] `forum/` (phpBB3)
- [ ] `wiki/` (DokuWiki)
- [ ] `HTMLPurifier/`
- [ ] `ses/` (AWS SDK)
- [ ] `font-awesome/`

### Borrar archivos
- [ ] `classes/PHPMailer.class.php`
- [ ] `cron/backup_forum.php`

### Crear stubs

- [ ] **`classes/Purifier.class.php`** — Reemplazar require de HTMLPurifier con fallback:
  - Constructor: no requiere librería externa
  - `purify($text)`: retorna `htmlspecialchars($text, ENT_QUOTES, 'UTF-8')`
  - Usado en: `classes/Mail.class.php`, `classes/PC.class.php`, `classes/Clan.class.php`, `webserver.php`

- [ ] **`classes/SES.class.php`** — Stub que loguea en vez de enviar:
  - Eliminar `require '/var/www/ses/aws-autoloader.php'`
  - Método de envío: `error_log("Email stub: $to - $subject")`
  - Usado en: `classes/EmailVerification.class.php`, `classes/Premium.class.php`, `reset.php`

- [ ] **`classes/Forum.class.php`** — Stub que retorna vacío:
  - Eliminar includes de phpBB3
  - `showPosts()`: retorna string vacío
  - Usado en: `classes/Database.class.php`, `classes/Player.class.php`, `template/tttpl.php`, `template/fbtpl.php`, `logout.php`

### Limpieza crontab
- [ ] Eliminar entrada de `backup_forum.php` del `crontab`
- [ ] Eliminar entradas de piwik/log-analytics del `crontab`

## Verificación
```bash
# Confirmar que los directorios no existen
ls forum/ wiki/ HTMLPurifier/ ses/ font-awesome/ 2>&1 | grep "No such file"

# Confirmar que los stubs existen y tienen sintaxis válida
php -l classes/Purifier.class.php
php -l classes/SES.class.php
php -l classes/Forum.class.php
```
