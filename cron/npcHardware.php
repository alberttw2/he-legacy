<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

$stmt = $pdo->prepare("SELECT id FROM npc INNER JOIN npc_key ON npc_key.npcID = npc.id");
$stmt->execute();

$npcIDs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$updateStmt = $pdo->prepare("
    UPDATE hardware
    SET hdd = 10000, cpu = 8000, net = 50, ram = 1024
    WHERE isNPC = 1 AND userID = :npcID
");

foreach ($npcIDs as $npcID) {
    $updateStmt->execute([':npcID' => $npcID]);
}

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
