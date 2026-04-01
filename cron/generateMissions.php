<?php
require_once dirname(__DIR__) . '/config.php';

if(php_sapi_name() != 'cli') exit();
function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'){

    $str = '';
    $count = strlen($charset);
    while ($length--) {
        $str .= $charset[mt_rand(0, $count-1)];
    }
    return $str;

}

require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();

$sql = 'SELECT status FROM round ORDER BY id DESC LIMIT 1';
$curStatus = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->status;

if($curStatus != 1){
    exit("Not running scripts on finished round");
}

$pdo->query('DELETE FROM missions WHERE status = 1');

function generateMissions($level, $total, $pdo){

    switch($level){
        case 1:
            $npcType = 71;
            $multiplier = 1;
            break;
        case 2:
            $npcType = 72;
            $multiplier = 1.1;
            break;
        case 3:
            $npcType = 73;
            $multiplier = 1.2;
            break;
    }
    
    //pega o total de missoes disponiveis por ora
    $sqlQuery = "   SELECT missions.id 
                    FROM missions
                    WHERE missions.status = 1 AND level = '".$level."'";
    $tmp = $pdo->query($sqlQuery)->fetchAll();
    $availableMissions = count($tmp);
    
    //coloca todos os npcs contratantes num array
    $sqlQuery = "   SELECT id, npcIP 
                    FROM npc 
                    WHERE npcType = ".$npcType;
    $tmp = $pdo->query($sqlQuery);
    $npcArr = Array();
    $count=0;
    while($npcInfo = $tmp->fetch(PDO::FETCH_OBJ)){
        $npcArr['id'][$count] = $npcInfo->id;
        $npcArr['ip'][$count] = $npcInfo->npcip;
        $count++;
    }
    if($count == '0'){
        die("ERROR: SEM REGISTROS");
    }

    $i = $availableMissions;
    $k = 0;

    while($i < $total){

        if(($k < $count) && ($count < $total)){
            $hirer = $npcArr['ip'][$k];
            $hirerID = $k;
            $k++;
        } else {
            $rand = rand(0,$count-1);
            $hirer = $npcArr['ip'][$rand];
            $hirerID = $rand;
        }

        do {
            $rand = rand(0, $count-1);
        } while($rand == $hirerID);

        $victim = $npcArr['ip'][$rand];
        $victimID = $rand;

        $type = rand(1,100);

        if($type <= 25){
            $type = 1;
        } elseif($type <= 48){
            $type = 2;
        } elseif($type <= 65){
            $type = 3;
        } elseif($type <= 77){
            $type = 4;
        } elseif($type <= 84){
            $type = 5;
        } elseif($type <= 92){
            $type = 6;
        } elseif($type <= 97){
            $type = 7;
        } else {
            $type = 8;
        }

        switch($type){

            case 1:
                $prize = rand(150,350);
                break;
            case 2:
                $prize = rand(250, 450);
                break;
            case 3:
                $prize = rand(500, 750);
                break;
            case 4:
                $prize = rand(1000, 1500);
                break;
            case 5:
                $prize = rand(3000,5000);
                break;
            case 6:
                $prize = rand(200, 400);
                break;
            case 7:
                $prize = rand(400, 700);
                break;
            case 8:
                $prize = rand(500, 1000);
                break;

        }
        
        if($level == 2){
            $prize *= 1.25;
        } elseif($level == 3){
            $prize *= 1.5;
        }

        $info = '';
        $newInfo = '';
        $info2 = '';
        $newInfo2 = '';
        
        switch($type){

            case 1:
            case 2:
                $id = $npcArr['id'][$victimID];
                $sqlQuery = "SELECT id FROM software_original WHERE npcID = $id AND softType < 7";
                $tmp = $pdo->query($sqlQuery);

                $softArr = Array();
                $softCount = 0;
                while($softInfo = $tmp->fetch(PDO::FETCH_OBJ)){
                    $softArr['id'][$softCount] = $softInfo->id;
                    $softCount++;
                }
                $rand = rand(0,$softCount-1);
                $info = $softArr['id'][$rand]; //softid final

                break;
            case 3:
            case 4:

                if($level == 1){
                    $sqlQuery = "SELECT npc.id, npc.npcIP FROM npc LEFT JOIN npc_key ON npc_key.npcID = npc.id WHERE npc_key.key = 'BANK/1' OR npc_key.key = 'BANK/2'";
                } elseif($level == 2){
                    $sqlQuery = "SELECT npc.id, npc.npcIP FROM npc LEFT JOIN npc_key ON npc_key.npcID = npc.id WHERE npc_key.key = 'BANK/1' OR npc_key.key = 'BANK/2' OR npc_key.key = 'BANK/3' OR npc_key.key = 'BANK/4'";
                } else {
                    $sqlQuery = "SELECT npc.id, npc.npcIP FROM npc WHERE npcType = 1";
                }
                $tmp = $pdo->query($sqlQuery);
                $bankArr = Array();
                $totalBank=0;
                while($npcInfo = $tmp->fetch(PDO::FETCH_OBJ)){
                    $bankArr['id'][$totalBank] = $npcInfo->id;
                    $bankArr['ip'][$totalBank] = $npcInfo->npcip;
                    $totalBank++;
                }

                $rand = rand(0, $totalBank-1);
                $victim = $bankArr['ip'][$rand];
                $victimID = $bankArr['id'][$rand];

                do {

                    $bankAcc = rand(111111111, 999999999);

                    $sqlBankSearch = "SELECT id FROM bankAccounts WHERE bankAcc = $bankAcc";
                    $info = $pdo->query($sqlBankSearch)->fetchAll();

                } while(count($info) != 0);

                $info = $bankAcc;
                $newInfo = rand(100,1400);
                

                $sql = "INSERT INTO bankAccounts (id, bankAcc, bankPass, bankID, bankUser, cash, dateCreated) VALUES ('', ?, ?, ?, ?, ?, NOW())";
                $sqlReg = $pdo->prepare($sql);
                $sqlReg->execute(array($info, randString(6), $victimID, '0', $newInfo));

                if($type == '4'){

                    do {

                        $bankAcc = rand(111111, 999999);

                        $sqlBankSearch = "SELECT id FROM bankAccounts WHERE bankAcc = $bankAcc";
                        $tmp = $pdo->query($sqlBankSearch)->fetchAll();

                    } while(count($tmp) != 0);

                    $rand = rand(0, $totalBank-1);
                    $transferID = $bankArr['id'][$rand];
                    $cash = rand(0, 2500);

                    $sql = "INSERT INTO bankAccounts (id, bankAcc, bankPass, bankID, bankUser, cash, dateCreated) VALUES ('', ?, ?, ?, ?, ?, NOW())";
                    $sqlReg = $pdo->prepare($sql);
                    $sqlReg->execute(array($bankAcc, randString(6), $transferID, '0', $cash));

                    $info2 = $bankAcc;
                    $newInfo2 = $bankArr['ip'][$rand];

                }

                break;
            case 6: //spy - collect intel
                $id = $npcArr['id'][$victimID];
                $sqlQuery = "SELECT userID FROM log WHERE isNPC = 1 AND userID = $id LIMIT 1";
                $tmp = $pdo->query($sqlQuery)->fetchAll();
                if(count($tmp) > 0){
                    $info = $tmp[0]['userid'];
                } else {
                    $info = 0;
                }
                break;
            case 7: //upload virus
                $info = 0;
                break;
            case 8: //research race
                $softTypes = array(1, 2, 3, 4, 5, 6);
                $info = $softTypes[array_rand($softTypes)];
                $newInfo = rand(20, 80);
                break;
            case 5: //ddos

                $sql = "SELECT npc.npcIP
                        FROM hardware
                        INNER JOIN npc
                        ON hardware.userID = npc.id
                        WHERE 
                            (
                                hardware.cpu <> '500' OR
                                hardware.hdd <> '1000' OR
                                hardware.ram <> '256' OR
                                hardware.net <> '1'
                            ) AND
                            hardware.isNPC = '1' AND 
                            npc.npcType = '4' AND 
                            npc.npcIP <> '".$hirer."'
                        LIMIT 1";
                $query = $pdo->query($sql)->fetchAll();

                if(count($query) > '0'){

                    $victim = $query['0']['npcip'];

                }

                break;

        }

        $prize *= $multiplier;
        
        //finally, adiciona
        $sqlQuery = "INSERT INTO missions (id, type, status, hirer, victim, info, newInfo, info2, newInfo2, prize, level) VALUES ('', ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)";
        $sqlReg = $pdo->prepare($sqlQuery);
        $sqlReg->execute(array($type, $hirer, $victim, $info, $newInfo, $info2, $newInfo2, $prize, $level));

        // Pre-generate mission seed so it's ready when a player accepts the mission
        $missionID = $pdo->lastInsertId();
        $missionType = $type;

        $limitArr = Array(3, 3, 4, 3, 3, 3, 0);
        switch($missionType){
            case 1:
            case 2:
                $limitArr[6] = 3;
                break;
            case 3:
                $limitArr[2] = 2;
                $limitArr[5] = 2;
                break;
            case 4:
                $limitArr[2] = 2;
                $limitArr[5] = 2;
                break;
            case 5:
                $limitArr[2] = 2;
                break;
            case 6:
                $limitArr[2] = 2;
                break;
            case 7:
                $limitArr[2] = 2;
                break;
            case 8:
                $limitArr[2] = 2;
                break;
        }

        $seedArr = Array();
        for($s = 0; $s < count($limitArr); $s++){
            $seedArr[$s] = rand(1, $limitArr[$s]);
        }

        $sqlSeed = "INSERT INTO missions_seed (missionID, greeting, intro, victim_call, payment, victim_location, warning, action) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtSeed = $pdo->prepare($sqlSeed);
        $stmtSeed->execute(array($missionID, $seedArr[0], $seedArr[1], $seedArr[2], $seedArr[3], $seedArr[4], $seedArr[5], $seedArr[6]));

        $i++;

    }

}

generateMissions(1, 50, $pdo);
generateMissions(2, 30, $pdo);
generateMissions(3, 25, $pdo);

?>
