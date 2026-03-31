# Fase 1: Definir BASE_PATH y $gameDomain en config.php

## Objetivo
Crear las constantes y variables centrales que serán la base de todas las fases siguientes.

## Tareas

### config.php
- [ ] Agregar `define('BASE_PATH', __DIR__ . '/');` al inicio del archivo
- [ ] Agregar `$gameDomain = 'localhost';`
- [ ] Agregar `$gameDomainProto = 'http://' . $gameDomain;`
- [ ] Actualizar `$wikiPath` existente para usar `$gameDomainProto . '/wiki/'`
- [ ] Agregar `$forumDomain = 'forum.' . $gameDomain;`
- [ ] Agregar `$contactEmail = 'contact@' . $gameDomain;`

## Verificación
```bash
php -l config.php
php -r "require 'config.php'; echo BASE_PATH . PHP_EOL; echo \$gameDomain . PHP_EOL;"
```
