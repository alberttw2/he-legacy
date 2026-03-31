<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

$stmt = $pdo->prepare("DELETE FROM npc_down WHERE TIMESTAMPDIFF(SECOND, NOW(), downUntil) < 0");
$stmt->execute();

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
