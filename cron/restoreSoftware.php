<?php
require_once dirname(__DIR__) . '/config.php';

//function: restore npc software according to originalsoftware table on mysql

require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();

//talvez dá pra otimizar com um left / right join aqui..

$sql = "SELECT id, npcID, softName, softVersion, softRam, softSize, softType FROM software_original";
$query = $pdo->query($sql);

while($row = $query->fetch(PDO::FETCH_OBJ)){
 
    $newSql = "SELECT id FROM software WHERE userID = :npcid AND isNPC = 1 AND softName = :softname AND softVersion = :softversion";
    $stmtCheck = $pdo->prepare($newSql);
    $stmtCheck->execute(array(':npcid' => $row->npcid, ':softname' => $row->softname, ':softversion' => $row->softversion));
    $newQuery = $stmtCheck->fetchAll();

    if(count($newQuery) == 0){

        $sqlQuery = "INSERT INTO software (id, softHidden, softHiddenWith, softLastEdit, softName, softSize,
            softType, softVersion, userID, isNPC, softRam) VALUES ('', '0', '0', NOW(), ?, ?, ?, ?, ?, '1', ?)";
        $sqlDown = $pdo->prepare($sqlQuery);
        $sqlDown->execute(array($row->softname, $row->softsize, $row->softtype, $row->softversion, $row->npcid, $row->softram));

    }

}

?>
