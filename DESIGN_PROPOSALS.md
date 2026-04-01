# Propuestas de diseño

## 1. Internet: Nuevos tiers + Líneas asimétricas

### Tiers actuales
| NET | Precio | Display |
|-----|--------|---------|
| 1 | Starter | 1 Mbps |
| 2 | $500 | 2 Mbps |
| 4 | $999 | 4 Mbps |
| 10 | $2,500 | 10 Mbps |
| 20 | $10,000 | 20 Mbps |
| 50 | $25,000 | 50 Mbps |
| 100 | $75,000 | 100 Mbps |
| 250 | $250,000 | 250 Mbps |
| 500 | $500,000 | 500 Mbps |
| 1000 | $1,000,000 | 1 Gbps |

### Nuevos tiers propuestos
| NET | Precio | Display | Nota |
|-----|--------|---------|------|
| 2000 | $2,500,000 | 2 Gbps | Nuevo |
| 5000 | $7,500,000 | 5 Gbps | Nuevo |
| 10000 | $20,000,000 | 10 Gbps | Nuevo, endgame |

### Simetría de líneas (nueva mecánica)

Actualmente la velocidad de transferencia se calcula así:
- **Download**: `min(mi_download, su_upload)` — mi velocidad de bajada está limitada por la subida del otro
- **Upload**: `min(mi_upload, su_download)` — mi subida está limitada por la bajada del otro

Donde: `download_rate = NET / 8` y `upload_rate = NET / 16` (la subida es la mitad que la bajada).

**Propuesta: Líneas simétricas como upgrade premium**

Añadir un tipo de conexión: **Simétrica** vs **Asimétrica**:

| Tipo | Download | Upload | Precio (multiplicador) |
|------|----------|--------|----------------------|
| Asimétrica (actual) | NET/8 | NET/16 | x1 (base) |
| Simétrica | NET/8 | NET/8 | x1.5 |
| Fibra dedicada | NET/6 | NET/6 | x2.5 |

Implementación:
- Nuevo campo `netType` en tabla `hardware` (0=asimétrica, 1=simétrica, 2=dedicada)
- Upgrade disponible en Hardware → Internet tab
- `getDownloadSpeed()` en Process.class.php ya calcula las rates — solo hay que multiplicar upload rate según tipo

**Impacto en gameplay:**
- Jugadores que suben archivos (virus, uploads a clanes) se benefician de simétrica
- Servidores de clan con fibra dedicada son más difíciles de atacar (uploads rápidos = respuesta rápida)
- Añade una dimensión de decisión: ¿invierto en más velocidad o en simetría?

---

## 2. Servidor del clan: Investigación compartida

### Problema actual
El server del clan solo sirve para almacenar software y ser target de ataques. Los miembros no pueden aprovechar su CPU/RAM para nada útil.

### Propuesta: Investigación compartida en el clan

**Concepto**: Los miembros del clan pueden usar la CPU del servidor del clan para investigar software de forma cooperativa. La investigación es más rápida porque usa el hardware del clan.

#### Mecánica

1. **Research Pool del clan**: Un nuevo tipo de investigación "Clan Research" donde los miembros contribuyen tiempo de CPU del clan para investigar un software compartido.

2. **Flujo**:
   - El líder del clan elige un software para investigar (ej: "Investigar Cracker a versión 8.0")
   - Los miembros pueden "contribuir" al research desde la página del clan
   - La contribución usa CPU del clan server (no del personal)
   - Cuando el research termina, TODOS los miembros reciben el software

3. **Fórmula de tiempo**:
   - Tiempo base = mismo que research individual
   - Bonus clan = `base_time / (1 + clan_members_contributing * 0.3)`
   - Con 5 miembros contribuyendo: tiempo = base / 2.5 (60% más rápido)

4. **Limitaciones**:
   - Solo un research activo a la vez por clan
   - Necesita que el clan server tenga suficiente RAM para el software
   - El líder o un oficial debe aprobar qué se investiga
   - Los miembros que contribuyen no pueden usar la CPU del clan para otra cosa

#### Tablas nuevas
```sql
CREATE TABLE clan_research (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clanID INT NOT NULL,
    softType TINYINT NOT NULL,
    targetVersion INT NOT NULL,
    startedBy INT NOT NULL,
    startDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estimatedEnd TIMESTAMP NULL,
    status TINYINT DEFAULT 1,  -- 1=active, 2=completed, 3=cancelled
    INDEX (clanID, status)
);

CREATE TABLE clan_research_contributors (
    researchID INT NOT NULL,
    userID INT NOT NULL,
    joinedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cpuContributed INT DEFAULT 0,
    PRIMARY KEY (researchID, userID)
);
```

#### UI

**Página del Clan → pestaña "Research Lab"**:
- Muestra el research activo (si hay)
- Barra de progreso con contribuidores
- Botón "Contribute" para unirse
- Resultado: software disponible para todos al completar

### Otras ideas para el servidor del clan

**A. Clan Firewall**: El hardware del clan server determina la defensa contra DDoS enemigos. Más CPU = más resistencia.

**B. Clan Storage compartido**: Los miembros pueden subir/descargar software al/del server del clan. Ya existe parcialmente.

**C. Clan Bounty Board**: Misiones específicas del clan (atacar clan enemigo, defender contra DDoS). Requiere server activo.

**D. Clan Income**: El server del clan genera ingresos pasivos basados en su hardware (como mining). Dividendos entre miembros.

---

## Prioridad de implementación recomendada

| # | Propuesta | Complejidad | Impacto |
|---|-----------|-------------|---------|
| 1 | Nuevos tiers NET (2/5/10 Gbps) | Baja | Medio |
| 2 | Líneas simétricas | Media | Alto |
| 3 | Clan Research compartido | Alta | Muy alto |
| 4 | Clan Firewall (defensa DDoS) | Media | Alto |
| 5 | Clan Income | Media | Medio |
