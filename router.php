<?php
/**
 * Router for PHP built-in development server.
 * Maps clean URLs (e.g. /login) to their .php counterparts.
 *
 * Usage: php -S 0.0.0.0:8080 -t /path/to/legacy-master/ router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static files directly
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'woff', 'woff2', 'ttf', 'svg', 'html'];
$ext = pathinfo($uri, PATHINFO_EXTENSION);
if (in_array($ext, $staticExtensions)) {
    return false; // Let the built-in server handle it
}

// If it's already a .php file, serve it
if ($ext === 'php') {
    return false;
}

// If a .php file exists for this path, route to it
$phpFile = __DIR__ . $uri . '.php';
if ($uri !== '/' && file_exists($phpFile)) {
    // Fix server vars so the game knows which page is being served
    $_SERVER['PHP_SELF'] = $uri . '.php';
    $_SERVER['SCRIPT_NAME'] = $uri . '.php';
    $_SERVER['SCRIPT_FILENAME'] = $phpFile;
    // Preserve query string in REQUEST_URI for the PHP file
    $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    $_SERVER['REQUEST_URI'] = $uri . '.php' . ($query ? '?' . $query : '');
    chdir(__DIR__);
    require $phpFile;
    return true;
}

// Default: serve index.php or let built-in server handle it
return false;
