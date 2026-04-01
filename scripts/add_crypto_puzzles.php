<?php
/**
 * Script to add PUZZLE_CRYPTO NPCs to the database without touching existing NPCs.
 * Run once: php scripts/add_crypto_puzzles.php
 */

require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();

// Check if PUZZLE_CRYPTO NPCs already exist
$sql = "SELECT COUNT(*) AS total FROM npc_key WHERE `key` LIKE 'PUZZLE_CRYPTO/%'";
$existing = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

if ($existing > 0) {
    echo "PUZZLE_CRYPTO NPCs already exist ($existing found). Skipping.\n";
    exit(0);
}

// Load NPC definitions from npc.json
$npcData = json_decode(file_get_contents(BASE_PATH . 'json/npc.json'), true);

if (!isset($npcData['PUZZLE_CRYPTO'])) {
    echo "ERROR: PUZZLE_CRYPTO section not found in npc.json\n";
    exit(1);
}

$cryptoPuzzles = $npcData['PUZZLE_CRYPTO'];
$npcType = $cryptoPuzzles['type']; // type 7 (same as PUZZLE)

// Generate unique IPs for each crypto puzzle NPC
function generateUniqueIP($pdo) {
    do {
        $ip = rand(10, 250) . '.' . rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254);
        $ipLong = ip2long($ip);
        $sql = "SELECT COUNT(*) AS total FROM npc WHERE npcIP = '$ipLong'";
        $count = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;
        // Also check users table
        $sql2 = "SELECT COUNT(*) AS total FROM users WHERE gameIP = '$ipLong'";
        $count2 = $pdo->query($sql2)->fetch(PDO::FETCH_OBJ)->total;
    } while ($count > 0 || $count2 > 0);
    return $ipLong;
}

$pdo->beginTransaction();

try {
    for ($i = 1; $i <= 10; $i++) {
        $entry = $cryptoPuzzles[(string)$i];
        $nameEn = $entry['name']['en'];
        $namePt = $entry['name']['pt'];
        $hw = $entry['hardware'];
        $webEn = $entry['web']['en'];
        $webPt = $entry['web']['pt'];

        $npcIP = generateUniqueIP($pdo);

        // Insert NPC
        $sql = "INSERT INTO npc (npcIP, npcType) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcIP, $npcType));
        $npcID = $pdo->lastInsertId();

        echo "Created NPC #$npcID: $nameEn (IP: " . long2ip($npcIP) . ")\n";

        // Insert npc_info_en
        $sql = "INSERT INTO npc_info_en (npcID, name, web) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, $nameEn, $webEn));

        // Insert npc_info_pt
        $sql = "INSERT INTO npc_info_pt (npcID, name, web) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, $namePt, $webPt));

        // Insert npc_key
        $key = "PUZZLE_CRYPTO/$i";
        $sql = "INSERT INTO npc_key (npcID, `key`) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, $key));

        // Insert hardware
        $sql = "INSERT INTO hardware (userID, isNPC, cpu, hdd, ram, net) VALUES (?, 1, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, $hw['cpu'], $hw['hdd'], $hw['ram'], $hw['net']));

        // Insert log entry
        $sql = "INSERT INTO log (userID, isNPC, text) VALUES (?, 1, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, 'localhost logged in'));

        // Insert npc_reset
        $sql = "INSERT INTO npc_reset (npcID) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID));

        echo "  -> npc_info, npc_key ($key), hardware, log, npc_reset created.\n";
    }

    $pdo->commit();
    echo "\nDone! 10 PUZZLE_CRYPTO NPCs created successfully.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
