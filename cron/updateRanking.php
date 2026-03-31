<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

// User ranking
$stmt = $pdo->query("
    SELECT userID FROM hist_users_current
    WHERE reputation > 1000
    ORDER BY reputation DESC
");

$updateUserRank = $pdo->prepare("UPDATE ranking_user SET rank = ? WHERE userID = ?");
$rank = 0;
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userID) {
    $rank++;
    $updateUserRank->execute([$rank, $userID]);
}

// Clan ranking
$stmt = $pdo->query("
    SELECT cid FROM hist_clans_current
    WHERE reputation > 0
    ORDER BY reputation DESC
");

$updateClanRank = $pdo->prepare("UPDATE ranking_clan SET rank = ? WHERE clanID = ?");
$rank = 0;
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $clanID) {
    $rank++;
    $updateClanRank->execute([$rank, $clanID]);
}

// Software ranking
$stmt = $pdo->query("
    SELECT softID FROM software_research
    WHERE softID IN (SELECT softID FROM ranking_software)
    ORDER BY newVersion DESC
");

$updateSoftRank = $pdo->prepare("UPDATE ranking_software SET rank = ? WHERE softID = ?");
$rank = 0;
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $softID) {
    $rank++;
    $updateSoftRank->execute([$rank, $softID]);
}

// DDoS ranking
$stmt = $pdo->query("
    SELECT id, power FROM round_ddos
    WHERE vicNPC = 0
    ORDER BY power DESC
");

$updateDdosRank = $pdo->prepare("UPDATE ranking_ddos SET rank = ? WHERE ddosID = ?");
$rank = 0;
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $rank++;
    $updateDdosRank->execute([$rank, $row['id']]);
}

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
