<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

$stmt = $pdo->prepare("
    DELETE users_expire, users_online, internet_connections
    FROM users_expire
    LEFT JOIN users_online ON users_online.id = users_expire.userID
    LEFT JOIN internet_connections ON internet_connections.userID = users_expire.userID
    WHERE TIMESTAMPDIFF(SECOND, expireDate, NOW()) > 0
");
$stmt->execute();

$stmt = $pdo->prepare("
    DELETE users_online, internet_connections
    FROM users_online
    LEFT JOIN internet_connections ON internet_connections.userID = users_online.id
    WHERE TIMESTAMPDIFF(HOUR, loginTime, NOW()) > 10
");
$stmt->execute();

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
