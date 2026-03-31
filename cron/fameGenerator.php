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

function save($html, $rank, $page, $curRound) {
    $fameDir = BASE_PATH . 'html/fame/';
    if (!is_dir($fameDir)) {
        mkdir($fameDir, 0755, true);
    }

    if (is_int($page)) {
        $string = (string)$page;
    } else {
        $string = 'preview';
    }

    $path = $fameDir . $curRound . '_' . $rank . '_' . $string . '.html';
    file_put_contents($path, $html);
}

function createRankUsers($pdo, $curRound, $preview) {
    $html = '';
    $limit = '';
    $i = 0;
    $page = 0;
    $limite = 50;

    if ($preview) {
        $limit = 'LIMIT 10';
    }

    $stmt = $pdo->prepare('
        SELECT
            hist_users.userID, hist_users.reputation, hist_users.bestSoft, hist_users.bestSoftVersion, hist_users.clanName, clan.clanID,
            users.login, hist_users.rank, hist_users.round
        FROM hist_users
        LEFT JOIN users
        ON hist_users.userID = users.id
        LEFT JOIN clan
        ON clan.name = hist_users.clanName
        WHERE hist_users.round = :curRound
        ORDER BY
            hist_users.round DESC,
            hist_users.rank ASC
        ' . $limit . '
    ');
    $stmt->execute(array(':curRound' => $curRound));

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $userID          = $row['userID'];
        $exp             = $row['reputation'];
        $bestSoft        = $row['bestSoft'];
        $bestSoftVersion = $row['bestSoftVersion'];
        $clanName        = $row['clanName'];
        $clanID          = $row['clanID'];
        $user            = $row['login'];

        $software = '';

        $pos = '<center>' . $i . '</center>';

        if ($clanID) {
            $clanName = '<a href="clan?id=' . $clanID . '">' . $clanName . '</a>';
        }

        $user = '<a href="profile?id=' . $userID . '">' . $user . '</a>';
        $power = '<center>' . $exp . '</center>';

        if ($bestSoft) {
            $software = $bestSoft . ' <span class="green">(' . $bestSoftVersion . ')</span>';
        }

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $user . '</td>
                                            <td>' . $power . '</td>
                                            <td>' . $software . '</td>
                                            <td>' . $clanName . '</td>
                                        </tr>
';

        if (($i % $limite == 0) && !$preview) {
            save($html, 'user', $page, $curRound);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        if ($preview) {
            $page = '';
        }
        save($html, 'user', $page, $curRound);
    }
}

function createRankClans($pdo, $curRound, $preview) {
    $html = '';
    $limit = '';
    $i = 0;
    $page = 0;
    $limite = 50;

    if ($preview) {
        $limit = 'LIMIT 10';
    }

    $stmt = $pdo->prepare('
        SELECT
            clan.clanID, hist_clans.name, hist_clans.nick, hist_clans.reputation, hist_clans.members, hist_clans.won, hist_clans.lost, hist_clans.rate
        FROM hist_clans
        LEFT JOIN clan
        ON hist_clans.cid = clan.clanID
        WHERE hist_clans.round = :curRound
        ORDER BY
            hist_clans.round DESC,
            hist_clans.rank ASC
        ' . $limit . '
    ');
    $stmt->execute(array(':curRound' => $curRound));

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $clanID  = $row['clanID'];
        $name    = $row['name'];
        $nick    = $row['nick'];
        $power   = $row['reputation'];
        $members = $row['members'];
        $won     = $row['won'];
        $lost    = $row['lost'];
        $rate    = $row['rate'];

        $pos = '<center>' . $i . '</center>';

        if ($rate < 0) {
            $rateStr = '';
        } else {
            $rateStr = ' <span class="small">' . intval($rate) . ' %</span>';
        }

        $clanNameHtml = '[' . $nick . '] ' . $name;

        if ($clanID) {
            $clanNameHtml = '<a href="clan?id=' . $clanID . '">' . $clanNameHtml . '</a>';
        }

        $clanPower   = '<center>' . $power . '</center>';
        $clanWL      = '<center><font color="green">' . $won . '</font> / <font color="red">' . $lost . '</font>' . $rateStr . '</center>';
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
            save($html, 'clan', $page, $curRound);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        if ($preview) {
            $page = '';
        }
        save($html, 'clan', $page, $curRound);
    }
}

function createRankSoft($pdo, $curRound, $preview) {
    $html = '';
    $limit = '';
    $i = 0;
    $page = 0;
    $limite = 50;

    if ($preview) {
        $limit = 'LIMIT 10';
    }

    $stmt = $pdo->prepare('
        SELECT
            hist_software.softName, hist_software.softType, hist_software.softVersion, hist_software.owner, hist_software.ownerID
        FROM hist_software
        WHERE
            hist_software.round = :curRound AND
            hist_software.softType != 26
        ORDER BY
            softVersion DESC,
            softType ASC
        ' . $limit . '
    ');
    $stmt->execute(array(':curRound' => $curRound));

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
        $ownerName   = '<a href="profile?id=' . $ownerID . '">' . $owner . '</a>';

        $html .= '                                        <tr>
                                            <td>' . $pos . '</td>
                                            <td>' . $softName . '</td>
                                            <td>' . $softVersion . '</td>
                                            <td>' . $ownerName . '</td>
                                            <td>' . $softTypeStr . '</td>
                                        </tr>
';

        if (($i % $limite == 0) && !$preview) {
            save($html, 'soft', $page, $curRound);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        if ($preview) {
            $page = '';
        }
        save($html, 'soft', $page, $curRound);
    }
}

function createRankDDoS($pdo, $curRound, $preview) {
    $html = '';
    $limit = '';
    $i = 0;
    $page = 0;
    $limite = 50;

    if ($preview) {
        $limit = 'LIMIT 10';
    }

    $stmt = $pdo->prepare('
        SELECT
            hist_ddos.power, hist_ddos.attID, hist_ddos.attUser, hist_ddos.vicID, hist_ddos.servers, hist_ddos.vicUser
        FROM hist_ddos
        WHERE hist_ddos.round = :curRound
        ORDER BY
            power DESC,
            servers DESC
        ' . $limit . '
    ');
    $stmt->execute(array(':curRound' => $curRound));

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $i++;

        $power   = $row['power'];
        $attID   = $row['attID'];
        $attUser = $row['attUser'];
        $vicID   = $row['vicID'];
        $servers = $row['servers'];
        $victim  = $row['vicUser'];

        $pos     = '<center>' . $i . '</center>';
        $attName = '<a href="profile?id=' . $attID . '">' . $attUser . '</a>';
        $vicName = '<a href="profile?id=' . $vicID . '">' . $victim . '</a>';
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

        if (($i % $limite == 0) && !$preview) {
            save($html, 'ddos', $page, $curRound);
            $page++;
            $html = '';
        }
    }

    if ($html != '') {
        if ($preview) {
            $page = '';
        }
        save($html, 'ddos', $page, $curRound);
    }
}

// Ensure output directory exists
if (!is_dir(BASE_PATH . 'html/fame/')) {
    mkdir(BASE_PATH . 'html/fame/', 0755, true);
}

// Get round ID from argv or fallback to latest round
if (isset($argv[1])) {
    $curRound = (string)$argv[1];
} else {
    $stmt = $pdo->query('SELECT id FROM round ORDER BY id DESC LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $curRound = (string)$row['id'];
}

// Check for preview mode (second argument)
$preview = isset($argv[2]) ? true : false;

createRankUsers($pdo, $curRound, $preview);
createRankClans($pdo, $curRound, $preview);
createRankSoft($pdo, $curRound, $preview);
createRankDDoS($pdo, $curRound, $preview);
