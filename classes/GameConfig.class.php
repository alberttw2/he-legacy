<?php
class GameConfig {
    private static $cache = null;

    public static function get($key, $default = null) {
        if (self::$cache === null) self::loadAll();
        return self::$cache[$key] ?? $default;
    }

    public static function set($key, $value) {
        $pdo = PDO_DB::factory();
        $stmt = $pdo->prepare("UPDATE game_config SET config_value = ? WHERE config_key = ?");
        $stmt->execute([$value, $key]);
        if (self::$cache !== null) self::$cache[$key] = $value;
    }

    public static function getAll() {
        if (self::$cache === null) self::loadAll();
        return self::$cache;
    }

    public static function getAllGrouped() {
        $pdo = PDO_DB::factory();
        $rows = $pdo->query("SELECT * FROM game_config ORDER BY category, config_key")->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['category']][] = $row;
        }
        return $grouped;
    }

    private static function loadAll() {
        $pdo = PDO_DB::factory();
        $rows = $pdo->query("SELECT config_key, config_value FROM game_config")->fetchAll(PDO::FETCH_ASSOC);
        self::$cache = [];
        foreach ($rows as $row) {
            self::$cache[$row['config_key']] = $row['config_value'];
        }
    }
}
