<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);
$pdo = PDO_DB::factory();

// For each active clan, calculate income based on server hardware
$clans = $pdo->query("
    SELECT c.clanID, SUM(h.cpu) as totalCpu, SUM(h.net) as totalNet
    FROM clan c
    INNER JOIN npc n ON n.npcIP = c.clanIP
    INNER JOIN hardware h ON h.userID = n.id AND h.isNPC = 1
    GROUP BY c.clanID
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($clans as $clan) {
    // Income formula: $1 per 100 MHz CPU per hour
    $income = floor($clan['totalcpu'] / 100);
    if ($income <= 0) continue;

    // Get clan members
    $members = $pdo->prepare("SELECT userID FROM clan_users WHERE clanID = ?");
    $members->execute([$clan['clanid']]);
    $memberList = $members->fetchAll(PDO::FETCH_ASSOC);

    if (empty($memberList)) continue;

    // Split income equally among members
    $perMember = floor($income / count($memberList));
    if ($perMember <= 0) continue;

    foreach ($memberList as $member) {
        // Add to their wealthiest bank account
        $acc = $pdo->prepare("SELECT id FROM bankAccounts WHERE bankUser = ? ORDER BY cash DESC LIMIT 1");
        $acc->execute([$member['userid']]);
        $accRow = $acc->fetch(PDO::FETCH_ASSOC);

        if ($accRow) {
            $pdo->prepare("UPDATE bankAccounts SET cash = cash + ? WHERE id = ?")->execute([$perMember, $accRow['id']]);
        }
    }
}

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
