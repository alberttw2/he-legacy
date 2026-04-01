<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';
require_once BASE_PATH . 'classes/BadgeManager.class.php';

$startTime = microtime(true);

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
    return intval($version / 10) . '.' . ($version % 10);
}

function save($html, $rank, $page, $preview = false) {
    $rankingDir = BASE_PATH . 'html/ranking/';
    if (!is_dir($rankingDir)) {
        mkdir($rankingDir, 0755, true);
    }

    if ($preview) {
        $path = BASE_PATH . 'html/fame/rank_' . $rank . '_preview.html';
        $fameDir = BASE_PATH . 'html/fame/';
        if (!is_dir($fameDir)) {
            mkdir($fameDir, 0755, true);
        }
    } else {
        $path = $rankingDir . $rank . '_' . $page . '.html';
    }

    file_put_contents($path, $html);
}

function createRankUsers($pdo) {
    $stmt = $pdo->query('
        SELECT ranking_user.userID, clan_users.clanID, users.login, users_premium.id, users_online.id, users_stats.exp, clan.nick, clan.name,
            (
                SELECT COUNT(*)
                FROM lists
                WHERE lists.userID = ranking_user.userID
            ) AS hackedDB
        FROM ranking_user
        INNER JOIN users
         ON ranking_user.userID = users.id
        INNER JOIN users_stats
        ON ranking_user.userID = users_stats.uid
        LEFT JOIN users_online
        ON ranking_user.userID = users_online.id
        LEFT JOIN users_premium
        ON ranking_user.userID = users_premium.id
        LEFT JOIN clan_users
        ON ranking_user.userID = clan_users.userID
        LEFT JOIN clan
        ON clan.clanID = clan_users.clanID
        WHERE ranking_user.rank >= 0
        ORDER BY ranking_user.rank ASC
        LIMIT 5000
    ');

    $html = '';
    $i = 0;
    $page = 0;
    $preview = 0;
    $previewLimit = 10;
    $limite = 100;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $userID   = $row['userid'];
        $clanID   = $row['clanid'];
        $username = $row['login'];
        $premium  = $row[4] ?? $row['id'];
        $online   = $row[4] ?? null;
        $exp      = $row['exp'];
        $clanNick = $row['nick'];
        $clanName = $row['name'];
        $hackCount = $row['hackeddb'];

        $pos = '<center>' . $i . '</center>';

        $clan = $premiumImg = $onlineImg = '';

        if ($clanID) {
            $clan = '<a href="clan?id=' . $clanID . '">[' . $clanNick . '] ' . $clanName . '</a>';
        }

        if ($premium) {
            $premiumImg = '<span class="r-premium"></span>';
        }

        if ($online) {
            $onlineImg = '<span class="r-online"></span>';
        }

        $user = '<a href="profile?id=' . $userID . '">' . $username . '</a>' . $onlineImg . $premiumImg;

        $power = '<center>' . $exp . '</center>';
        $count = '<center>' . $hackCount . '</center>';

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $user . '</td>
                                            <td>' . $power . '</td>
                                            <td>' . $count . '</td>
                                            <td>' . $clan . '</td>
                                        </tr>
';

        if ($page == 0 && ($i % $previewLimit) == 0 && $preview == 0) {
            save($html, 'user', '', true);
            $preview = 1;
        }

        if ($i % $limite == 0) {
            save($html, 'user', $page);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        save($html, 'user', $page);
    }

    if ($preview == 0) {
        save($html, 'user', '', true);
    }
}

function createRankClans($pdo) {
    $stmt = $pdo->query('
        SELECT ranking_clan.clanID, clan.name, clan.nick, clan.slotsUsed, clan.power, clan_war.clanID1, clan_stats.won, clan_stats.lost
        FROM ranking_clan
        INNER JOIN clan
        ON ranking_clan.clanID = clan.clanID
        INNER JOIN clan_stats
        ON clan_stats.cid = clan.clanID
        LEFT JOIN clan_war
        ON (clan_war.clanID1 = clan.clanID OR clan_war.clanID2 = clan.clanID)
        WHERE ranking_clan.rank > 0
        ORDER BY ranking_clan.rank ASC
    ');

    $html = '';
    $i = 0;
    $page = 0;
    $preview = 0;
    $previewLimit = 10;
    $limite = 100;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $clanID  = $row['clanid'];
        $name    = $row['name'];
        $nick    = $row['nick'];
        $members = $row['slotsused'];
        $power   = $row['power'];
        $war     = $row['clanid1'];
        $won     = $row['won'];
        $lost    = $row['lost'];

        $pos = '<center>' . $i . '</center>';

        $label = '';
        if ($war) {
            $label = '<span class="r-war"></span>';
        }

        if ($won == 0 && $lost == 0) {
            $rate = '';
        } else {
            $rate = ' <span class="small">' . intval(round($won / ($lost + $won), 2) * 100) . ' %</span>';
        }

        $clanNameHtml = '<a href="clan?id=' . $clanID . '">[' . $nick . '] ' . $name . $label . '</a>';
        $clanPower    = '<center>' . $power . '</center>';
        $clanWL       = '<center><font color="green">' . $won . '</font> / <font color="red">' . $lost . '</font>' . $rate . '</center>';
        $clanMembers  = '<center>' . $members . '</center>';

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $clanNameHtml . '</td>
                                            <td>' . $clanPower . '</td>
                                            <td>' . $clanWL . '</td>
                                            <td>' . $clanMembers . '</td>
                                        </tr>
';

        if ($page == 0 && ($i % $previewLimit) == 0 && $preview == 0) {
            save($html, 'clan', '', true);
            $preview = 1;
        }

        if ($i % $limite == 0) {
            save($html, 'clan', $page);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        save($html, 'clan', $page);
    }

    if ($preview == 0) {
        save($html, 'clan', '', true);
    }
}

function createRankSoft($pdo) {
    $stmt = $pdo->query('
        SELECT ranking_software.softID, r.softwarename, r.userID, r.softwareType, r.newVersion
        FROM ranking_software
        INNER JOIN software_research r
        ON ranking_software.softID = r.softID
        ORDER BY ranking_software.rank ASC
    ');

    $html = '';
    $htmlPreview = '';
    $i = 0;
    $page = 0;
    $preview = 0;
    $previewLimit = 10;
    $limite = 100;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $softID   = $row['softid'];
        $name     = $row['softwarename'];
        $userID   = $row['userid'];
        $softType = $row['softwaretype'];
        $version  = $row['newversion'];

        $pos = '<center>' . $i . '</center>';

        $extension = getExtension($softType);

        $softName    = $name . $extension;
        $softVersion = '<center>' . dotVersion($version) . '</center>';
        $softTypeStr = '<a href="?show=software&orderby=' . $extension . '">' . getSoftwareType($softType) . '</a>';

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $softName . '</td>
                                            <td>' . $softVersion . '</td>
                                            <td>' . $softTypeStr . '</td>
                                        </tr>
';

        $htmlPreview .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $softName . '</td>
                                            <td>' . $softVersion . '</td>
                                            <td>Unknown</td>
                                            <td>' . $softTypeStr . '</td>
                                        </tr>
';

        if ($page == 0 && ($i % $previewLimit) == 0 && $preview == 0) {
            save($htmlPreview, 'soft', '', true);
            $preview = 1;
        }

        if ($i % $limite == 0) {
            save($html, 'soft', $page);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        save($html, 'soft', $page);
    }

    if ($preview == 0) {
        save($htmlPreview, 'soft', '', true);
    }
}

function createRankDDoS($pdo) {
    $stmt = $pdo->query('
        SELECT round_ddos.attID, users.login AS attUser, round_ddos.vicID, round_ddos.power, round_ddos.servers
        FROM ranking_ddos
        INNER JOIN round_ddos
        ON ranking_ddos.ddosID = round_ddos.id
        INNER JOIN users
        ON users.id = round_ddos.attID
        ORDER BY ranking_ddos.rank ASC
    ');

    $html = '';
    $i = 0;
    $page = 0;
    $preview = 0;
    $previewLimit = 10;
    $limite = 10;

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $attID   = $row['attid'];
        $attUser = $row['attuser'];
        $vicID   = $row['vicid'];
        $power   = $row['power'];
        $servers = $row['servers'];

        $pos     = '<center>' . $i . '</center>';
        $attName = '<a href="profile?id=' . $attID . '">' . $attUser . '</a>';
        $vicName = 'Unknown';
        $powerHtml   = '<center>' . $power . '</center>';
        $serversHtml = '<center>' . $servers . '</center>';

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $attName . '</td>
                                            <td>' . $vicName . '</td>
                                            <td>' . $powerHtml . '</td>
                                            <td>' . $serversHtml . '</td>
                                        </tr>
';

        if ($page == 0 && ($i % $previewLimit) == 0 && $preview == 0) {
            save($html, 'ddos', '', true);
            $preview = 1;
        }

        if ($i % $limite == 0) {
            save($html, 'ddos', $page);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        save($html, 'ddos', $page);
    }

    if ($preview == 0) {
        save($html, 'ddos', '', true);
    }
}

// Ensure output directories exist
if (!is_dir(BASE_PATH . 'html/ranking/')) {
    mkdir(BASE_PATH . 'html/ranking/', 0755, true);
}

createRankUsers($pdo);
createRankClans($pdo);
createRankSoft($pdo);
createRankDDoS($pdo);

$elapsed = round(microtime(true) - $startTime, 4);
echo date('d/m/y H:i:s') . ' - ' . __FILE__ . ' - ' . $elapsed . "s\n";
