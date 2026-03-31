# MISSIONS.md

Documentación del sistema de misiones de Hacker Experience Legacy.

---

## Resumen

Las misiones son la principal fuente de ingresos para los jugadores. Se generan automáticamente por el cron `cron/generateMissions.php` cada 15 minutos. Un jugador solo ve misiones de su nivel y solo las de hirers cuya IP tenga en su Hacked Database.

---

## Niveles de misión

El nivel del jugador se determina por la versión de su mejor Cracker instalado:

| Nivel | Requisito Cracker | Misiones disponibles | Multiplicador premio |
|-------|-------------------|---------------------|---------------------|
| 1 | Cualquiera (o sin cracker) | 50 misiones | x1.0 |
| 2 | Versión >= 30 | 30 misiones | x1.1 premio, x1.25 base |
| 3 | Versión >= 60 | 25 misiones | x1.2 premio, x1.5 base |

---

## Tipos de misión

### Misiones regulares (generadas por cron)

| Tipo | Nombre | Descripción | Premio base | Probabilidad | Requisitos del jugador |
|------|--------|-------------|-------------|-------------|----------------------|
| 1 | **Delete software** | Hackear el server víctima y borrar un software específico | $150–350 | 29% | Cracker ≥ Firewall víctima |
| 2 | **Steal software** | Hackear el server víctima y copiar (download) un software específico | $250–450 | 30% | Cracker ≥ Firewall víctima, espacio en HDD |
| 3 | **Check bank status** | Hackear un banco, encontrar una cuenta específica y verificar su saldo | $500–750 | 20% | Cracker ≥ Firewall banco |
| 4 | **Transfer money** | Hackear un banco, encontrar una cuenta y transferir dinero a otra cuenta en otro banco | $1000–1500 | 10% | Cracker ≥ Firewall de ambos bancos |
| 5 | **Destroy server (DDoS)** | Realizar un ataque DDoS contra un servidor ISP | $3000–5000 | 11% | Certificación Hacking avanzado, software DDoS |

### Misiones de storyline (Doom)

Estas misiones son parte de la historia principal del juego y se activan por eventos específicos.

| Tipo | Trigger | Objetivo | Siguiente paso |
|------|---------|----------|---------------|
| 50 | Jugador desarrolló CRC X | Hackear NSA, descargar Doom | Instalar Doom |
| 51 | Jugador descargó Doom | Instalar Doom | Fin storyline |
| 52 | Jugador descargó CRC X | Hackear NSA, descargar Doom | Instalar Doom |
| 53 | Jugador recibió upload de Doom | Instalar Doom | Fin storyline |
| 54 | Jugador recibió upload de CRC X | Hackear NSA, descargar Doom | Instalar Doom |

### Misiones tutorial

Se crean automáticamente para nuevos jugadores como parte del onboarding.

| Tipo | Paso | Descripción | Premio |
|------|------|-------------|--------|
| 80 | Tutorial 1 | Hackear un servidor y encontrar un archivo | $500 |
| 81 | Tutorial 2 | Ir al panel de software del servidor hackeado | — |
| 82 | Tutorial 3 | Ir al log del servidor y cambiar la IP de la víctima | — |
| 83 | Tutorial 4 | Continuación tutorial | — |
| 84 | Tutorial 5 | Completar tutorial | — |

---

## Flujo de una misión regular

```
1. El jugador va a Missions → Available missions
2. Ve la lista de misiones de su nivel (solo hirers conocidos)
3. Clica en una misión para ver detalles
4. Acepta la misión (botón "Accept mission")
5. La misión pasa a estado "Current mission" (status=2)
6. El jugador debe:
   a. Navegar al servidor víctima (Internet → IP)
   b. Hackear el servidor (Login con Cracker)
   c. Completar el objetivo según el tipo de misión
7. Vuelve a Missions → Current mission
8. Completa la misión (selecciona cuenta bancaria para recibir pago)
9. Recibe el premio en su cuenta bancaria
```

---

## Hirers (NPCs que contratan misiones)

Cada nivel tiene sus propios NPCs que contratan misiones. El jugador debe tener la IP del hirer en su Hacked Database para ver sus misiones.

### Nivel 1 — NPC Type 71 (15 hirers)
Capitalism, elgooG, Fiasco Systems, GayPal, Gimme Your Bucks, Hell Computers, McDiabetes, Microlost, Murder King, Noplace, Pineapple, Stalker, uPay, Very Cheesy Pictures, WTF

### Nivel 2 — NPC Type 72 (10 hirers)
Broke, CatVideos, Elvi's, Fail, Hacker Inside, Insane, Life's Though, Nokids, Oh Deere, Titanic

### Nivel 3 — NPC Type 73 (10 hirers)
Abersnobby & Bitch, Nothing to do, Nuke, Oops, Pervert, Sexsi, Sunk Microsystems, Toshibe, Weird, Yahoo?

---

## Víctimas

- **Tipos 1-2** (delete/steal): La víctima es otro hirer NPC del mismo nivel. Se selecciona un software aleatorio (softType < 7) del NPC víctima.
- **Tipo 3** (bank check): La víctima es un banco NPC. Nivel 1 usa Bank 1-2, Nivel 2 usa Bank 1-4, Nivel 3 usa cualquier banco.
- **Tipo 4** (transfer): Similar a tipo 3 pero con dos bancos (origen y destino).
- **Tipo 5** (DDoS): La víctima es un NPC tipo ISP con hardware no básico.

### Bancos disponibles
| NPC Key | Nombre | Niveles de misión |
|---------|--------|-------------------|
| BANK/1 | First International Bank | 1, 2, 3 |
| BANK/2 | HEBC | 1, 2, 3 |
| BANK/3 | American Expense | 2, 3 |
| BANK/4 | Swiss International Bank | 2, 3 |
| BANK/5 | Ultimate Bank | 3 |

---

## Requisitos para cada tipo

| Tipo | Software necesario | Certificación | Otros |
|------|-------------------|--------------|-------|
| 1 (Delete) | Cracker running | Hacking 101 (cert 2) | — |
| 2 (Steal) | Cracker running | Hacking 101 (cert 2) | Espacio en HDD local |
| 3 (Bank check) | Cracker running | Hacking 101 (cert 2) | Tener cuenta bancaria propia |
| 4 (Transfer) | Cracker running | Hacking 101 (cert 2) | Tener cuenta bancaria propia |
| 5 (DDoS) | Cracker + DDoS software | Advanced Hacking (cert 4) | Hardware potente |

---

## Generación de misiones (cron)

El script `cron/generateMissions.php` se ejecuta cada 15 minutos:

1. Borra misiones disponibles no aceptadas (status=1)
2. Genera nuevas misiones hasta alcanzar el objetivo por nivel
3. Para tipo 3/4: crea cuentas bancarias temporales asociadas a la misión
4. Para tipo 5: busca un NPC ISP con hardware no básico como víctima
5. Genera seeds de texto para cada misión (greeting, intro, payment, etc.)

---

## Misión seeds (texto generado)

Cada misión tiene texto narrativo generado aleatoriamente con estos componentes:

| Campo | Descripción | Variantes |
|-------|-------------|-----------|
| greeting | Saludo del hirer | 1-3 |
| intro | Introducción de la misión | 1-3 |
| victim_call | Cómo se refiere a la víctima | 1-4 (tipo 1-2: 3, tipo 3-5: 2) |
| payment | Mención del pago | 1-3 |
| victim_location | Dónde encontrar a la víctima | 1-3 (tipo 3-5: 2) |
| warning | Advertencia opcional | 0-3 (solo tipos 1-2) |
| action | Instrucción de acción | 0-3 (solo tipos 1-2) |

---

## Estados de misión

| Status | Significado |
|--------|-------------|
| 1 | Disponible (en la lista) |
| 2 | Aceptada (en progreso) |
| 3 | Completada (pendiente de cobro) |
| 4 | Finalizada/abortada |

---

## Progresión recomendada

```
Nuevo jugador (cert 0)
  → Obtener cert "Basic Tutorial" (gratis)
  → Obtener cert "Hacking 101" (gratis)
  → Descargar Cracker del Download Center
  → Instalar y ejecutar Cracker
  → Hackear hirers de Nivel 1 (elgooG, Murder King, Pineapple...)
  → Aceptar misiones Nivel 1 (Delete/Steal: $150-450)
  → Ganar dinero → Mejorar hardware → Investigar mejor Cracker

Jugador intermedio (cracker v30+)
  → Misiones Nivel 2 desbloqueadas ($185-560)
  → Obtener cert "Intermediate Hacking" ($50)
  → Hackear hirers de Nivel 2

Jugador avanzado (cracker v60+)
  → Misiones Nivel 3 desbloqueadas ($220-750)
  → Obtener cert "Advanced Hacking" ($200) → DDoS desbloqueado
  → Misiones DDoS ($3600-6000)
  → Participar en guerras de clanes
  → Storyline Doom
```
