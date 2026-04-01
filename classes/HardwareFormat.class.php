<?php

class HardwareFormat {

    private static $cpuNames = [
        500 => 'Intel Atom',
        1000 => 'Intel i3',
        1500 => 'Intel i5',
        2000 => 'Intel i7',
        2500 => 'AMD Ryzen 5',
        3000 => 'AMD Ryzen 7',
        3500 => 'AMD Ryzen 9',
        4000 => 'Intel i9',
    ];

    private static $ramTypes = [
        256 => 'DDR3',
        512 => 'DDR3',
        1024 => 'DDR4',
        2048 => 'DDR4',
    ];

    private static $hddTypes = [
        100 => 'HDD',
        5000 => 'HDD',
        10000 => 'SSD',
        20000 => 'SSD',
        50000 => 'NVMe',
        100000 => 'NVMe',
    ];

    public static function cpu($mhz) {
        $ghz = round($mhz / 1000, 1);
        $name = self::$cpuNames[$mhz] ?? self::closestName(self::$cpuNames, $mhz);
        return $ghz . ' GHz' . ($name ? ' — ' . $name : '');
    }

    public static function ram($mb) {
        $type = self::$ramTypes[$mb] ?? self::closestName(self::$ramTypes, $mb);
        $suffix = $type ? ' — ' . $type : '';
        if ($mb >= 1024) {
            return round($mb / 1024, 1) . ' GB' . $suffix;
        }
        return $mb . ' MB' . $suffix;
    }

    public static function hdd($mb) {
        $type = self::$hddTypes[$mb] ?? self::closestName(self::$hddTypes, $mb);
        $suffix = $type ? ' — ' . $type : '';
        if ($mb >= 1000) {
            return round($mb / 1000, 1) . ' GB' . $suffix;
        }
        return $mb . ' MB' . $suffix;
    }

    public static function net($mbit, $netType = 0) {
        $types = ['', ' (Sym)', ' (Fiber)'];
        $suffix = $types[$netType] ?? '';
        if ($mbit >= 1000) {
            return round($mbit / 1000, 1) . ' Gbps' . $suffix;
        }
        return $mbit . ' Mbps' . $suffix;
    }

    public static function xhd($mb) {
        if ($mb >= 1000) {
            return round($mb / 1000, 1) . ' GB';
        }
        return $mb . ' MB';
    }

    private static function closestName($map, $value) {
        $closest = null;
        $minDiff = PHP_INT_MAX;
        foreach ($map as $k => $v) {
            $diff = abs($k - $value);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $v;
            }
        }
        return $closest;
    }
}
