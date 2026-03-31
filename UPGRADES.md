# UPGRADES.md

Lista priorizada de mejoras para Hacker Experience Legacy tras la migración a PHP 8 + MariaDB.

---

## 1. Inyección SQL — Migrar a prepared statements [CRITICO/SEGURIDAD]
**50+ queries** usan concatenación de strings directamente en SQL. Afecta: `Player.class.php`, `ajax.php`, `contentStart.php`, `Mail.class.php`, `News.class.php`, `Clan.class.php`, todos los cron originales, `scripts/`. Cualquier input de usuario puede comprometer la base de datos completa.
- **Acción:** Reemplazar toda concatenación SQL por `$pdo->prepare()` + `->execute()` con parámetros.

## 2. XSS — Escapar toda salida de datos de usuario [CRITICO/SEGURIDAD]
Variables `$_GET`, `$_POST` y datos de BD se imprimen sin `htmlspecialchars()`. Ejemplos: `reset.php:120` (`$_GET['code']`), `Clan.class.php:2989-2993` (`$_POST['id']`, `$_POST['text']`), `Clan.class.php:3234` (`$_GET['ctag']`, `$_GET['cname']`).
- **Acción:** Auditar todos los `echo`/`<?=` y aplicar `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.

## 3. CSRF — Añadir tokens a todos los formularios POST [CRITICO/SEGURIDAD]
Ningún formulario tiene protección CSRF. Login, registro, misiones, clan, mail — todos vulnerables.
- **Acción:** Generar token en sesión, incluir como hidden field, validar en cada POST.

## 4. Email real — Reemplazar stub SES [ALTO/FUNCIONALIDAD]
`classes/SES.class.php` es un stub que solo hace `error_log()`. Sin email no funciona: verificación de cuenta, reset de password, notificaciones de badges/premium.
- **Acción:** Instalar PHPMailer via Composer, configurar SMTP, reimplementar `SES.class.php`.

## 5. HTML Purifier real — Reemplazar stub Purifier [ALTO/SEGURIDAD]
`classes/Purifier.class.php` usa `strip_tags()` como fallback. No filtra atributos maliciosos (onclick, onerror, javascript: URIs).
- **Acción:** Instalar HTMLPurifier via Composer, restaurar la configuración original por tipo (mail, text, clan-desc, etc.).

## 6. Gestión de errores — try/catch en operaciones de BD [ALTO/ESTABILIDAD]
100+ llamadas `$pdo->query()->fetch()` sin verificar si la query tuvo éxito. Si una query falla, se obtiene un fatal error en `->fetch()` sobre `false`.
- **Acción:** Envolver operaciones críticas en try/catch, verificar resultados antes de usar.

## 7. Undefined array keys — Acceso a $_SESSION/$_GET sin isset [ALTO/ESTABILIDAD]
PHP 8 genera warnings por acceder a keys inexistentes. 50+ ocurrencias en `contentStart.php`, `gameHeader.php`, templates. Genera ruido en logs y puede causar bugs.
- **Acción:** Usar `$_SESSION['key'] ?? default` o `isset()` checks.

## 8. Comparaciones de tipos débiles — == vs === [MEDIO/CALIDAD]
15+ comparaciones `== '0'`, `== 0` con strings donde deberían usarse `===`. PHP 8.0+ cambió el comportamiento de `==` con strings numéricas.
- **Acción:** Reemplazar `==` por `===` con cast explícito donde sea necesario.

## 9. Composer — Gestión de dependencias [MEDIO/INFRAESTRUCTURA]
No hay `composer.json`. Las dependencias (PHPMailer, HTMLPurifier, BCrypt) deberían gestionarse con Composer.
- **Acción:** Crear `composer.json`, migrar BCrypt a `password_hash()`/`password_verify()` (nativo PHP 5.5+), instalar PHPMailer y HTMLPurifier.

## 10. BCrypt legacy → password_hash nativo [MEDIO/SEGURIDAD]
`classes/BCrypt.class.php` es una implementación custom. PHP tiene `password_hash()` y `password_verify()` nativos desde 5.5.
- **Acción:** Reemplazar `BCrypt.class.php` por funciones nativas. Mantener retrocompatibilidad con hashes existentes.

## 11. Autoloader — Eliminar requires manuales [MEDIO/CALIDAD]
60+ archivos tienen cadenas de `require`/`require_once` manuales. Frágil y propenso a errores de orden.
- **Acción:** Implementar PSR-4 autoloading via Composer o `spl_autoload_register()`.

## 12. Panel de administración [MEDIO/FUNCIONALIDAD]
No existe interfaz admin. Solo hay Adminer para acceso directo a BD. Falta: gestión de usuarios, baneos, edición de NPCs, control de rondas, ver logs, estadísticas.
- **Acción:** Crear panel admin básico en `/admin/` con autenticación separada.

## 13. HTTPS y headers de seguridad [MEDIO/SEGURIDAD]
No se envían headers de seguridad: `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`.
- **Acción:** Añadir headers en `template/gameHeader.php` o en configuración del servidor web.

## 14. Cron npc-restore — Fix query con keys incorrectos [BAJO/BUG]
`cron/restoreNPC.php` falla con "Undefined array key" para softSize, npcID, etc. El RIGHT JOIN devuelve columnas con nombres diferentes a los esperados.
- **Acción:** Corregir los nombres de columnas en el fetch del query de la fase de adición.

## 15. Misiones — Pre-generar seeds [BAJO/RENDIMIENTO]
Las mission seeds se generan lazy (al ver la misión) en vez de durante `generateMissions.php`. Funciona pero es ineficiente y genera datos inconsistentes.
- **Acción:** Llamar `seed_generate()` al crear cada misión en `generateMissions.php`.

## 16. Imágenes e iconos — Restaurar assets [BAJO/UI]
El repositorio original no incluía la mayoría de imágenes/iconos (famfamfam, perfiles, etc.). Muchos `<img>` apuntan a archivos inexistentes.
- **Acción:** Descargar iconset famfamfam silk, crear directorio de iconos, añadir placeholder para profile pics.

## 17. Logs estructurados [BAJO/OPERACIONES]
Los cron scripts imprimen a stdout sin formato consistente. No hay log centralizado ni rotación.
- **Acción:** Implementar logging con nivel (INFO/WARN/ERROR), timestamps, y rotación con logrotate.

## 18. Tests — Crear suite de tests básica [BAJO/CALIDAD]
No existen tests. Cualquier cambio puede romper funcionalidad sin saberlo.
- **Acción:** Añadir PHPUnit, crear tests para: login, registro, misiones, NPC generation, badge system.

## 19. Rate limiting — Proteger endpoints sensibles [BAJO/SEGURIDAD]
Solo hay un check básico de IP en registro (10 min cooldown). Login, AJAX, y API no tienen rate limiting.
- **Acción:** Implementar rate limiting por IP en login, registro, y ajax.php.

## 20. Internacionalización — Completar traducciones [BAJO/UX]
Muchas strings están hardcodeadas en inglés sin `_()`. Las traducciones PT están incompletas. El selector de idioma apunta a subdominios que ya no existen.
- **Acción:** Auditar strings sin `_()`, actualizar `defaults.po`, adaptar selector de idioma a parámetro GET/cookie.
