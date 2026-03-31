# Fase 6: Verificación final

## Objetivo
Confirmar que toda la migración es correcta y no quedan restos del stack anterior.

## Tareas

### Syntax checks
- [ ] PHP syntax check en todos los archivos:
  ```bash
  find . -name '*.php' | xargs -I{} php -l {} 2>&1 | grep -v "No syntax errors"
  ```

### Paths hardcodeados
- [ ] No quedan `/var/www/` en PHP (excepto comentarios):
  ```bash
  grep -rn '/var/www/' --include='*.php' | grep -v '^\s*//' | grep -v '2019:' | grep -v 'REDACTED'
  ```

### Dominios hardcodeados
- [ ] No quedan `hackerexperience.com` en código activo:
  ```bash
  grep -rn 'hackerexperience\.com' --include='*.php' | grep -v README | grep -v LICENSE | grep -v '2019:' | grep -v '//'
  ```

### Python eliminado
- [ ] No existen directorios Python:
  ```bash
  ls python/ cron2/ 2>&1
  ```
- [ ] No quedan archivos .py o .sh:
  ```bash
  find . -name '*.py' -o -name '*.sh'
  ```
- [ ] No quedan llamadas a Python en PHP:
  ```bash
  grep -rn "exec.*python\|system.*python\|usr/bin.*python" --include='*.php'
  ```
- [ ] `classes/Python.class.php` no existe:
  ```bash
  ls classes/Python.class.php 2>&1
  ```

### Vendored eliminado
- [ ] No existen directorios vendorizados:
  ```bash
  ls forum/ wiki/ HTMLPurifier/ ses/ font-awesome/ 2>&1
  ```
- [ ] `classes/PHPMailer.class.php` no existe

### Stubs funcionales
- [ ] `classes/Purifier.class.php` — syntax OK + método purify() funciona
- [ ] `classes/SES.class.php` — syntax OK
- [ ] `classes/Forum.class.php` — syntax OK

### Integridad del crontab
- [ ] No hay python en crontab
- [ ] No hay /var/www/ en crontab
- [ ] Todos los scripts referenciados existen
- [ ] No hay entradas de forum/wiki/piwik

### Resumen post-migración
- [ ] Actualizar `CLAUDE.md` con nuevo stack (PHP 8 only)
- [ ] Actualizar `README.md` eliminando referencias a Python 2
- [ ] Verificar que `game.sql` sigue siendo compatible con MySQL 8 / MariaDB 10+
