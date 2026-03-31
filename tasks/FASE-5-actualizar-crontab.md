# Fase 5: Actualizar crontab

## Objetivo
Reescribir el crontab para reflejar el stack unificado PHP, con paths actualizados.

## Tareas

### Entradas a mantener (PHP existentes, actualizar paths)
- [ ] `cron/defcon2.php` — cada 10min
- [ ] `cron/endWar2.php` — cada 10min
- [ ] `cron/doomUpdater.php` — cada minuto
- [ ] `cron/generateMissions.php` — cada 15min
- [ ] `cron/safenetUpdate.php` — cada 30min
- [ ] `cron/updateServerStats.php` — cada hora
- [ ] `cron/backup_game.php` — cada hora

### Entradas nuevas (migradas de Python)
- [ ] `cron/updateCurStats.php` — cada hora (era cron2/updateCurStats.py)
- [ ] `cron/updateRanking.php` — cada hora (era cron2/updateRanking.py)
- [ ] `cron/rankGenerator.php` — cada hora (era python/rank_generator.py)
- [ ] `cron/removeExpiredLogins.php` — cada 15min (era cron2/removeExpiredLogins.py)
- [ ] `cron/removeExpiredHTMLPages.php` — min 40 cada hora (era cron2/removeExpiredHTMLPages.py)
- [ ] `cron/antivirusNPC.php` — cada 3h (era cron2/antivirusNPC.py)
- [ ] `cron/restoreNPC.php` — cada 15min (era cron2/restoreNPC.py)
- [ ] `cron/queryCounter.php` — cada hora (era python/query_counter.py)
- [ ] `cron/newRoundUpdater.php` — cada minuto (era cron2/newRoundUpdater.py)
- [ ] `cron/removeExpiredAccs.php` — cada hora (era cron2/removeExpiredAccs.py)
- [ ] `cron/removeExpiredNPC.php` — cada hora (era cron2/removeExpiredNPC.py)
- [ ] `cron/removeExpiredPremium.php` — cada hora (era cron2/removeExpiredPremium.py)
- [ ] `cron/removeDownNPC.php` — cada hora (era cron2/removeDownNPC.py)
- [ ] `cron/fbiUpdate.php` — cada 5min (era cron2/fbiUpdate.py)
- [ ] `cron/badgeHunter.php` — cada 30min (era python/badge_hunter.py)
- [ ] `cron/badgeHunterAll.php` — cada hora (era python/badge_hunter_all.py)
- [ ] `cron/npcHardware.php` — (ejecutar manualmente, era cron2/npcHardware.py)

### Entradas a eliminar
- [ ] Todas las entradas con `/usr/bin/env python`
- [ ] `backup_forum.php` (foro eliminado)
- [ ] Entradas de piwik/log-analytics (líneas 33-39)

### Formato de cada entrada
```
INTERVALO  /usr/local/bin/php  BASE_PATH/cron/script.php  >> /var/log/game/cron.log 2>&1
```

## Verificación
```bash
# No debe haber referencias a python en crontab
grep -i python crontab

# No debe haber /var/www/ en crontab
grep '/var/www/' crontab

# Todos los scripts PHP referenciados deben existir
grep -oP '/cron/\S+\.php' crontab | sort -u | while read f; do
  test -f ".$f" || echo "FALTA: $f"
done
```
