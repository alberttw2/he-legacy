# Propuesta de actualización de hardware

El juego usa unidades de 2012-2014 (MHz, MB). Esta propuesta moderniza nombres y escalas manteniendo el balance del juego.

---

## Cambios propuestos

### Unidades actuales vs propuestas

| Componente | Actual | Propuesto | Factor |
|------------|--------|-----------|--------|
| CPU | MHz (500 - 4000) | GHz (0.5 - 4.0) | ÷ 1000, mostrar como GHz |
| RAM | MB (256 - 2048) | GB (0.25 - 2.0) | ÷ 1000, mostrar como GB |
| HDD | MB (100 - 100000) | GB (0.1 - 100) | ÷ 1000, mostrar como GB |
| NET | Mbit (1 - 1000) | Mbit/Gbps (sin cambio) | Ya es moderno |
| XHD | MB | GB | ÷ 1000 |

**Nota:** Solo cambia la PRESENTACIÓN. Los valores internos de la BD se mantienen iguales para no romper el balance.

### Nombres de servidor propuestos

| Actual | Propuesto |
|--------|-----------|
| Server #1 | VPS Node #1 |
| Server #2 | VPS Node #2 |
| (clan server) | Cluster Node |

### Tiers de hardware propuestos (presentación)

#### CPU
| Actual | Display actual | Display propuesto | Precio |
|--------|---------------|-------------------|--------|
| 500 | 500 MHz | 0.5 GHz — Intel Atom | Starter |
| 1000 | 1000 MHz | 1.0 GHz — Intel i3 | $999 |
| 1500 | 1500 MHz | 1.5 GHz — Intel i5 | $1,500 |
| 2000 | 2000 MHz | 2.0 GHz — Intel i7 | $3,000 |
| 2500 | 2500 MHz | 2.5 GHz — AMD Ryzen 5 | $6,000 |
| 3000 | 3000 MHz | 3.0 GHz — AMD Ryzen 7 | $15,000 |
| 3500 | 3500 MHz | 3.5 GHz — AMD Ryzen 9 | $20,000 |
| 4000 | 4000 MHz | 4.0 GHz — Intel i9 | $50,000 |

#### RAM
| Actual | Display actual | Display propuesto | Precio |
|--------|---------------|-------------------|--------|
| 256 | 256 MB | 256 MB — DDR3 | Starter |
| 512 | 512 MB | 512 MB — DDR3 | $999 |
| 1024 | 1024 MB | 1 GB — DDR4 | $5,000 |
| 2048 | 2048 MB | 2 GB — DDR4 | $25,000 |

#### HDD
| Actual | Display actual | Display propuesto | Precio |
|--------|---------------|-------------------|--------|
| 100 | 100 MB | 100 MB — HDD | Starter |
| 5000 | 5000 MB | 5 GB — HDD | $999 |
| 10000 | 10000 MB | 10 GB — SSD | $3,000 |
| 20000 | 20000 MB | 20 GB — SSD | $10,000 |
| 50000 | 50000 MB | 50 GB — NVMe | $30,000 |
| 100000 | 100000 MB | 100 GB — NVMe | $80,000 |

#### NET
| Actual | Display actual | Display propuesto | Precio |
|--------|---------------|-------------------|--------|
| 1 | 1 Mbit/s | 1 Mbps | Starter |
| 2 | 2 Mbit/s | 2 Mbps | $500 |
| 4 | 4 Mbit/s | 4 Mbps | $999 |
| 10 | 10 Mbit/s | 10 Mbps | $2,500 |
| 20 | 20 Mbit/s | 20 Mbps | $10,000 |
| 50 | 50 Mbit/s | 50 Mbps | $25,000 |
| 100 | 100 Mbit/s | 100 Mbps | $75,000 |
| 250 | 250 Mbit/s | 250 Mbps | $250,000 |
| 500 | 500 Mbit/s | 500 Mbps | $500,000 |
| 1000 | 1000 Mbit/s | 1 Gbps | $1,000,000 |

---

## Implementación

La forma más limpia de implementar esto es crear funciones de formato en `PC.class.php` que convierten los valores internos a display strings. NO se cambian los valores de la BD.

### Funciones de formato propuestas

```php
class HardwareFormat {
    public static function cpu($mhz) {
        $ghz = $mhz / 1000;
        $names = [
            0.5 => 'Intel Atom', 1.0 => 'Intel i3', 1.5 => 'Intel i5',
            2.0 => 'Intel i7', 2.5 => 'AMD Ryzen 5', 3.0 => 'AMD Ryzen 7',
            3.5 => 'AMD Ryzen 9', 4.0 => 'Intel i9'
        ];
        $name = $names[$ghz] ?? '';
        $suffix = $name ? " — $name" : '';
        return round($ghz, 1) . " GHz" . $suffix;
    }

    public static function ram($mb) {
        if ($mb >= 1024) return round($mb / 1024, 1) . ' GB';
        return $mb . ' MB';
    }

    public static function hdd($mb) {
        if ($mb >= 1000) return round($mb / 1000, 1) . ' GB';
        return $mb . ' MB';
    }

    public static function net($mbit) {
        if ($mbit >= 1000) return round($mbit / 1000, 1) . ' Gbps';
        return $mbit . ' Mbps';
    }

    public static function serverName($index) {
        return 'VPS Node #' . $index;
    }
}
```

### Archivos a modificar

1. `classes/PC.class.php` — Donde se muestra CPU/RAM/HDD/NET en la UI, usar las funciones de formato
2. `classes/Player.class.php` — Donde se muestra info de hardware en controlpanel
3. `template/contentStart.php` — Sidebar hardware info
4. `cron/rankGenerator.php` — Rankings de hardware

---

## Quieres que lo implemente?

Responde si quieres que aplique estos cambios. Solo cambia la capa de presentación, no los valores internos ni el balance del juego.
