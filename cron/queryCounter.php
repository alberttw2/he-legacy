<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$queryToAdd = (int) $argv[1];

$filePath = BASE_PATH . 'status/queries.txt';

$currentTotal = (int) file_get_contents($filePath);
$newTotal = $currentTotal + $queryToAdd;

file_put_contents($filePath, (string) $newTotal);

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
