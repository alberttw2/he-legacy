<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();
$start = microtime(true);

// Fetch all users with their stats and clan info
$stmt = $pdo->prepare("
    SELECT
        h.userID, s.exp, s.dateJoined, s.timePlaying, s.bitcoinSent, s.warezSent, s.spamSent,
        s.profileViews, s.hackCount, s.ipResets, s.moneyEarned, s.moneyTransfered, s.moneyHardware, s.moneyResearch,
        clan.clanID, clan.name
    FROM hist_users_current h
    INNER JOIN users_stats s ON s.uid = h.userID
    LEFT JOIN clan_users ON clan_users.userID = h.userID
    LEFT JOIN clan ON clan.clanID = clan_users.clanID
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare reusable statements
$stmtDdos = $pdo->prepare("SELECT COUNT(*) FROM round_ddos WHERE attID = ?");

$stmtUpdate = $pdo->prepare("
    UPDATE hist_users_current
    SET
        reputation = ?,
        age = TIMESTAMPDIFF(DAY, ?, NOW()),
        clanID = ?,
        clanName = ?,
        timePlaying = ?,
        warezSent = ?,
        spamSent = ?,
        bitcoinSent = ?,
        profileViews = ?,
        hackCount = ?,
        ddosCount = ?,
        ipResets = ?,
        moneyEarned = ?,
        moneyTransfered = ?,
        moneyHardware = ?,
        moneyResearch = ?
    WHERE userID = ?
");

$stmtCache = $pdo->prepare("UPDATE cache SET cache.reputation = ? WHERE userID = ?");

foreach ($users as $user) {
    $userID        = $user['userid'];
    $exp           = $user['exp'];
    $dateJoined    = $user['datejoined'];
    $timePlaying   = $user['timeplaying'];
    $bitcoinSent   = $user['bitcoinsent'];
    $warezSent     = $user['warezsent'];
    $spamSent      = $user['spamsent'];
    $profileViews  = $user['profileviews'];
    $hackCount     = $user['hackcount'];
    $ipResets      = $user['ipresets'];
    $moneyEarned   = $user['moneyearned'];
    $moneyTransfered = $user['moneytransfered'];
    $moneyHardware = $user['moneyhardware'];
    $moneyResearch = $user['moneyresearch'];
    $clanID        = $user['clanid'];
    $clanName      = $user['name'];

    // Get DDoS count for this user
    $stmtDdos->execute([$userID]);
    $ddosCount = (int)$stmtDdos->fetchColumn();

    // Update hist_users_current
    $stmtUpdate->execute([
        $exp,
        $dateJoined,
        $clanID,
        $clanName,
        $timePlaying,
        $warezSent,
        $spamSent,
        $bitcoinSent,
        $profileViews,
        $hackCount,
        $ddosCount,
        $ipResets,
        $moneyEarned,
        $moneyTransfered,
        $moneyHardware,
        $moneyResearch,
        $userID
    ]);

    // Update cache reputation
    $stmtCache->execute([$exp, $userID]);
}

// Update clan stats
$pdo->exec("
    UPDATE hist_clans_current
    INNER JOIN clan_stats ON clan_stats.cid = hist_clans_current.cid
    SET
        hist_clans_current.clanIP = (
            SELECT npcIP
            FROM npc
            WHERE npc.id = hist_clans_current.cid
        ),
        hist_clans_current.reputation = (
            SELECT power
            FROM clan
            WHERE clan.clanID = hist_clans_current.cid
        ),
        hist_clans_current.members = (
            SELECT COUNT(*)
            FROM clan_users
            WHERE clan_users.clanID = hist_clans_current.cid
        ),
        hist_clans_current.won = clan_stats.won,
        hist_clans_current.lost = clan_stats.lost,
        hist_clans_current.clicks = clan_stats.pageClicks
");

$elapsed = round(microtime(true) - $start, 4);
echo date('d/m/y H:i:s') . ' - ' . __FILE__ . ' - ' . $elapsed . "s\n";
