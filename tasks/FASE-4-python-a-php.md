# Fase 4: Reescribir Python a PHP y eliminar Python

## Objetivo
Portar los 28 scripts Python a PHP y eliminar todo el código Python, unificando el stack.

## Tareas

### 4A: Cron scripts simples (1-2 queries cada uno)

- [ ] **`cron2/fbiUpdate.py` → `cron/fbiUpdate.php`**
  - DELETE FROM fbi WHERE dateEnd < NOW()

- [ ] **`cron2/removeDownNPC.py` → `cron/removeDownNPC.php`**
  - DELETE FROM npc_down WHERE downUntil < NOW()

- [ ] **`cron2/removeExpiredAccs.py` → `cron/removeExpiredAccs.php`**
  - SELECT de bankaccounts_expire expirados
  - DELETE FROM bankAccounts por cada uno

- [ ] **`cron2/removeExpiredLogins.py` → `cron/removeExpiredLogins.php`**
  - DELETE con LEFT JOIN de users_expire + users_online + internet_connections
  - DELETE usuarios inactivos >10h de users_online + internet_connections

- [ ] **`cron2/npcHardware.py` → `cron/npcHardware.php`**
  - SELECT NPCs con npc_key
  - UPDATE hardware SET hdd=10, cpu=8, ram=1024, net=50

- [ ] **`python/query_counter.py` → `cron/queryCounter.php`**
  - Leer status/queries.txt, sumar, escribir

### 4B: Cron scripts medios

- [ ] **`cron2/removeExpiredHTMLPages.py` → `cron/removeExpiredHTMLPages.php`**
  - SELECT cache_profile expirados (>1h)
  - unlink() archivos HTML de profile
  - DELETE FROM cache_profile

- [ ] **`cron2/removeExpiredPremium.py` → `cron/removeExpiredPremium.php`**
  - SELECT users_premium expirados
  - Otorgar badge donator (usar BadgeManager)
  - INSERT premium_history
  - DELETE FROM users_premium
  - UPDATE internet_webserver SET active=0

- [ ] **`cron2/removeExpiredNPC.py` → `cron/removeExpiredNPC.php`**
  - SELECT NPCs expirados
  - DELETE software texts/folders (softType 30,31)
  - Cascading DELETE: npc, hardware, software, software_running, npc_key, npc_info, npc_reset
  - Notificar usuarios afectados (INSERT lists_notifications)
  - DELETE FROM lists, virus, virus_ddos, internet_connections

- [ ] **`cron2/antivirusNPC.py` → `cron/antivirusNPC.php`**
  - SELECT NPCs con scan pendiente (npc_reset)
  - Buscar virus instalados
  - Generar nombres formateados (.vddos, .vwarez, etc.)
  - Notificar usuarios (INSERT lists_notifications)
  - DELETE virus + software
  - UPDATE npc_reset +7 días

- [ ] **`cron2/updateRanking.py` → `cron/updateRanking.php`**
  - Ranking usuarios: SELECT WHERE reputation > 1000 ORDER BY reputation DESC → UPDATE rank
  - Ranking clanes: SELECT WHERE reputation > 0 ORDER BY reputation DESC → UPDATE rank
  - Ranking software: SELECT con ranking_software → UPDATE rank
  - Ranking DDoS: SELECT WHERE vicNPC=0 ORDER BY power DESC → UPDATE rank

- [ ] **`cron2/restoreNPC.py` → `cron/restoreNPC.php`**
  - DELETE software que no está en software_original (con JOINs)
  - INSERT software faltante (RIGHT JOIN software_original)
  - INSERT software_running para software que debería estar activo

### 4C: Cron scripts complejos

- [ ] **`cron2/updateCurStats.py` → `cron/updateCurStats.php`**
  - Para cada usuario: SELECT stats multi-JOIN
  - COUNT round_ddos por usuario
  - UPDATE hist_users_current (16 campos)
  - UPDATE cache SET reputation
  - UPDATE hist_clans_current con subqueries (IP, reputation, members, wins, losses)

- [ ] **`cron2/newRoundUpdater.py` → `cron/newRoundUpdater.php`**
  - CHECK round status=0, startDate pasado
  - Llamar NPCGenerator (antes era os.system npc_generator.py)
  - Generar IPs y passwords para todos los jugadores
  - INSERT hardware para cada jugador
  - UPDATE/INSERT bankAccounts
  - Crear clan NPCs
  - DELETE users_online
  - UPDATE round status=1
  - Llamar updateRanking + rankGenerator (includes PHP directos)

### 4D: Clases generadoras

- [ ] **`python/badge_add.py` → `classes/BadgeManager.class.php`**
  - Validar elegibilidad (badge existente, restricción por ronda, delay)
  - INSERT users_badge o clan_badge
  - Enviar email notificación (usar SES stub)
  - Trigger regeneración de perfil (usar ProfileGenerator)
  - Auto-award "30 badges" achievement
  - Lee `json/badges.json`

- [ ] **`python/create_user.py` → `classes/UserCreator.class.php`**
  - Generar password de juego random
  - INSERT en 11 tablas: users, users_stats, hardware, log, cache, cache_profile, hist_users_current, ranking_user, certifications, users_puzzle, users_learning, users_language
  - Soporte Facebook/Twitter (INSERT users_facebook/users_twitter)
  - Trigger generación de perfil en EN y PT
  - Transacción con rollback

- [ ] **`python/profile_generator.py` → `classes/ProfileGenerator.class.php`**
  - **El más complejo.** SELECT con 5+ JOINs (20+ campos)
  - COUNT missions completadas, friends
  - SELECT 5 friends con info de clan
  - SELECT badges con conteos
  - Formatear dinero (thousand separators)
  - Convertir playtime a formato legible con i18n
  - Detectar clan master
  - Generar MD5 hash para profile picture path
  - Construir HTML completo
  - Escribir a `html/profile/{userID}_{lang}.html`
  - UPDATE cache_profile y cache.reputation
  - Soporte multi-idioma (gettext)

- [ ] **`python/npc_generator.py` → `classes/NPCGenerator.class.php`**
  - Leer `json/npc.json`
  - Truncar tablas NPC existentes
  - INSERT npc con IPs random y passwords
  - INSERT npc_info_en, npc_info_pt
  - INSERT npc_key
  - INSERT hardware
  - INSERT log
  - INSERT npc_reset (scan schedule random)
  - Llamar SoftwareGenerator y RiddleSoftwareGenerator
  - Transacción con rollback

- [ ] **`python/npc_generator_web.py` → `classes/NPCWebGenerator.class.php`**
  - Leer `json/npc.json`
  - Procesar templates con sintaxis `::key::`
  - Resolver valores dinámicos (IPs, nombres, cross-references con `/`)
  - UPDATE npc_info por idioma (en, pt)

- [ ] **`python/software_generator.py` → `classes/SoftwareGenerator.class.php`**
  - Leer `json/npcsoftware.json`
  - Lookup tables de RAM y size (~100 entradas cada una)
  - Mapear tipos de software a extensiones y nombres
  - INSERT software_original
  - INSERT processes (para software que debe correr)
  - SELECT npcID FROM npc_key

- [ ] **`python/software_generator_riddle.py` → `classes/RiddleSoftwareGenerator.class.php`**
  - Leer `json/riddle_software.json`
  - Mismo patrón que SoftwareGenerator pero para puzzles
  - INSERT software_original + processes

- [ ] **`python/badge_hunter.py` → `cron/badgeHunter.php`**
  - 13 queries de stats con umbrales
  - Llamar BadgeManager para cada usuario elegible
  - Badges: h4x0r, b4nk3r, who ate my ram, Employee, I Cant Handle, Addicted player, Rich, DDoSer, Efficient, researcher, Hacker, What'ya Doin

- [ ] **`python/badge_hunter_all.py` → `cron/badgeHunterAll.php`**
  - 15+ queries de stats acumulados (UNION, JOIN históricos)
  - Badges: Web Celeb, you are addicted, Noob Certification, I need help, anniversaries (1/2/5yr), I haz fame, Powerful member, DDoS Master, Talker, Famous, software engineer, hacker master

- [ ] **`python/rank_generator.py` → `cron/rankGenerator.php`**
  - Generar 4 páginas HTML paginadas (100 items, 10 para DDoS)
  - Users: SELECT ranking_user con 8 JOINs
  - Clans: SELECT ranking_clan con stats
  - Software: SELECT ranking_software
  - DDoS: SELECT ranking_ddos
  - Escribir a `html/ranking/`

- [ ] **`python/fame_generator.py` → `cron/fameGenerator.php`**
  - Generar 4 tipos de páginas fame por ronda (50 items/página)
  - Users, Clans, Software, DDoS
  - Escribir a `html/fame/`

- [ ] **`python/fame_generator_alltime.py` → `cron/fameGeneratorAlltime.php`**
  - Generar 4 tipos de páginas fame all-time
  - Queries UNION historical + current
  - Escribir a `html/fame/`

### 4E: Actualizar llamadas internas

- [ ] **`classes/Database.class.php`** — Reemplazar llamada a `Python.class.php` para crear usuarios con `UserCreator.class.php`
- [ ] **`classes/Player.class.php`** — Reemplazar llamada a Python para regenerar perfiles con `ProfileGenerator.class.php`
- [ ] **`cron/finishRound.php`** — 21 exec() a scripts Python → require + llamar clases/funciones PHP
- [ ] **`cron/defcon2.php`** — exec() a Python → llamada PHP directa
- [ ] **`template/contentEnd.php`** — exec() query_counter → include cron/queryCounter.php

### 4F: Eliminar todo Python

- [ ] Borrar directorio `python/` completo
- [ ] Borrar directorio `cron2/` completo
- [ ] Borrar `classes/Python.class.php`
- [ ] Borrar `cron2/updateStatsAndRanking.sh`
- [ ] Borrar `python/sandbox.py` (ya incluido en directorio)
- [ ] Borrar `python/functions.py` (ya incluido en directorio)

## Verificación
```bash
# No debe quedar Python
find . -name '*.py' -o -name '*.sh' | grep -v __pycache__
ls python/ cron2/ 2>&1

# No deben quedar llamadas a Python en PHP
grep -rn 'python' --include='*.php' | grep -v '//' | grep -v 'Python fue eliminado'

# Syntax check de todo lo nuevo
find cron/ classes/ -name '*.php' | xargs -I{} php -l {}
```
