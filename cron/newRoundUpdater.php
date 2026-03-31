<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();
$start = microtime(true);

// Check if a round is ready to start
$stmt = $pdo->prepare("SELECT id FROM round WHERE status = 0 AND TIMESTAMPDIFF(SECOND, startDate, NOW()) > 0 LIMIT 1");
$stmt->execute();
$round = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$round) {
    $elapsed = round(microtime(true) - $start, 4);
    echo date('d/m/y H:i:s') . ' - ' . __FILE__ . ' - No round ready - ' . $elapsed . "s\n";
    exit;
}

$roundID = $round['id'];

// TODO: Call NPCGenerator when migrated
// Original: os.system('python /var/www/python/npc_generator.py')

/**
 * Generate a random IP address
 */
function ip_generator() {
    return mt_rand(1, 254) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255);
}

/**
 * Generate a random password
 */
function pwd_generator($size = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $size; $i++) {
        $password .= $chars[mt_rand(0, $max)];
    }
    return $password;
}

/**
 * Generate a random bank account number (no leading zero)
 */
function acc_generator($size = 6) {
    $digits = '0123456789';
    // First digit must not be zero
    $first = '';
    while ($first === '' || $first === '0') {
        $first = $digits[mt_rand(0, 9)];
    }
    $account = $first;
    for ($i = 1; $i < $size; $i++) {
        $account .= $digits[mt_rand(0, 9)];
    }
    return $account;
}

/**
 * Get the first bank NPC ID
 */
function firstBankID($pdo) {
    $stmt = $pdo->prepare("SELECT id FROM npc WHERE npcType = 1 LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['id'] : null;
}

/**
 * Insert a news entry
 */
function insertNews($pdo, $title, $content) {
    $stmt = $pdo->prepare("
        INSERT INTO news (author, title, content, news.date, news.type)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    $stmt->execute(['-8', $title, $content, '']);
}

// Reset last IP/password reset timestamps for all users
$pdo->exec("UPDATE users_stats SET lastIpReset = NOW(), lastPwdReset = NOW()");

// Fetch all user IDs
$stmt = $pdo->prepare("SELECT id FROM users");
$stmt->execute();
$userIDs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare reusable statements for user setup
$stmtHardware = $pdo->prepare("INSERT INTO hardware (userID, name) VALUES (?, 'Server #1')");
$stmtUserUpdate = $pdo->prepare("UPDATE users SET gameIP = INET_ATON(?), gamePass = ? WHERE id = ? LIMIT 1");
$stmtBankAccount = $pdo->prepare("
    INSERT INTO bankAccounts (bankAcc, bankPass, bankID, bankUser, cash, dateCreated)
    VALUES (?, ?, ?, ?, '0', NOW())
");

foreach ($userIDs as $user) {
    $uid = $user['id'];

    // Create hardware for user
    $stmtHardware->execute([$uid]);

    // Generate and assign IP + password
    $stmtUserUpdate->execute([ip_generator(), pwd_generator(), $uid]);

    // Create bank account
    $bankID = firstBankID($pdo);
    $stmtBankAccount->execute([acc_generator(), pwd_generator(6), $bankID, $uid]);
}

// Set up clan NPCs
$stmt = $pdo->prepare("SELECT clanID, clanIP FROM clan");
$stmt->execute();
$clans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtNpc = $pdo->prepare("INSERT INTO npc (npcType, npcIP, npcPass) VALUES (10, INET_ATON(?), ?)");
$stmtClanUpdate = $pdo->prepare("UPDATE clan SET clanIP = INET_ATON(?) WHERE clan.clanID = ?");
$stmtNpcHardware = $pdo->prepare("INSERT INTO hardware (userID, name, isNPC) VALUES (?, 'Server #1', 1)");

foreach ($clans as $clan) {
    $clanIP = ip_generator();

    // Insert clan NPC
    $stmtNpc->execute([$clanIP, pwd_generator()]);
    $npcID = $pdo->lastInsertId();

    // Update clan IP
    $stmtClanUpdate->execute([$clanIP, $clan['clanid']]);

    // Create hardware for clan NPC
    $stmtNpcHardware->execute([$npcID]);
}

// Clear online users
$pdo->exec("DELETE FROM users_online");

// Activate the round
$stmt = $pdo->prepare("UPDATE round SET status = 1 WHERE id = ?");
$stmt->execute([$roundID]);

// Insert round stats
$stmt = $pdo->prepare("INSERT INTO round_stats (id) VALUES (?)");
$stmt->execute([$roundID]);

// Run ranking updates
require_once BASE_PATH . 'cron/updateRanking.php';
// TODO: require_once BASE_PATH . 'cron/rank_generator.php'; when migrated

// Insert news about the new round
$title = 'Round #' . $roundID . ' started';
$content = "Ye'all, get ready to hack! Round " . $roundID . " just started.";
insertNews($pdo, $title, $content);

$elapsed = round(microtime(true) - $start, 4);
echo date('d/m/y H:i:s') . ' - ' . __FILE__ . ' - ' . $elapsed . "s\n";
