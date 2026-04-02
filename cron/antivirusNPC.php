<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

// Find NPCs whose AV scan is due
$stmt = $pdo->query("
    SELECT npc_reset.npcID, npc.npcIP
    FROM npc_reset
    INNER JOIN npc ON npc.id = npc_reset.npcID
    WHERE TIMESTAMPDIFF(SECOND, NOW(), npc_reset.nextScan) < 0
");

$npcs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtViruses = $pdo->prepare("
    SELECT software.id, software.softName, software.softVersion, software.softType
    FROM software
    INNER JOIN (
        SELECT virus.virusID
        FROM virus
        WHERE virus.installedip = ?
    ) v ON v.virusID = software.id
");

$stmtListUsers = $pdo->prepare("
    SELECT userID FROM lists WHERE virusID = ?
");

$stmtInsertNotif = $pdo->prepare("
    INSERT INTO lists_notifications (userID, ip, notificationType, virusName)
    VALUES (?, ?, '3', ?)
");

$stmtUpdateLists = $pdo->prepare("UPDATE lists SET virusID = 0 WHERE ip = ?");
$stmtDelVirusDdos = $pdo->prepare("DELETE FROM virus_ddos WHERE ip = ?");
$stmtDelVirus = $pdo->prepare("DELETE FROM virus WHERE installedIp = ?");
$stmtDelRunning = $pdo->prepare("DELETE FROM software_running WHERE userID = ? AND isNPC = '1'");
$stmtDelSoftware = $pdo->prepare("DELETE FROM software WHERE userID = ? AND isNPC = '1'");

foreach ($npcs as $npc) {
    $npcID = (int)($npc['npcid'] ?? $npc['npcID'] ?? 0);
    $npcIP = $npc['npcip'] ?? $npc['npcIP'] ?? 0;

    // Find viruses installed on this NPC
    $stmtViruses->execute([$npcIP]);
    $viruses = $stmtViruses->fetchAll(PDO::FETCH_ASSOC);

    foreach ($viruses as $virus) {
        $virusID = (int)$virus['id'];

        // Get users who will be affected
        $stmtListUsers->execute([$virusID]);
        $affectedUsers = $stmtListUsers->fetchAll(PDO::FETCH_COLUMN);

        foreach ($affectedUsers as $userID) {
            // Build virus name with extension
            $virusName = $virus['softname'];

            if ((int)$virus['softtype'] === 97) {
                $virusName .= '.vddos ';
            } elseif ((int)$virus['softtype'] === 98) {
                $virusName .= '.vwarez ';
            } elseif ((int)$virus['softtype'] === 99) {
                $virusName .= '.vpsam ';
            }

            $virusName .= $virus['softversion'];

            $stmtInsertNotif->execute([$userID, $npcIP, $virusName]);
        }
    }

    // Remove virus references from lists
    $stmtUpdateLists->execute([$npcIP]);

    // Delete viruses
    $stmtDelVirusDdos->execute([$npcIP]);
    $stmtDelVirus->execute([$npcIP]);

    // Delete software running and software for NPC
    $stmtDelRunning->execute([$npcID]);
    $stmtDelSoftware->execute([$npcID]);
}

// Update next scan: +1 day (comment says 7 days but code uses 1)
$scanInterval = 1;
$pdo->exec("
    UPDATE npc_reset
    SET nextScan = DATE_ADD(NOW(), INTERVAL {$scanInterval} DAY)
    WHERE TIMESTAMPDIFF(SECOND, NOW(), npc_reset.nextScan) < 0
");

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
