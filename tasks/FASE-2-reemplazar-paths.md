# Fase 2: Reemplazar /var/www/ en todos los PHP

## Objetivo
Eliminar los 510+ paths hardcodeados `/var/www/` y usar `BASE_PATH` en su lugar.

## Tareas

### Entry points — agregar require config.php como primer include
- [ ] `index.php`
- [ ] `ajax.php`
- [ ] `login.php`
- [ ] `register.php`
- [ ] `logout.php`
- [ ] `mail.php`
- [ ] `software.php`
- [ ] `hardware.php`
- [ ] `hardwareItens.php`
- [ ] `internet.php`
- [ ] `clan.php`
- [ ] `fame.php`
- [ ] `premium.php`
- [ ] `pagarme.php`
- [ ] `bitcoin.php`
- [ ] `DDoS.php`
- [ ] `log.php`
- [ ] `logEdit.php`
- [ ] `profile.php`
- [ ] `ranking.php`
- [ ] `settings.php`
- [ ] `options.php`
- [ ] `webserver.php`
- [ ] `missions.php`
- [ ] `research.php`
- [ ] `researchTable.php`
- [ ] `processes.php`
- [ ] `createsoft.php`
- [ ] `finances.php`
- [ ] `list.php`
- [ ] `war.php`
- [ ] `doom.php`
- [ ] `certs.php`
- [ ] `riddle.php`
- [ ] `reset.php`
- [ ] `resetIP.php`
- [ ] `welcome.php`
- [ ] `uploadImage.php`
- [ ] `stats.php`
- [ ] `stats_1.php`
- [ ] `TOS.php`
- [ ] `privacy.php`
- [ ] `about.php`
- [ ] `news.php`
- [ ] `changelog.php`
- [ ] `gameInfo.php`
- [ ] `badge_config.php`
- [ ] `legal.php`

### Clases — reemplazar /var/www/ con BASE_PATH
- [ ] `classes/Session.class.php`
- [ ] `classes/PDO.class.php`
- [ ] `classes/Database.class.php`
- [ ] `classes/Player.class.php`
- [ ] `classes/PC.class.php`
- [ ] `classes/Internet.class.php`
- [ ] `classes/Pagination.class.php`
- [ ] `classes/Premium.class.php`
- [ ] `classes/Mail.class.php`
- [ ] `classes/Clan.class.php`
- [ ] `classes/SES.class.php` (stub)
- [ ] `classes/Forum.class.php` (stub)
- [ ] `classes/Purifier.class.php` (stub)
- [ ] `classes/Mission.class.php`
- [ ] `classes/NPC.class.php`
- [ ] `classes/Riddle.class.php`
- [ ] `classes/Python.class.php` (se eliminará en Fase 4, por ahora actualizar paths)
- [ ] `classes/Images.class.php`
- [ ] `classes/Facebook.class.php`
- [ ] `classes/Social.class.php`
- [ ] `classes/Versioning.class.php`
- [ ] `classes/EmailVerification.class.php`
- [ ] `classes/RememberMe.class.php`

### Templates
- [ ] `template/default.php`
- [ ] `template/contentEnd.php`
- [ ] `template/contentStart.php`
- [ ] `template/gameHeader.php`
- [ ] `template/fbtpl.php`
- [ ] `template/tttpl.php`

### Cron PHP existentes
- [ ] `cron/defcon.php`
- [ ] `cron/defcon2.php`
- [ ] `cron/doomUpdater.php`
- [ ] `cron/endWar.php`
- [ ] `cron/endWar2.php`
- [ ] `cron/finishRound.php`
- [ ] `cron/generateMissions.php`
- [ ] `cron/restoreSoftware.php`
- [ ] `cron/safenetUpdate.php`
- [ ] `cron/updatePremium.php`
- [ ] `cron/updateServerStats.php`
- [ ] `cron/backup_game.php`

### Scripts
- [ ] `scripts/recordRoundHistory.php`
- [ ] `scripts/shutdownRegion.php`
- [ ] `scripts/transformPrepared.php`

### Otros
- [ ] `status/index.php`
- [ ] Archivos en `npccontent/` (si tienen paths)
- [ ] Archivos en `certs/` (si tienen paths)
- [ ] Archivos en `paypal/` (si tienen paths)

### Patrón de reemplazo
```
require '/var/www/         →  require BASE_PATH . '
require_once '/var/www/    →  require_once BASE_PATH . '
include '/var/www/         →  include BASE_PATH . '
file_get_contents('/var/www/ → file_get_contents(BASE_PATH . '
fopen('/var/www/           →  fopen(BASE_PATH . '
exec('...python /var/www/  →  (marcar como TODO para Fase 4)
```

## Verificación
```bash
# No deben quedar /var/www/ (excepto comentarios)
grep -rn '/var/www/' --include='*.php' | grep -v '^\s*//' | grep -v '2019:'

# Syntax check
find . -name '*.php' -not -path './forum/*' -not -path './wiki/*' | xargs -I{} php -l {}
```
