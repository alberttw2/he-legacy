<?php
require_once dirname(__DIR__) . '/config.php';

// 2019: TODO: What if there's a tie?
//TODO: e se empatar?


$start = microtime(true);

require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();

$sql = 'SELECT COUNT(*) AS total
        FROM clan_war 
        WHERE TIMESTAMPDIFF(SECOND, NOW(), endDate) < 0';
$total = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

if($total > 0){
    
    $sql = 'SELECT 
                w.clanID1, w.clanID2, w.score1, w.score2, w.endDate, w.startDate, w.bounty,
                c1.name AS name1, c2.name AS name2
            FROM clan_war w 
            INNER JOIN clan c1 ON w.clanID1 = c1.clanID
            INNER JOIN clan c2 ON w.clanID2 = c2.clanID
            WHERE TIMESTAMPDIFF(SECOND, NOW(), endDate) < 0';
    $warInfo = $pdo->query($sql)->fetchAll();
    
    for($i=0;$i<sizeof($warInfo);$i++){
        
        $startDate = $warInfo[$i]['startdate'];
        $endDate = $warInfo[$i]['enddate'];
        
        if($warInfo[$i]['score1'] > $warInfo[$i]['score2']){
            
            $winnerID = $warInfo[$i]['clanid1'];
            $loserID = $warInfo[$i]['clanid2'];
            $winnerScore = $warInfo[$i]['score1'];
            $loserScore = $warInfo[$i]['score2'];
            $winnerName = $warInfo[$i]['name1'];
            $loserName = $warInfo[$i]['name2'];
            
        } elseif($warInfo[$i]['score1'] < $warInfo[$i]['score2']) {
            
            $winnerID = $warInfo[$i]['clanid2'];
            $loserID = $warInfo[$i]['clanid1'];
            $winnerScore = $warInfo[$i]['score2'];
            $loserScore = $warInfo[$i]['score1'];
            $winnerName = $warInfo[$i]['name2'];
            $loserName = $warInfo[$i]['name1'];
            
        } else {
            continue;
        }
        
        $sql = "SELECT r.attID, r.power
                FROM round_ddos r
                INNER JOIN clan_ddos d
                ON d.ddosID = r.id
                WHERE 
                    d.attackerClan = '".$winnerID."' AND 
                    d.victimClan = '".$loserID."' AND 
                    TIMESTAMPDIFF(SECOND, r.date, '".$startDate."') < 0";
        $ddosList = $pdo->query($sql)->fetchAll();
        
        $ddoserArr = Array();
        $totalPower = 0;
        
        for($k=0;$k<sizeof($ddosList);$k++){
            
            $total = ceil(sizeof($ddoserArr)/2);

            $invalid = 0;
            for($a=0;$a<$total;$a++){
                
                if($ddoserArr[$a]['userID'] == $ddosList[$k]['attid']){
                    $id = $a;
                    $invalid = 1;
                    break;
                }

            }

            if($invalid == 0){ //nao foi add ainda
                $ddoserArr[$total]['userID'] = $ddosList[$k]['attid'];
                $ddoserArr[$total]['power'] = $ddosList[$k]['power'];
            } else {
                $ddoserArr[$id]['power'] += $ddosList[$k]['power'];
            }
            
            $totalPower += $ddosList[$k]['power'];
            
        }
           
        $bounty = $warInfo[$i]['bounty'];
        $mostInfluent = 0;
        $split = 0;
        
        $earnedArr = Array();
        
        for($k=0;$k<sizeof($ddoserArr);$k++){
            
            $playerInfluence = $ddoserArr[$k]['power']/$totalPower;
            $earned = ceil($bounty * $playerInfluence);
            $earnedArr[$ddoserArr[$k]['userID']] = $earned;
            
            if($playerInfluence > $mostInfluent){
                $mostInfluent = $playerInfluence;
                $mostInfluentID = $ddoserArr[$k]['userID'];
            }
                      
            $sql = "SELECT bankAcc FROM bankAccounts WHERE bankUser = :uid ORDER BY cash ASC LIMIT 1";
            $stmtBank = $pdo->prepare($sql);
            $stmtBank->execute(array(':uid' => $ddoserArr[$k]['userID']));
            $bankacc = $stmtBank->fetch(PDO::FETCH_OBJ)->bankacc;

            $sql = "UPDATE bankAccounts SET cash = cash + :earned WHERE bankAcc = :bankAcc";
            $stmtCash = $pdo->prepare($sql);
            $stmtCash->execute(array(':earned' => $earned, ':bankAcc' => $bankacc));

            $sql = "UPDATE users_stats SET moneyEarned = moneyEarned + :earned WHERE uid = :uid";
            $stmtStats = $pdo->prepare($sql);
            $stmtStats->execute(array(':earned' => $earned, ':uid' => $ddoserArr[$k]['userID']));
            
            $split++;

        }
        

        
        // 2019: Updates related to the end of the clan war
        //ATUALIZAÇÕES RELATIVAS AO FIM DA CLAN WAR \/
        
        $sql = "UPDATE clan
                INNER JOIN clan_stats
                ON clan.clanID = clan_stats.cid
                SET clan_stats.won = clan_stats.won + 1, clan.power = clan.power + :totalPower
                WHERE clan.clanID = :winnerID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':totalPower' => $totalPower, ':winnerID' => $winnerID));
        
        $sql = "UPDATE clan_stats SET lost = lost + 1 WHERE cid = :loserID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':loserID' => $loserID));
        
        $sql = "DELETE FROM clan_war WHERE (clanID1 = :w1 and clanID2 = :l1) OR (clanID2 = :w2 and clanID1 = :l2)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':w1' => $winnerID, ':l1' => $loserID, ':w2' => $winnerID, ':l2' => $loserID));
        
        $sql = "INSERT INTO clan_war_history (id, idWinner, idLoser, scoreWinner, scoreLoser, startDate, endDate, bounty)
                VALUES ('', :winnerID, :loserID, :winnerScore, :loserScore, :startDate, NOW(), :bounty)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':winnerID' => $winnerID, ':loserID' => $loserID, ':winnerScore' => $winnerScore, ':loserScore' => $loserScore, ':startDate' => $startDate, ':bounty' => $bounty));
        $warID = $pdo->lastInsertId();
        
        $sql = "SELECT attackerClan, victimClan, ddosID FROM clan_ddos WHERE (attackerClan = :w1 AND victimClan = :l1) OR (attackerClan = :l2 AND victimClan = :w2)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':w1' => $winnerID, ':l1' => $loserID, ':l2' => $loserID, ':w2' => $winnerID));
        $data2 = $stmt->fetchAll();
        
        if(sizeof($data2) > 0){
            
            for($j=0; $j<sizeof($data2); $j++){
                
                $sql = "INSERT INTO clan_ddos_history (attackerClan, victimClan, ddosID, warID)
                        VALUES (:attClan, :vicClan, :ddosID, :warID)";
                $stmtHist = $pdo->prepare($sql);
                $stmtHist->execute(array(':attClan' => $data2[$j]['attackerclan'], ':vicClan' => $data2[$j]['victimclan'], ':ddosID' => $data2[$j]['ddosid'], ':warID' => $warID));
                
            }
            
        }
        
        // 2019: "Social" updates notifying the end of the war
        //ATUALIZAÇÕES "SOCIAIS" AVISANDO O FIM DA GUERRA \/
        
        $sql = "SELECT login FROM users WHERE id = :uid LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':uid' => $mostInfluentID));
        $playerName = $stmt->fetch(PDO::FETCH_OBJ)->login;
        
        $title = $winnerName.' won clan battle against '.$loserName;
        
        $brief = 'The war against <a href="clan?id='.$winnerID.'">'.$winnerName.'</a> and <a href="clan?id='.$loserID.'">'.$loserName.'</a> reached its end at '.substr($endDate, 0, -3).'<br/>
                The total score was <font color="green"><b>'.number_format($winnerScore,'0', '.', ',').'</b></font> for <a href="clan?id='.$winnerID.'">'.$winnerName.'</a>,
                and <font color="red"><b>'.number_format($loserScore,'0', '.', ',').'</b></font> for <a href="clan?id='.$loserID.'">'.$loserName.'</a>. <br/> The most influent player was 
                <a href="profile?id='.$mostInfluentID.'">'.$playerName.'</a>, who scored alone <b>'.number_format(($winnerScore * $mostInfluent),'0', '.', ',').'</b>
                points. The total bounty for this clan war was <font color="green">$'.number_format($bounty,'0', '.', ',').'</font>, split between '.$split.' players.';
        
        $sql = 'INSERT INTO news (id, author, title, content, date)
                VALUES (\'\', \'-5\', :title, :content, NOW())';
        $data2 = $pdo->prepare($sql);
        $data2->execute(array(':title' => $title, ':content' => $brief));
        
        $newsID = $pdo->lastInsertId();
        
        $sql = "INSERT INTO news_history (newsID, info1, info2)
                VALUES (:newsID, :winnerID, :bounty)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':newsID' => $newsID, ':winnerID' => $winnerID, ':bounty' => $bounty));
        
        $sql = "SELECT r.attID, clan_users.clanID, users.login
                FROM round_ddos r
                INNER JOIN clan_ddos d
                ON d.ddosID = r.id
                INNER JOIN clan_users
                ON r.attID = clan_users.userID
                INNER JOIN users
                ON users.id = r.attID
                WHERE
                    (d.attackerClan = :w1 AND d.victimClan = :l1) OR
                    (d.attackerClan = :l2 AND d.victimClan = :w2)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':w1' => $winnerID, ':l1' => $loserID, ':l2' => $loserID, ':w2' => $winnerID));
        $usersInvolved = $stmt->fetchAll();
        
        $from = -5;
        $type = 1;
        
        $sentArr = Array();
        
        for($k = 0; $k < sizeof($usersInvolved); $k++){

            if(!array_key_exists($usersInvolved[$k]['attid'], $sentArr)){
            
                $to = $usersInvolved[$k]['attid'];

                if($usersInvolved[$k]['clanid'] == $winnerID){
                    
                    $subject = 'We won the clan battle against '.$loserName;
                    $text = 'Yay, '.$usersInvolved[$k]['login'].'! We won the battle against <a href="clan?id='.$loserID.'">'.$loserName.'</a>. You earned <span class="green">$'.number_format($earnedArr[$usersInvolved[$k]['attid']]).'</span>!! 
                            Check our name at <a href="news?id='.$newsID.'">the news</a>.';
                    
                } else {
                    
                    $subject = 'Clan battle lost';
                    $text = 'Hey, '.$usersInvolved[$k]['login'].'. I\'m sad to say that we lost the clan battle against <a href="clan?id='.$winnerID.'">'.$winnerName.'</a>.
                             You can see more information of the battle on <a href="news?id='.$newsID.'">the news</a>.';
                    
                }

                $sentArr[$usersInvolved[$k]['attid']] = 1;
                
                $sql = "INSERT INTO mails (id, mails.from, mails.to, mails.type, subject, text, dateSent) VALUES ('', ?, ?, ?, ?, ?, NOW())";
                $sqlMail = $pdo->prepare($sql);
                $sqlMail->execute(array($from, $to, $type, $subject, $text));
                
                require_once BASE_PATH . 'classes/BadgeManager.class.php';
                BadgeManager::award('user', $to, 60);
                
            }
            
        }
        
        $sql = "DELETE FROM clan_ddos WHERE (attackerClan = :w1 AND victimClan = :l1) OR (attackerClan = :l2 AND victimClan = :w2)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':w1' => $winnerID, ':l1' => $loserID, ':l2' => $loserID, ':w2' => $winnerID));
        
        require_once BASE_PATH . 'classes/BadgeManager.class.php';
        BadgeManager::award('user', $mostInfluentID, 61);
        BadgeManager::award('user', $mostInfluentID, 71);
        
    }
    
}

echo round(microtime(true)-$start,3)*1000 .'ms';
