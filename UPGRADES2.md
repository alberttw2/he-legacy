# UPGRADES2.md

Mejoras de UI/UX y gameplay para Hacker Experience Legacy, priorizadas por impacto.

---

## UI/UX

### 1. Empty states — Mostrar mensajes cuando no hay datos [ALTO]
Tablas y listas vacías no muestran nada. Internet sin IP, Hacked Database sin entries, External HD vacío, Software sin programas — todos aparecen en blanco sin explicación.
- **Acción:** Añadir mensajes "No hay datos" con orientación al jugador (ej: "No tienes software. Visita el Download Center para descargar tu primer programa.").

### 2. Onboarding de nuevos jugadores [ALTO]
Un jugador nuevo no sabe qué hacer. No hay tutorial visual, no hay indicaciones claras de los primeros pasos. La certificación "Basic Tutorial" existe pero no guía activamente.
- **Acción:** Crear un wizard de onboarding interactivo: 1) Visita el WHOIS → 2) Navega al Download Center → 3) Descarga un Cracker → 4) Hackea tu primer servidor → 5) Completa el primer puzzle.

### 3. Feedback en tiempo real para procesos [ALTO]
Los procesos (download, upload, hack) toman de 20 segundos a 2 horas pero no hay barra de progreso ni auto-refresh. El jugador tiene que refrescar manualmente la página para ver si terminó.
- **Acción:** Implementar polling AJAX cada 5s o Server-Sent Events. Mostrar barra de progreso con tiempo restante. Notificación cuando el proceso termina.

### 4. Responsive / Mobile [ALTO]
El juego no funciona en móvil. Las tablas se desbordan, la sidebar de 220px no colapsa, los formularios no se adaptan. Bootstrap 3 tiene soporte responsive limitado.
- **Acción:** Migrar a Bootstrap 5 o usar CSS Grid/Flexbox. Colapsar sidebar en hamburger menu. Tablas responsive con scroll horizontal.

### 5. Indicadores de carga en AJAX [MEDIO]
Las llamadas AJAX no muestran spinner ni feedback. El usuario clicka un botón y no sabe si algo está pasando hasta que la respuesta llega (o falla silenciosamente).
- **Acción:** Añadir spinner CSS en cada AJAX call. Deshabilitar botones durante requests. Mostrar toast/notification al completar.

### 6. Mensajes de error descriptivos [MEDIO]
Los errores son crípticos: "PROC_NOT_FOUND", "INVALID_GET", "BAD_ACC", "NO_SOFT". No ayudan al jugador a entender qué hacer.
- **Acción:** Reemplazar códigos por mensajes legibles: "No tienes un Cracker instalado. Descarga uno del Download Center." en vez de "NO_SOFT".

### 7. Sistema de temas CSS [MEDIO]
Colores hardcodeados en 13 archivos CSS (#444444 fondo, #259D1C verde, #BA1E20 rojo). Imposible cambiar el look sin editar múltiples archivos.
- **Acción:** Migrar a CSS custom properties (`--primary-color`, `--bg-color`, etc.) o SCSS. Crear 2-3 temas predefinidos (dark hacker, light, retro green).

### 8. Limpiar assets CSS/JS obsoletos [BAJO]
Archivos backup en css/ (`application.js~`, `icol16.css~`, `original.css~`). jQuery 1.11.1 y Bootstrap 3.0.2 tienen vulnerabilidades conocidas. 13 CSS files cargados, muchos redundantes.
- **Acción:** Eliminar archivos `~` backup. Consolidar CSS en 2-3 archivos. Actualizar jQuery a 3.x y Bootstrap a 5.x.

### 9. Font Awesome roto [BAJO]
El directorio `font-awesome/` fue eliminado pero las templates lo referencian. Los iconos `<i class="fa fa-*">` no se muestran.
- **Acción:** Instalar Font Awesome 6 via CDN o npm. Actualizar clases de iconos si hay cambios de API.

---

## GAMEPLAY

### 10. Chat en tiempo real [ALTO]
Solo existe email asíncrono (classes/Mail.class.php). No hay chat entre jugadores, ni chat de clan, ni chat global. La coordinación entre jugadores es imposible sin herramienta externa.
- **Acción:** Implementar WebSocket chat con canales: global, clan, privado. Historial persistente en BD. Rate limiting anti-spam.

### 11. Sistema de clanes sin foro [ALTO]
El foro phpBB fue eliminado pero los clanes dependían de él para discusión, coordinación de guerras, y reclutamiento. `Clan.class.php` llama `Forum::createForum()` que ahora retorna vacío.
- **Acción:** Reemplazar con chat de clan (ver punto 10), o integrar Discord/Slack via webhook, o crear mini-foro in-game.

### 12. Configuración de juego en admin panel [MEDIO]
Todos los parámetros del juego están hardcodeados en `config.php`: tiempos de proceso, precios de hardware, multiplicadores de misiones. Cambiar el balance requiere editar código.
- **Acción:** Crear tabla `game_config` en BD. Panel admin para editar: tiempos de proceso, costes de hardware, recompensas de misiones, etc. Cache en memoria.

### 13. Sistema de premium/monetización [MEDIO]
Premium existe (`classes/Premium.class.php`) pero el payment gateway (Pagarme) fue eliminado y PayPal IPN está incompleto. No hay forma de monetizar.
- **Acción:** Integrar Stripe como payment gateway. Definir beneficios premium claros (más RAM, procesos más rápidos, skins). Alternativa: modelo cosmético.

### 14. Leaderboards y estadísticas en tiempo real [MEDIO]
Los rankings se generan como HTML estático via cron cada hora. No hay leaderboard de economía, ni top hackers por earnings, ni estadísticas de servidor.
- **Acción:** Generar rankings dinámicamente con queries (cache 5min). Añadir categorías: top earnings, top DDoS, top completación de misiones, jugadores más activos.

### 15. Balanceo de tiempos de proceso [MEDIO]
Los tiempos son hardcodeados y no escalan: DOWNLOAD 20-7200s, FORMAT 1200-3600s. Un jugador nuevo espera lo mismo que uno veterano. No hay incentivo a mejorar hardware.
- **Acción:** Escalar tiempos según hardware del jugador (CPU reduce tiempo de hack, NET reduce tiempo de download). Fórmula: `base_time * (1 - hardware_bonus)`.

### 16. Notificaciones in-game [MEDIO]
No hay sistema de notificaciones para: misiones completadas, ataques recibidos, virus detectados, badges ganados. El jugador tiene que revisar cada sección manualmente.
- **Acción:** Añadir icono de campana en header con dropdown de notificaciones. Tabla `notifications` con tipo, mensaje, leído/no leído. Badge count en el icono.

### 17. Búsqueda de jugadores [BAJO]
No hay forma de buscar jugadores por nombre. `Social.class.php` tiene un placeholder "User search is TODO".
- **Acción:** Implementar búsqueda con autocomplete via AJAX. Mostrar perfil público con stats, clan, badges.

### 18. Puzzles — Más variedad y progresión [BAJO]
Solo hay 5 tipos de puzzle (tictactoe, 2048, lightsout, minesweeper, sudoku) + preguntas Q&A. 47 puzzles en total pero muchos son repetitivos.
- **Acción:** Añadir nuevos tipos: descifrar código, reverse engineering, steganografía, network routing. Dificultad progresiva real.

### 19. Eliminar código muerto y TODOs [BAJO]
Múltiples placeholders sin implementar:
- `riddle.php:60` — IP hardcodeada `XXX.XXX.XXX.XXX`
- `Riddle.class.php` — IPs con XXX
- `Storyline.class.php` — TODO "what if deleted?"
- `doomUpdater.php` — TODO "disconnect all users"
- `config.php` — acciones deprecadas COLLECT y D_LOG
- **Acción:** Completar todos los TODOs o eliminarlos. Borrar acciones deprecadas. Limpiar código comentado.

### 20. Economía del juego — Dashboard y métricas [BAJO]
No hay visibilidad sobre la economía: cuánto dinero circula, inflación, distribución de riqueza. Si la economía se desequilibra no hay herramientas para detectarlo.
- **Acción:** Dashboard en admin con: dinero total en circulación, distribución por jugador (Gini), transacciones/hora, tasa de misiones completadas, software más investigado.
