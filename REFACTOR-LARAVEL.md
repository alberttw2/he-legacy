# REFACTOR-LARAVEL.md

Plan de migración de Hacker Experience Legacy a Laravel 12 con frontend moderno.

---

## Estado actual del codebase

| Métrica | Valor |
|---------|-------|
| Líneas PHP total | 63,457 |
| Clases PHP | 46 |
| Entry points (páginas) | 53 |
| Endpoints AJAX | 65 |
| Tablas BD | 89 |
| Cron jobs | 31 |
| Archivos JS | 23 (22,622 líneas) |
| Archivos CSS | 14 (9,298 líneas) |

### Clases más grandes (a descomponer)
| Clase | Líneas | Responsabilidades |
|-------|--------|-------------------|
| PC.class.php | 8,505 | Hardware, software display, upgrades, file system |
| Process.class.php | 4,666 | Task manager, downloads, uploads, hacks, completion |
| Clan.class.php | 4,204 | Clanes, guerras, miembros, foros |
| Internet.class.php | 4,041 | Navegación, login servers, NPC pages, banks |
| Mission.class.php | 1,906 | Misiones, storyline, tutorial |
| Player.class.php | 1,862 | Info jugador, perfil, badges, admin |

---

## Stack propuesto (2026)

| Componente | Actual (Legacy) | Propuesto | Versión |
|-----------|----------------|-----------|---------|
| **Backend** | PHP 8.4 procedural | Laravel 12 | 12.x (PHP 8.4+) |
| **Frontend Framework** | jQuery 1.11 | Vue 3 (Composition API) | 3.5+ |
| **CSS Framework** | Bootstrap 3.0.2 | Tailwind CSS | 4.x |
| **SPA Bridge** | — | Inertia.js | 2.x |
| **UI Components** | — | Headless UI / Radix Vue | latest |
| **Build Tool** | Archivos sueltos | Vite | 6.x |
| **BD** | MariaDB raw PDO | Eloquent ORM + Migrations | MariaDB 11+ |
| **Auth** | Sesión custom | Laravel Fortify + Sanctum | latest |
| **API** | $.post → ajax.php | API Routes + Axios | — |
| **Cron** | crontab + scripts PHP | Laravel Task Scheduler | — |
| **Email** | SES stub | Laravel Mail (Resend/SMTP) | — |
| **Tests** | PHPUnit básico (16 tests) | Pest 3 + Laravel Dusk | Pest 3.x |
| **Cache** | Ninguno | Laravel Cache (Redis) | Redis 7+ |
| **Queue** | Ninguno | Laravel Queue (Redis driver) | — |
| **WebSocket** | Ninguno | Laravel Reverb | 1.x |
| **Monitoreo** | Ninguno | Laravel Pulse | 1.x |
| **Admin Panel** | Custom PHP | Filament 3 | 3.x |
| **Icons** | Font Awesome 4.7 | Heroicons / Lucide | latest |
| **Charts** | easyPieChart | Chart.js / ApexCharts | latest |
| **TypeScript** | — | TypeScript | 5.x |
| **Linting** | — | ESLint + Prettier + Pint | latest |
| **CI/CD** | — | GitHub Actions | — |
| **Deploy** | Manual | Laravel Forge / Coolify | — |

### Por qué este stack

- **Laravel 12**: El framework PHP más maduro, con ecosystem completo (auth, queue, cache, websockets, admin, monitoring)
- **Vue 3 + Inertia.js**: SPA sin API separada — Inertia envía props desde Laravel controllers directamente a Vue components. Sin duplicar rutas ni serializar/deserializar JSON manualmente
- **Tailwind CSS 4**: Utility-first, sin CSS custom. Diseño consistente, responsive nativo, dark mode gratis. Reemplaza 14 archivos CSS por clases utilitarias
- **Pest 3**: Tests legibles, tipo BDD. Feature tests que cubren flujos completos del juego
- **Filament 3**: Admin panel auto-generado desde modelos Eloquent. Reemplaza el admin/ custom
- **Laravel Reverb**: WebSocket server nativo de Laravel. Process bars en real-time, chat de clan, alertas DDoS instantáneas — sin polling
- **Laravel Pulse**: Dashboard de monitoreo integrado. Queries lentas, jobs fallidos, uso de memoria

---

## Fases de migración

### Fase 1: Proyecto Laravel + BD [1-2 semanas]

**Objetivo:** Proyecto Laravel funcional con la BD existente.

```bash
composer create-project laravel/laravel he-reborn
cd he-reborn
```

**Tareas:**
- [ ] Crear proyecto Laravel 12
- [ ] Configurar `.env` con BD existente
- [ ] Generar Migrations desde las 89 tablas existentes (`php artisan schema:dump`)
- [ ] Normalizar schema: añadir timestamps, foreign keys, indexes faltantes
- [ ] Crear Seeders basados en `scripts/seed.php`
- [ ] Crear Factories para testing

**Tablas a normalizar (nombres inconsistentes):**
- `bankAccounts` → `bank_accounts` (snake_case)
- `bankaccounts_expire` → `bank_account_expires`
- `users_stats` → user_stats (singular)
- `hist_users_current` → `user_stats_current`
- `npc_info_en`, `npc_info_pt` → `npc_translations` (tabla única con columna `locale`)

### Fase 2: Modelos Eloquent [1-2 semanas]

**Objetivo:** Mapear todas las entidades del juego como modelos con relaciones.

**Modelos principales (17):**
- [ ] `User` — id, login, email, password, gameIP, homeIP, premium
- [ ] `UserStat` — belongsTo User. EXP, timePlaying, hackCount, etc.
- [ ] `Hardware` — belongsTo User. CPU, HDD, RAM, NET
- [ ] `Software` — belongsTo User. nombre, versión, tipo, RAM
- [ ] `SoftwareRunning` — belongsTo Software, belongsTo User
- [ ] `Process` — belongsTo User. acción, víctima, tiempos, prioridad
- [ ] `Npc` — type, IP, password. hasMany NpcTranslation, hasMany NpcKey
- [ ] `NpcTranslation` — npc_id, locale, name, web
- [ ] `Mission` — type, status, hirer, victim, prize, level
- [ ] `MissionHistory` — belongsTo User
- [ ] `BankAccount` — belongsTo User, belongsTo Npc (bank)
- [ ] `Clan` — name, nick, IP, reputation
- [ ] `ClanMember` — belongsTo Clan, belongsTo User
- [ ] `HackedServer` (lists) — belongsTo User. IP, user, pass
- [ ] `Notification` — belongsTo User
- [ ] `Round` — name, startDate, status
- [ ] `Badge` — many-to-many User

**Relaciones clave:**
```php
User hasOne UserStat
User hasMany Hardware
User hasMany Software
User hasMany Process
User hasMany HackedServer
User hasMany BankAccount
User hasMany Notification
User belongsToMany Badge
User belongsTo Clan (through ClanMember)
Clan hasMany ClanMember
Clan hasMany ClanWar
Npc hasMany NpcTranslation
Npc hasMany Software (NPC software)
Mission belongsTo Npc (hirer)
Mission belongsTo Npc (victim)
```

### Fase 3: Autenticación + Middleware [3-5 días]

**Objetivo:** Login/registro/sesión con Laravel.

- [ ] Instalar Laravel Fortify (login, registro, reset password)
- [ ] Migrar BCrypt hashes (ya compatible con password_verify)
- [ ] Middleware `EnsureRoundActive` — verifica que haya ronda activa
- [ ] Middleware `EnsureNotBanned` — verifica gamePass != 'BANNED'
- [ ] Middleware `IsStaff` — verifica users_admin
- [ ] Gate/Policy para acciones de admin

### Fase 4: Servicios de lógica de juego [2-3 semanas]

**Objetivo:** Extraer la lógica de las clases monolíticas a Services.

Descomponer las 6 clases grandes en servicios enfocados:

**PC.class.php (8,505 líneas) →**
- [ ] `HardwareService` — getInfo, upgrade, buy, calculateUsage
- [ ] `SoftwareService` — list, install, uninstall, research, display
- [ ] `FileSystemService` — folders, texts, hide, seek
- [ ] `VirusService` — install, detect, remove

**Process.class.php (4,666 líneas) →**
- [ ] `ProcessService` — create, complete, pause, resume, delete
- [ ] `ProcessCalculator` — calculateDuration, updateUsage, redistribute
- [ ] `DownloadHandler`, `UploadHandler`, `HackHandler`, etc. (Strategy pattern)

**Internet.class.php (4,041 líneas) →**
- [ ] `NavigationService` — navigate, gatherInfo, showPage
- [ ] `ServerLoginService` — verifyLogin, doLogin
- [ ] `BankService` — accounts, transfers, hacking (mover desde Finances)

**Clan.class.php (4,204 líneas) →**
- [ ] `ClanService` — create, join, leave, manage
- [ ] `ClanWarService` — declare, score, end, history

**Mission.class.php (1,906 líneas) →**
- [ ] `MissionService` — list, accept, complete, abort
- [ ] `MissionGenerator` — generate, seed text
- [ ] `StorylineService` — tutorial, doom storyline

**Player.class.php (1,862 líneas) →**
- [ ] `PlayerService` — profile, stats, badges
- [ ] `RankingService` — rankings, certifications

### Fase 5: Controllers + API Routes [2-3 semanas]

**Objetivo:** Reemplazar los 53 entry points + 65 AJAX endpoints con controllers.

**Controllers (mapeo):**
```
index.php           → HomeController@index
login.php           → Auth\LoginController (Fortify)
register.php        → Auth\RegisterController (Fortify)
software.php        → SoftwareController@index
hardware.php        → HardwareController@index/upgrade/buy
internet.php        → InternetController@navigate/login/hack
missions.php        → MissionController@index/accept/complete
processes.php       → ProcessController@index/complete/pause
clan.php            → ClanController@index/create/war
ranking.php         → RankingController@index
mail.php            → MailController@index/read/send
profile.php         → ProfileController@show
settings.php        → SettingsController@index/update
stats.php           → StatsController@game/server
finances.php        → FinanceController@index
university.php      → UniversityController@research/certification
list.php            → HackedDatabaseController@index
fame.php            → FameController@index
ajax.php            → API Controllers (dividir 65 endpoints)
```

**API Routes (reemplazar ajax.php):**
```php
Route::middleware('auth:sanctum')->group(function () {
    // Processes
    Route::get('/api/processes', [ProcessApiController::class, 'index']);
    Route::post('/api/processes/{id}/complete', [ProcessApiController::class, 'complete']);
    Route::post('/api/processes/{id}/pause', [ProcessApiController::class, 'pause']);
    Route::post('/api/processes/complete-all', [ProcessApiController::class, 'completeAll']);

    // Bank
    Route::get('/api/bank/accounts', [BankApiController::class, 'accounts']);

    // Notifications
    Route::get('/api/notifications', [NotificationApiController::class, 'index']);
    Route::post('/api/notifications/read-all', [NotificationApiController::class, 'readAll']);

    // Game common data (online users, money, etc.)
    Route::get('/api/common', [GameApiController::class, 'common']);

    // Software
    Route::post('/api/software/{id}/download', [SoftwareApiController::class, 'download']);
    Route::post('/api/software/{id}/install', [SoftwareApiController::class, 'install']);

    // Missions
    Route::post('/api/missions/{id}/accept', [MissionApiController::class, 'accept']);
    Route::post('/api/missions/{id}/complete', [MissionApiController::class, 'complete']);

    // Clan
    Route::get('/api/clan/search', [ClanApiController::class, 'search']);
    // ... etc
});
```

### Fase 6: Frontend Vue 3 + Tailwind [3-4 semanas]

**Objetivo:** UI moderna, reactiva, responsive.

**Stack:**
- Vue 3 con Composition API
- Inertia.js (SPA sin API separada)
- Tailwind CSS v4
- Vite como bundler

**Componentes principales:**
```
layouts/
  GameLayout.vue          — sidebar, header, notifications
  AuthLayout.vue          — login/register

pages/
  Dashboard.vue           — home después de login
  Software/Index.vue      — lista de software
  Hardware/Index.vue       — hardware + upgrade shop
  Internet/Browser.vue    — navegador de IPs con terminal look
  Internet/Server.vue     — vista de servidor (login, software, log)
  Missions/Index.vue      — lista + misión activa
  Processes/Index.vue     — task manager con barras real-time
  Clan/Index.vue          — clan management
  Ranking/Index.vue       — leaderboards
  Profile/Show.vue        — perfil de jugador
  University/Index.vue    — research + certifications
  Admin/Dashboard.vue     — panel admin

components/
  ProgressBar.vue         — barra de progreso animada
  NotificationBell.vue    — campana con dropdown
  BankSelector.vue        — selector de cuenta bancaria
  ServerTerminal.vue      — terminal de servidor con look hacker
  HardwareCard.vue        — tarjeta de hardware con specs
  SoftwareTable.vue       — tabla de software con acciones
  MissionCard.vue         — tarjeta de misión
  ProcessItem.vue         — item de proceso con barra + controles
```

**Tema visual propuesto:**
- Dark theme por defecto con look "terminal hacker"
- Fuente monospace para IPs, logs, terminal
- Colores: fondo negro/gris oscuro, verde neón para acentos, rojo para alertas
- Animaciones de escritura (typewriter) en logs y terminal
- Transiciones suaves entre páginas (Inertia)

### Fase 7: Task Scheduler [3-5 días]

**Objetivo:** Reemplazar crontab con Laravel Scheduler.

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Cada minuto
    $schedule->job(new DoomUpdaterJob)->everyMinute();
    $schedule->job(new NewRoundUpdaterJob)->everyMinute();

    // Cada 5 minutos
    $schedule->job(new FbiUpdateJob)->everyFiveMinutes();

    // Cada 10 minutos
    $schedule->job(new DefconUpdateJob)->everyTenMinutes();
    $schedule->job(new EndWarJob)->everyTenMinutes();

    // Cada 15 minutos
    $schedule->job(new RemoveExpiredLoginsJob)->everyFifteenMinutes();
    $schedule->job(new RestoreNpcJob)->everyFifteenMinutes();
    $schedule->job(new GenerateMissionsJob)->everyFifteenMinutes();

    // Cada 30 minutos
    $schedule->job(new SafenetUpdateJob)->everyThirtyMinutes();
    $schedule->job(new BadgeHunterJob)->everyThirtyMinutes();

    // Cada hora
    $schedule->job(new UpdateStatsJob)->hourly();
    $schedule->job(new UpdateRankingJob)->hourly();
    $schedule->job(new RankGeneratorJob)->hourly();
    // ... etc
}
```

### Fase 8: Real-time con WebSockets [1-2 semanas]

**Objetivo:** Actualizaciones en tiempo real sin polling.

Usar **Laravel Reverb** (WebSocket server nativo):

```php
// Channels
Broadcast::channel('user.{id}', function ($user, $id) {
    return $user->id === (int) $id;
});

// Events
class ProcessCompleted implements ShouldBroadcast {
    public function broadcastOn() {
        return new PrivateChannel('user.' . $this->userId);
    }
}

class NotificationReceived implements ShouldBroadcast { ... }
class ServerHacked implements ShouldBroadcast { ... }
class ClanWarUpdate implements ShouldBroadcast { ... }
```

**Beneficios:**
- Process bars se actualizan en real-time sin polling
- Notificaciones instantáneas
- Chat de clan en tiempo real
- Alertas de ataque inmediatas

### Fase 9: Testing [1-2 semanas]

- [ ] Unit tests para Services (ProcessCalculator, MissionGenerator, etc.)
- [ ] Feature tests para cada Controller
- [ ] Integration tests para flujos completos (registrar → descargar → hackear → misión)
- [ ] Browser tests con Laravel Dusk

### Fase 10: Deploy + Migración de datos [3-5 días]

- [ ] Migrar datos existentes con scripts de migración
- [ ] Configurar server de producción (Nginx + PHP-FPM + Redis + Reverb)
- [ ] SSL con Let's Encrypt
- [ ] CI/CD con GitHub Actions

---

## Estimación de esfuerzo

| Fase | Duración estimada | Personas |
|------|------------------|----------|
| 1. Proyecto + BD | 1-2 semanas | 1 |
| 2. Modelos Eloquent | 1-2 semanas | 1 |
| 3. Auth + Middleware | 3-5 días | 1 |
| 4. Servicios de juego | 2-3 semanas | 1-2 |
| 5. Controllers + API | 2-3 semanas | 1-2 |
| 6. Frontend Vue 3 | 3-4 semanas | 1-2 |
| 7. Task Scheduler | 3-5 días | 1 |
| 8. WebSockets | 1-2 semanas | 1 |
| 9. Testing | 1-2 semanas | 1 |
| 10. Deploy | 3-5 días | 1 |
| **TOTAL** | **12-18 semanas** | **1-2 devs** |

---

## Estrategia de migración recomendada

**Opción A: Big Bang** — Reescribir todo de cero en Laravel. Más limpio pero más arriesgado.

**Opción B: Strangler Fig (Recomendada)** — Migrar ruta por ruta:
1. Laravel como proxy reverso del legacy
2. Migrar una página a la vez (empezar por las más simples: stats, ranking, profile)
3. Mantener la BD compartida entre legacy y Laravel
4. Ir retirando legacy a medida que Laravel cubre más rutas
5. Cuando todo esté migrado, eliminar legacy

**Opción C: API First** —
1. Crear API Laravel sobre la BD existente
2. Crear frontend Vue 3 que consume la API
3. El frontend reemplaza el legacy gradualmente
4. No requiere migrar backend de golpe
