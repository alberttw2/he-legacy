<?php

/**
 * Simple file-based rate limiter.
 * Tracks attempts per IP in a temp directory.
 */
class RateLimiter {

    private static $dir = null;

    private static function getDir() {
        if (self::$dir === null) {
            self::$dir = sys_get_temp_dir() . '/he_ratelimit/';
            if (!is_dir(self::$dir)) {
                mkdir(self::$dir, 0755, true);
            }
        }
        return self::$dir;
    }

    /**
     * Check if an action is rate limited.
     *
     * @param string $action  Action name (e.g., 'login', 'register', 'ajax')
     * @param int    $maxAttempts  Max attempts allowed in the window
     * @param int    $windowSeconds  Time window in seconds
     * @param string|null $ip  IP address (defaults to REMOTE_ADDR)
     * @return bool  True if allowed, false if rate limited
     */
    public static function check($action, $maxAttempts, $windowSeconds, $ip = null) {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $file = self::getDir() . md5($action . '_' . $ip) . '.json';

        $attempts = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data)) {
                $cutoff = time() - $windowSeconds;
                $attempts = array_filter($data, function($t) use ($cutoff) {
                    return $t > $cutoff;
                });
            }
        }

        if (count($attempts) >= $maxAttempts) {
            return false;
        }

        $attempts[] = time();
        file_put_contents($file, json_encode(array_values($attempts)), LOCK_EX);
        return true;
    }

    /**
     * Reset rate limit for an action/IP (e.g., after successful login).
     */
    public static function reset($action, $ip = null) {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $file = self::getDir() . md5($action . '_' . $ip) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Get remaining seconds until rate limit expires.
     */
    public static function retryAfter($action, $windowSeconds, $ip = null) {
        $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $file = self::getDir() . md5($action . '_' . $ip) . '.json';

        if (!file_exists($file)) return 0;

        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data) || empty($data)) return 0;

        $oldest = min($data);
        $expires = $oldest + $windowSeconds;
        $remaining = $expires - time();

        return max(0, $remaining);
    }
}
