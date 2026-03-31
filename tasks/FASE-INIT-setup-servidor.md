# Fase INIT: Configuración del servidor

## Objetivo
Instalar y configurar PHP 8, MariaDB y la base de datos para poder desarrollar y probar.

## Estado: COMPLETADO

### Software instalado
- [x] PHP 8.4.16 (cli)
- [x] MariaDB 11.8.6
- [x] Extensiones PHP: pdo, pdo_mysql, mysqli, mbstring, xml, gd, gettext, intl, curl

### Base de datos
- [x] Base de datos `game` creada (charset utf8mb4)
- [x] Usuario `he`@`localhost` creado con permisos
- [x] Schema importado desde `game.sql` — 109 tablas
- [x] Conexión PDO verificada desde PHP

### Configuración
- [x] `config.php` — BASE_PATH definido como `__DIR__ . '/'`
- [x] `config.php` — $gameDomain = `minion.twentic.com:8080`
- [x] `config.php` — $gameDomainProto = `https://minion.twentic.com:8080`
- [x] `config.php` — $contactEmail, $forumDomain, $wikiPath configurados
- [x] `connect.php` — DSN con charset=utf8mb4, credenciales actualizadas

### Dominio
- URL principal: `https://minion.twentic.com:8080`
