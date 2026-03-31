<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';
require_once BASE_PATH . 'classes/BadgeManager.class.php';

$startTime = microtime(true);

$pdo = PDO_DB::factory();

// Badge 'h4x0r' (100+ IPs in list)
$stmt = $pdo->query('
    SELECT COUNT(*) AS total, userID
    FROM lists
    GROUP BY userID
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 100) {
        break;
    }
    BadgeManager::award('user', $row['userID'], 22);
}

// Badge 'b4nk3r' (50+ accounts in list)
$stmt = $pdo->query('
    SELECT COUNT(*) AS total, userID
    FROM lists_bankAccounts
    GROUP BY userID
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 50) {
        break;
    }
    BadgeManager::award('user', $row['userID'], 23);
}

// Badge 'who ate my ram' (20+ running softwares)
$stmt = $pdo->query('
    SELECT COUNT(*) AS total, userID
    FROM software_running
    WHERE isNPC = 0
    GROUP BY userID
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 20) {
        break;
    }
    BadgeManager::award('user', $row['userID'], 51);
}

// Badge 'Employee' (50+ completed missions)
$stmt = $pdo->query('
    SELECT COUNT(*) AS total, userID
    FROM missions_history
    WHERE completed = 1
    GROUP BY userID
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 50) {
        break;
    }
    BadgeManager::award('user', $row['userID'], 36);
}

// Badge 'I Cant Handle' (20+ IP resets)
$stmt = $pdo->query('
    SELECT uid
    FROM users_stats
    WHERE ipResets >= 20
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    BadgeManager::award('user', $row['uid'], 38);
}

// Badge 'Addicted player' (24h+ game play)
$stmt = $pdo->query('
    SELECT uid
    FROM users_stats
    WHERE timePlaying >= 86400
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    BadgeManager::award('user', $row['uid'], 40);
}

// Badge 'Rich' ($1,000,000+ on all bank accounts)
$stmt = $pdo->query('
    SELECT SUM(cash) AS total, bankUser
    FROM bankAccounts
    GROUP BY bankUser
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 1000000) {
        break;
    }
    BadgeManager::award('user', $row['bankUser'], 55);
}

// Badge 'DDoSer' (100+ DDoS attacks)
$stmt = $pdo->query('
    SELECT uid
    FROM users_stats
    WHERE ddosCount >= 100
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    BadgeManager::award('user', $row['uid'], 56);
}

// Badge 'Efficient' (10+ missions with 95%+ completion rate)
$stmt = $pdo->query('
    SELECT
        userID AS curUser,
        COUNT(*) AS total,
        ROUND((COUNT(*) / (SELECT COUNT(*) FROM missions_history WHERE userID = curUser))*100) AS rate
    FROM missions_history
    WHERE completed = 1
    GROUP BY userID
    ORDER BY
        total DESC,
        rate DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 10) {
        break;
    }
    if ($row['rate'] < 95) {
        continue;
    }
    BadgeManager::award('user', $row['curUser'], 59);
}

// Badge 'researcher' (50+ researches in round)
$stmt = $pdo->query('
    SELECT userID, COUNT(*) AS total
    FROM software_research
    GROUP BY userID
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 50) {
        break;
    }
    BadgeManager::award('user', $row['userID'], 65);
}

// Badge 'Hacker' (100+ hack count)
$stmt = $pdo->query('
    SELECT uid
    FROM users_stats
    WHERE hackCount >= 100
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    BadgeManager::award('user', $row['uid'], 67);
}

// Badge 'What\'ya Doin' (50+ running tasks at once)
$stmt = $pdo->query('
    SELECT COUNT(*) AS total, pCreatorID
    FROM processes
    WHERE
        TIMESTAMPDIFF(SECOND, NOW(), pTimeEnd) < 0 AND
        isPaused = 0
    GROUP BY pCreatorID
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 50) {
        break;
    }
    BadgeManager::award('user', $row['pCreatorID'], 69);
}

$elapsed = round(microtime(true) - $startTime, 4);
echo date('d/m/y H:i:s') . ' - ' . __FILE__ . ' - ' . $elapsed . "s\n";
