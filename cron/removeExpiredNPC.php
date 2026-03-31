<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

$stmt = $pdo->query("
    SELECT npc_expire.npcID, npc.npcIP
    FROM npc_expire
    LEFT JOIN npc ON npc.id = npc_expire.npcID
    WHERE TIMESTAMPDIFF(SECOND, NOW(), expireDate) < 0
");

$expiredNPCs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtSoft = $pdo->prepare("
    SELECT id, softType
    FROM software
    WHERE userID = ? AND isNPC = 1 AND (softType = 30 OR softType = 31)
");

$delTexts = $pdo->prepare("
    DELETE FROM software_texts WHERE userID = ? AND isNPC = 1
");

$delFolders = $pdo->prepare("
    DELETE FROM software_folders WHERE folderID = ?
");

$delCascade = $pdo->prepare("
    DELETE npc, hardware, npc_expire, software, software_running, npc_reset
    FROM npc_expire
    LEFT JOIN npc ON npc.id = npc_expire.npcID
    LEFT JOIN npc_reset ON npc_reset.npcID = npc_expire.npcID
    LEFT JOIN hardware ON (hardware.userID = npc.id AND hardware.isNPC = 1)
    LEFT JOIN software ON (software.userID = npc.id AND software.isNPC = 1)
    LEFT JOIN software_running ON (software_running.userID = npc.id AND software_running.isNPC = 1)
    WHERE npc_expire.npcID = ?
");

$selectLists = $pdo->prepare("
    SELECT id, userID FROM lists WHERE ip = ?
");

$insertNotification = $pdo->prepare("
    INSERT INTO lists_notifications (userID, ip, notificationType)
    VALUES (?, ?, '1')
");

$delListsSpecs = $pdo->prepare("
    DELETE lists, lists_specs
    FROM lists
    LEFT JOIN lists_specs ON lists_specs.listID = lists.id
    WHERE ip = ?
");

$delVirusDdos = $pdo->prepare("DELETE FROM virus_ddos WHERE ip = ?");
$delVirus = $pdo->prepare("DELETE FROM virus WHERE installedIp = ?");
$delConnections = $pdo->prepare("DELETE FROM internet_connections WHERE ip = ?");

foreach ($expiredNPCs as $row) {
    $npcID = (int)$row['npcID'];
    $npcIP = $row['npcIP'];

    // Delete software texts and folders for softType 30/31
    $stmtSoft->execute([$npcID]);
    $softwares = $stmtSoft->fetchAll(PDO::FETCH_ASSOC);

    foreach ($softwares as $soft) {
        if ((int)$soft['softType'] === 30) {
            $delTexts->execute([$npcID]);
        } else {
            $delFolders->execute([$soft['id']]);
        }
    }

    // Cascade delete NPC and related records
    $delCascade->execute([$npcID]);

    // Notify users who had this IP in their lists
    $selectLists->execute([$npcIP]);
    $lists = $selectLists->fetchAll(PDO::FETCH_ASSOC);

    foreach ($lists as $list) {
        $insertNotification->execute([$list['userID'], $npcIP]);
    }

    // Delete lists and specs
    $delListsSpecs->execute([$npcIP]);

    // Delete virus-related records and connections
    $delVirusDdos->execute([$npcIP]);
    $delVirus->execute([$npcIP]);
    $delConnections->execute([$npcIP]);
}

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
