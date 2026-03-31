<?php
require_once dirname(__DIR__) . '/config.php';
die("ACHO QUE NAO USO ISSO!");
function getExtension($softType) {

    static $extensions = Array(

        '1' => '.crc',
        '2' => '.pec',
        '3' => '.scan',
        '4' => '.fwl',
        '5' => '.hdr',
        '6' => '.skr',
        '7' => '.av',
        '8' => '.vspam',
        '9' => '.vwarez',
        '10' => '.vddos',
        '11' => '.vcol',
        '12' => '.vbrk',
        '13' => '.exp',
        '14' => '.exp',
        '15' => '.nmap',
        '16' => '.ana',
        '17' => '.torrent',
        '29' => '.doom',
        '30' => '.txt',
        '31' => '',
        '50' => '.nsa',        
        '51' => '.emp',
        '9' => '.vdoom',
        '96' => '.vminer',
        '97' => '.vddos',
        '98' => '.vwarez',
        '99' => '.vspam',

    );

    return $extensions[$softType];
        
}

 function dotVersion($softVersion) {

    switch (strlen($softVersion)) {

        case '1':
            $strEdit = '0' . $softVersion;
            break;
        case '2': //1.9
            $strEdit = str_split($softVersion, 1);
            break;
        case '3': //12.0
            $strEdit = str_split($softVersion, 2);
            break;
        case '4': //132.8
            $strEdit = str_split($softVersion, 3);
            break;
        default:
            die("erreeeeeor");
            break;
    }

    $strReturn = $strEdit['0'] . '.' . $strEdit['1'];
    return $strReturn;

 }

require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();

$start = microtime(true);

//TESTES SOMENTE
$pdo->query('DELETE FROM hist_users; DELETE FROM hist_software; DELETE FROM hist_clans');
//TESTES SOMENTE

//cur round
$sql = 'SELECT id FROM round ORDER BY id DESC';
$curRound = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->id;

//INICIO USERS

$sql = 'SELECT users.id, users.login, users_stats.exp, clan_users.clanID, timePlaying, hackCount, ddosCount, ipResets, moneyEarned, moneyTransfered, moneyHardware, moneyResearch,
        TIMESTAMPDIFF(DAY, dateJoined, NOW()) AS age
        FROM users
        INNER JOIN users_stats
        ON users.id = users_stats.uid
        LEFT JOIN clan_users
        ON clan_users.userID = users.id
        ORDER BY users_stats.exp DESC';
$data = $pdo->query($sql)->fetchAll();

$pos = 1;

foreach($data as $_row){ $userInfo = (object)$_row;

    if($userInfo->clanid != NULL){
        $sql = "SELECT name FROM clan WHERE clanID = :clanID";
        $stmtClan = $pdo->prepare($sql);
        $stmtClan->execute(array(':clanID' => $userInfo->clanid));
        $clanName = $stmtClan->fetch(PDO::FETCH_OBJ)->name;
    } else {
        $clanName = '';
    }
    
    //melhorar dps, precisa ser certificado por mim!
    $sql = "SELECT softVersion, softName, softType FROM software WHERE userID = :uid AND isNPC = 0 AND softType < 30 ORDER BY softVersion DESC, softType DESC LIMIT 1";
    $stmtSoft = $pdo->prepare($sql);
    $stmtSoft->execute(array(':uid' => $userInfo->id));
    $softInfo = $stmtSoft->fetchAll();
    
    if(sizeof($softInfo) > 0){
        $bestSoft = $softInfo['0']['softname'].getExtension($softInfo['0']['softtype']);
        $bestSoftVersion = dotVersion($softInfo['0']['softversion']);
    } else {
        $bestSoft = '';
        $bestSoftVersion = '';
    }

    $sql = "INSERT INTO hist_users (id, rank, userID, user, reputation, bestSoft, bestSoftVersion, clanName, round, timePlaying, hackCount, ddosCount, ipResets, moneyEarned, moneyTransfered, moneyHardware, moneyResearch, age)
            VALUES ('', :pos, :uid, :login, :exp, :bestSoft, :bestSoftVersion, :clanName, :curRound, :timeplaying,
                    :hackcount, :ddoscount, :ipresets, :moneyearned, :moneytransfered, :moneyhardware, :moneyresearch, :age)";
    $stmtHist = $pdo->prepare($sql);
    $stmtHist->execute(array(
        ':pos' => $pos, ':uid' => $userInfo->id, ':login' => $userInfo->login, ':exp' => $userInfo->exp,
        ':bestSoft' => $bestSoft, ':bestSoftVersion' => $bestSoftVersion, ':clanName' => $clanName,
        ':curRound' => $curRound, ':timeplaying' => $userInfo->timeplaying, ':hackcount' => $userInfo->hackcount,
        ':ddoscount' => $userInfo->ddoscount, ':ipresets' => $userInfo->ipresets, ':moneyearned' => $userInfo->moneyearned,
        ':moneytransfered' => $userInfo->moneytransfered, ':moneyhardware' => $userInfo->moneyhardware,
        ':moneyresearch' => $userInfo->moneyresearch, ':age' => $userInfo->age
    ));

    $pos++;
    
}

//FIM USERS

//INICIO CLAN

$sql = 'SELECT clan.name, clan.nick, clan.slotsUsed, clan.power, clan_users.userID, users.login
        FROM clan
        INNER JOIN clan_users
        ON clan_users.clanID = clan.clanID
        INNER JOIN users
        ON clan_users.userID = users.id
        WHERE clan_users.authLevel = 4
        ORDER BY clan.power DESC';
$data = $pdo->query($sql)->fetchAll();

$pos = 1;

foreach($data as $_row){ $clanInfo = (object)$_row;
    
    $sql = "INSERT INTO hist_clans (id, rank, name, nick, reputation, owner, ownerID, members, round)
            VALUES ('', :pos, :name, :nick, :power, :owner, :ownerID, :members, :curRound)";
    $stmtClan = $pdo->prepare($sql);
    $stmtClan->execute(array(':pos' => $pos, ':name' => $clanInfo->name, ':nick' => $clanInfo->nick, ':power' => $clanInfo->power, ':owner' => $clanInfo->login, ':ownerID' => $clanInfo->userid, ':members' => $clanInfo->slotsused, ':curRound' => $curRound));
    
    $pos++;
    
}

//FIM CLAN
  
//INICIO SOFTWARE

$sql = 'SELECT software.softName, software.softVersion, software.softType, software.userID, users.login
        FROM software
        INNER JOIN users
        ON users.id = software.userID
        WHERE software.isNPC = 0 AND softType < 29
        ORDER BY softVersion DESC, softType DESC
        LIMIT 100';
$data = $pdo->query($sql)->fetchAll();

$pos = 1;

foreach($data as $_row){ $softInfo = (object)$_row;
    
    $sql = "INSERT INTO hist_software (id, rank, softName, softType, softVersion, owner, ownerID, round)
            VALUES ('', :pos, :softName, :softType, :softVersion, :owner, :ownerID, :curRound)";
    $stmtSoft2 = $pdo->prepare($sql);
    $stmtSoft2->execute(array(':pos' => $pos, ':softName' => $softInfo->softname, ':softType' => $softInfo->softtype, ':softVersion' => $softInfo->softversion, ':owner' => $softInfo->login, ':ownerID' => $softInfo->userid, ':curRound' => $curRound));
    
    $pos++;
    
}

//FIM SOFTWARE

//DELETE ALL SOFTWARES
//DELETE ALL CLANS

echo "<br/><br/>".round(microtime(true) - $start, 4)*1000 .'ms';

?>
