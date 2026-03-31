<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';
require_once BASE_PATH . 'classes/BadgeManager.class.php';

$pdo = PDO_DB::factory();

$extensionDict = array(
    '1'  => '.crc',
    '2'  => '.hash',
    '3'  => '.scan',
    '4'  => '.fwl',
    '5'  => '.hdr',
    '6'  => '.skr',
    '7'  => '.av',
    '8'  => '.vspam',
    '9'  => '.vwarez',
    '10' => '.vddos',
    '11' => '.vcol',
    '12' => '.vbrk',
    '13' => '.exp',
    '14' => '.exp',
    '20' => '.vminer',
);

$typeDict = array(
    '1'  => 'Cracker',
    '2'  => 'Hasher',
    '3'  => 'Port Scan',
    '4'  => 'Firewall',
    '5'  => 'Hidder',
    '6'  => 'Seeker',
    '7'  => 'Anti-Virus',
    '8'  => 'Spam Virus',
    '9'  => 'Warez Virus',
    '10' => 'DDoS Virus',
    '11' => 'Virus Collector',
    '12' => 'DDoS Breaker',
    '13' => 'FTP Exploit',
    '14' => 'SSH Exploit',
    '20' => 'BTC Miner',
);

function getExtension($softType) {
    global $extensionDict;
    return isset($extensionDict[(string)$softType]) ? $extensionDict[(string)$softType] : '.todo';
}

function getSoftwareType($softType) {
    global $typeDict;
    return isset($typeDict[(string)$softType]) ? $typeDict[(string)$softType] : 'Unknown';
}

function dotVersion($version) {
    return $version . '.' . ($version % 10);
}

function save($html, $rank, $page) {
    $fameDir = BASE_PATH . 'html/fame/';
    if (!is_dir($fameDir)) {
        mkdir($fameDir, 0755, true);
    }

    if (!is_int($page)) {
        $page = 'preview';
    }

    $path = $fameDir . 'top_' . $rank . '_' . $page . '.html';
    file_put_contents($path, $html);
}

function createRankUsers($pdo, $preview) {
    $html = '';
    $limit = '';
    $i = 0;
    $page = 0;
    $limite = 50;

    if ($preview) {
        $limit = 'LIMIT 10';
    }

    $stmt = $pdo->query('
        SELECT
            hist_users.userID, users.login,
            clan.clanID, clan.name,
            (
                SUM(hist_users.reputation) +
                hist_users_current.reputation
            ) AS totalReputation,
            hist_users.bestSoft, hist_users.bestSoftVersion
        FROM hist_users
        LEFT JOIN users
        ON hist_users.userID = users.id
        LEFT JOIN hist_users_current
        ON hist_users.userID = hist_users_current.userID
        LEFT JOIN clan_users
        ON hist_users.userID = clan_users.userID
        LEFT JOIN clan
        ON clan.clanID = clan_users.clanID
        GROUP BY hist_users.userID
        ORDER BY
            totalReputation DESC,
            hist_users.rank ASC
        ' . $limit . '
    ');

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $userID          = $row['userID'];
        $username        = $row['login'];
        $clanID          = $row['clanID'];
        $clanName        = $row['name'];
        $totalReputation = $row['totalReputation'];
        $bestSoft        = $row['bestSoft'];
        $bestSoftVersion = $row['bestSoftVersion'];

        $software = '';
        $clan = '';

        // Check current round best software
        $stmtSoft = $pdo->prepare('
            SELECT softName, softVersion
            FROM software
            WHERE
                software.id =
                (
                    SELECT softID
                    FROM ranking_software
                    WHERE
                        ranking_software.softID IN
                        (
                            SELECT software.id
                            FROM software
                            WHERE software.userID = :userID
                            ORDER BY softVersion DESC
                        )
                    ORDER BY ranking_software.rank ASC
                    LIMIT 1
                )
            LIMIT 1
        ');
        $stmtSoft->execute(array(':userID' => $userID));

        foreach ($stmtSoft->fetchAll(PDO::FETCH_ASSOC) as $softRow) {
            if ($softRow['softVersion'] > $bestSoftVersion) {
                $software = $softRow['softName'] . ' <span class="green">(' . dotVersion($softRow['softVersion']) . ')</span>';
            } elseif ($bestSoftVersion) {
                $software = $bestSoft . ' <span class="green">(' . dotVersion($bestSoftVersion) . ')</span>';
            }
        }

        if ($clanID) {
            $clan = '<a href="clan?id=' . $clanID . '">' . $clanName . '</a>';
        }

        $pos  = '<center>' . $i . '</center>';
        $user = '<a href="profile?id=' . $userID . '">' . $username . '</a>';
        $power = '<center>' . $totalReputation . '</center>';

        if ($bestSoft) {
            $software = $bestSoft . ' <span class="green">(' . $bestSoftVersion . ')</span>';
        }

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $user . '</td>
                                            <td>' . $power . '</td>
                                            <td>' . $software . '</td>
                                            <td>' . $clan . '</td>
                                        </tr>
';

        if (($i % $limite == 0) && !$preview) {
            save($html, 'user', $page);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        if ($preview) {
            $page = '';
        }
        save($html, 'user', $page);
    }
}

function createRankClans($pdo, $preview) {
    $html = '';
    $limit = '';
    $i = 0;
    $page = 0;
    $limite = 50;

    if ($preview) {
        $limit = 'LIMIT 10';
    }

    $stmt = $pdo->query('
        SELECT
            clan.clanID,
            hist_clans.name, hist_clans.nick, hist_clans_current.members, hist_clans.members,
            (
                SUM(hist_clans.reputation) +
                hist_clans_current.reputation
            ) AS totalReputation,
            (
                SUM(hist_clans.won) +
                hist_clans_current.won
            ) AS totalWon,
            (
                SUM(hist_clans.lost) +
                hist_clans_current.lost
            ) AS totalLost
        FROM hist_clans
        LEFT JOIN hist_clans_current
        ON hist_clans.cid = hist_clans_current.cid
        LEFT JOIN clan
        ON hist_clans.cid = clan.clanID
        GROUP BY hist_clans.cid
        ORDER BY
            totalReputation DESC,
            hist_clans.rank ASC
        ' . $limit . '
    ');

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $clanID      = $row['clanID'];
        $name        = $row['name'];
        $nick        = $row['nick'];
        $membersNow  = $row[3] ?? $row['members'];
        $membersHist = $row[4] ?? $row['members'];
        $totalPower  = $row['totalReputation'];
        $won         = $row['totalWon'];
        $lost        = $row['totalLost'];

        $pos = '<center>' . $i . '</center>';

        if ($membersNow > $membersHist) {
            $members = $membersNow;
        } else {
            $members = $membersHist;
        }

        if (!$won) {
            $won = 0;
        }
        if (!$lost) {
            $lost = 0;
        }

        if ($won == 0 && $lost == 0) {
            $rate = '';
        } else {
            $rate = ' <span class="small">' . intval(round((float)$won / (float)($lost + $won), 2) * 100) . ' %</span>';
        }

        $clanNameHtml = '[' . $nick . '] ' . $name;

        if ($clanID) {
            $clanNameHtml = '<a href="clan?id=' . $clanID . '">' . $clanNameHtml . '</a>';
        }

        $clanPower   = '<center>' . $totalPower . '</center>';
        $clanWL      = '<center><font color="green">' . $won . '</font> / <font color="red">' . $lost . '</font>' . $rate . '</center>';
        $clanMembers = '<center>' . $members . '</center>';

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $clanNameHtml . '</td>
                                            <td>' . $clanPower . '</td>
                                            <td>' . $clanWL . '</td>
                                            <td>' . $clanMembers . '</td>
                                        </tr>
';

        if (($i % $limite == 0) && !$preview) {
            save($html, 'clan', $page);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        if ($preview) {
            $page = '';
        }
        save($html, 'clan', $page);
    }
}

function createRankSoft($pdo, $preview) {
    $html = '';
    $limit = '';
    $i = 0;
    $page = 0;
    $limite = 50;

    if ($preview) {
        $limit = 'LIMIT 10';
    }

    $stmt = $pdo->query('
        SELECT
            hist_software.softName, hist_software.softType, hist_software.softVersion, hist_software.owner, hist_software.ownerID
        FROM hist_software
        UNION ALL
        (
            SELECT
                software.softname, software.softType, software.softVersion, \'0\' AS owner, software.userID AS ownerID
            FROM ranking_software
            INNER JOIN software
            ON ranking_software.softID = software.id
        )
        ORDER BY
            softVersion DESC,
            softType ASC
        ' . $limit . '
    ');

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $name     = $row['softName'];
        $softType = $row['softType'];
        $version  = $row['softVersion'];
        $owner    = $row['owner'];
        $ownerID  = $row['ownerID'];

        $extension = getExtension($softType);

        $pos         = '<center>' . $i . '</center>';
        $softName    = $name . $extension;
        $softVersion = '<center>' . $version . '</center>';
        $softTypeStr = '<a href="?show=software&orderby=' . $extension . '">' . getSoftwareType($softType) . '</a>';

        if ($owner === '0') {
            $ownerName = 'Unknown';
        } else {
            $ownerName = '<a href="profile?id=' . $ownerID . '">' . $owner . '</a>';
        }

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $softName . '</td>
                                            <td>' . $softVersion . '</td>
                                            <td>' . $ownerName . '</td>
                                            <td>' . $softTypeStr . '</td>
                                        </tr>
';

        if (($i % $limite == 0) && !$preview) {
            save($html, 'soft', $page);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        if ($preview) {
            $page = '';
        }
        save($html, 'soft', $page);
    }
}

function createRankDDoS($pdo, $preview) {
    $html = '';
    $limit = '';
    $i = 0;
    $page = 0;
    $limite = 50;

    if ($preview) {
        $limit = 'LIMIT 10';
    }

    $stmt = $pdo->query('
        SELECT
            hist_ddos.power, hist_ddos.attID, hist_ddos.attUser, hist_ddos.vicID, hist_ddos.servers, hist_ddos.vicUser AS victim
        FROM hist_ddos
        UNION ALL
        (
            SELECT
                round_ddos.power, round_ddos.attID, round_ddos.attUser, round_ddos.vicID, round_ddos.servers, round_ddos.vicNPC AS victim
            FROM round_ddos
        )
        ORDER BY
            power DESC,
            servers DESC
        ' . $limit . '
    ');

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $power   = $row['power'];
        $attID   = $row['attID'];
        $attUser = $row['attUser'];
        $vicID   = $row['vicID'];
        $servers = $row['servers'];
        $victim  = $row['victim'];

        $pos     = '<center>' . $i . '</center>';
        $attName = '<a href="profile?id=' . $attID . '">' . $attUser . '</a>';
        $powerHtml   = '<center>' . $power . '</center>';
        $serversHtml = '<center>' . $servers . '</center>';

        if ($victim !== '0') {
            $vicName = '<a href="profile?id=' . $vicID . '">' . $victim . '</a>';
        } else {
            $vicName = 'Unknown';
        }

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $attName . '</td>
                                            <td>' . $vicName . '</td>
                                            <td>' . $powerHtml . '</td>
                                            <td>' . $serversHtml . '</td>
                                        </tr>
';

        if (($i % $limite == 0) && !$preview) {
            save($html, 'ddos', $page);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        if ($preview) {
            $page = '';
        }
        save($html, 'ddos', $page);
    }
}

// Ensure output directory exists
if (!is_dir(BASE_PATH . 'html/fame/')) {
    mkdir(BASE_PATH . 'html/fame/', 0755, true);
}

// Check for preview mode (first argument)
$preview = isset($argv[1]) ? true : false;

createRankUsers($pdo, $preview);
createRankClans($pdo, $preview);
createRankSoft($pdo, $preview);
createRankDDoS($pdo, $preview);
