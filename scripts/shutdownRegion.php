<?php
require_once dirname(__DIR__) . '/config.php';

require '../connect.php';

$sql = "SELECT region, id FROM storyline_launches WHERE effect = 0 AND status = 3 ORDER BY endTime DESC";
$data = $pdo->query($sql)->fetchAll();

if(count($data) > 0){
    
    $sql = "UPDATE storyline_launches SET effect = '1' WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':id' => $data['0']['id']));
    
    $region = $data['0']['region'];
            
    //disable all npcs
    $sql = "UPDATE npc SET downUntil = '2020-01-01 12:00:00' WHERE region = :region";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':region' => $region));
    
    //disable all bank accounts
    $sql = "SELECT id FROM npc WHERE npcType = 1 AND region = :region";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':region' => $region));
    $data = $stmt;
    
    $bankArr = Array();
    
    $i = 0;
    while($bankInfo = $data->fetch(PDO::FETCH_OBJ)){
        
        $bankArr[$i]['id'] = $bankInfo->id;
        
        $i++;
    }
    
    for($a=0;$a<$i;$a++){
        
        $sql = "DELETE FROM bankAccounts WHERE bankID = :bankID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':bankID' => $bankArr[$a]['id']));
        
    }
    
}

?>
