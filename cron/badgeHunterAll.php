<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';
require_once BASE_PATH . 'classes/BadgeManager.class.php';

$startTime = microtime(true);

$pdo = PDO_DB::factory();

// Badge 'Web Celeb' (profile visited 1000+ times)
$stmt = $pdo->query('
    SELECT
        hist_users.userID,
        (
            SUM(hist_users.profileviews) +
            SUM(hist_users_current.profileviews)
        ) AS totalClicks
    FROM hist_users
    INNER JOIN hist_users_current
    ON hist_users_current.userID = hist_users.userID
    GROUP BY hist_users.userID
    ORDER BY totalClicks DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['totalclicks'] < 1000) {
        break;
    }
    BadgeManager::award('user', $row['userid'], 31);
}

// Badge 'you are addicted' (complete a total of 500 missions)
$stmt = $pdo->query('
    SELECT
        hist_missions.userID,
        COUNT(*) +
        (
            SELECT COUNT(*)
            FROM missions_history
            WHERE
                missions_history.userID = hist_missions.userID AND
                completed = 1
        ) AS totalMissions
    FROM hist_missions
    WHERE completed = 1
    GROUP BY userID
    ORDER BY totalMissions DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['totalmissions'] < 500) {
        break;
    }
    BadgeManager::award('user', $row['userid'], 37);
}

// Badge 'Noob Certification' (reset IPs over 100 times)
$stmt = $pdo->query('
    SELECT
        hist_users.userID,
        (
            SUM(hist_users.ipresets) +
            SUM(hist_users_current.ipresets)
        ) AS totalResets
    FROM hist_users
    INNER JOIN hist_users_current
    ON hist_users_current.userID = hist_users.userID
    GROUP BY hist_users.userID
    ORDER BY totalResets DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['totalresets'] < 100) {
        break;
    }
    BadgeManager::award('user', $row['userid'], 39);
}

// Badge 'I need help' (timeplaying >= 14 days = 20160 minutes)
$stmt = $pdo->query('
    SELECT
        hist_users.userID,
        (
            SUM(hist_users.timeplaying) +
            SUM(hist_users_current.timeplaying)
        ) AS totalPlaying
    FROM hist_users
    INNER JOIN hist_users_current
    ON hist_users_current.userID = hist_users.userID
    GROUP BY hist_users.userID
    ORDER BY totalPlaying DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['totalplaying'] < 20160) {
        break;
    }
    BadgeManager::award('user', $row['userid'], 41);
}

// Badges for 1, 2, and 5 years of age
$stmt = $pdo->query('
    SELECT uid, TIMESTAMPDIFF(YEAR, dateJoined, NOW()) > 0 AS age
    FROM users_stats
    WHERE TIMESTAMPDIFF(YEAR, dateJoined, NOW()) > 0
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['age'] == 1) {
        BadgeManager::award('user', $row['uid'], 42);
    } elseif ($row['age'] == 2) {
        BadgeManager::award('user', $row['uid'], 43);
    } elseif ($row['age'] == 5) {
        BadgeManager::award('user', $row['uid'], 44);
    }
}

// Badges 'I haz fame' and 'Powerful member' (reputation over 1M or 10M)
$stmt = $pdo->query('
    SELECT
        hist_users.userID,
        (
            SUM(hist_users.reputation) +
            SUM(hist_users_current.reputation)
        ) AS totalRep
    FROM hist_users
    INNER JOIN hist_users_current
    ON hist_users_current.userID = hist_users.userID
    GROUP BY hist_users.userID
    ORDER BY totalRep DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['totalrep'] < 1000000) {
        break;
    }
    if ($row['totalrep'] < 10000000) {
        BadgeManager::award('user', $row['userid'], 46);
    } else {
        BadgeManager::award('user', $row['userid'], 47);
    }
}

// Badge 'DDoS Master' (ddoscount over 1000)
$stmt = $pdo->query('
    SELECT
        hist_users.userID,
        (
            SUM(hist_users.ddoscount) +
            SUM(hist_users_current.ddoscount)
        ) AS totalDdos
    FROM hist_users
    INNER JOIN hist_users_current
    ON hist_users_current.userID = hist_users.userID
    GROUP BY hist_users.userID
    ORDER BY totalDdos DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['totalddos'] < 1000) {
        break;
    }
    BadgeManager::award('user', $row['userid'], 57);
}

// Badge 'Talker' (send over 100 emails)
$stmt = $pdo->query('
    SELECT
        mails.from,
        COUNT(*) +
        (
            SELECT COUNT(*)
            FROM hist_mails
            WHERE hist_mails.from = mails.from
        ) AS total
    FROM mails
    WHERE
        mails.to > 0 AND
        mails.from > 0
    GROUP BY mails.from
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 100) {
        break;
    }
    BadgeManager::award('user', $row['from'], 63);
}

// Badge 'Famous' (receive over 50 emails)
$stmt = $pdo->query('
    SELECT
        mails.to,
        COUNT(*) +
        (
            SELECT COUNT(*)
            FROM hist_mails
            WHERE hist_mails.to = mails.to
        ) AS total
    FROM mails
    WHERE
        mails.to > 0 AND
        mails.from > 0
    GROUP BY mails.to
    ORDER BY total DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['total'] < 50) {
        break;
    }
    BadgeManager::award('user', $row['to'], 64);
}

// Badge 'software engineer' (researchcount over 500)
$stmt = $pdo->query('
    SELECT
        hist_users.userID,
        (
            SUM(researchCount) +
            (
                SELECT COUNT(*)
                FROM software_research
                WHERE software_research.userID = hist_users.userID
                GROUP BY software_research.userID
            )
        ) AS totalResearch
    FROM hist_users
    GROUP BY hist_users.userID
    ORDER BY totalResearch DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['totalresearch'] < 500) {
        break;
    }
    BadgeManager::award('user', $row['userid'], 66);
}

// Badge 'hacker master' (hackcount over 1000)
$stmt = $pdo->query('
    SELECT
        hist_users.userID,
        (
            SUM(hist_users.hackCount) +
            SUM(hist_users_current.hackCount)
        ) AS totalHack
    FROM hist_users
    INNER JOIN hist_users_current
    ON hist_users_current.userID = hist_users.userID
    GROUP BY hist_users.userID
    ORDER BY totalHack DESC
');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if ($row['totalhack'] < 1000) {
        break;
    }
    BadgeManager::award('user', $row['userid'], 68);
}

$elapsed = round(microtime(true) - $startTime, 4);
echo date('d/m/y H:i:s') . ' - ' . __FILE__ . ' - ' . $elapsed . "s\n";
