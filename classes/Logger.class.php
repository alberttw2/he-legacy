<?php

class Logger {
    private static $logFile = null;

    public static function init($file = null) {
        self::$logFile = $file ?? BASE_PATH . 'logs/game.log';
        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }

    public static function info($message, $context = []) { self::log('INFO', $message, $context); }
    public static function warn($message, $context = []) { self::log('WARN', $message, $context); }
    public static function error($message, $context = []) { self::log('ERROR', $message, $context); }

    private static function log($level, $message, $context) {
        if (!self::$logFile) self::init();
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $line = "[$timestamp] [$level] $message$contextStr\n";
        file_put_contents(self::$logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
