<?php

require 'config.php';
require BASE_PATH . 'classes/Session.class.php';
require BASE_PATH . 'classes/Player.class.php';
require BASE_PATH . 'classes/System.class.php';
require BASE_PATH . 'classes/Ranking.class.php';

$session = new Session();

if($session->issetLogin()){

    $system = new System();
    
    $ranking = new Ranking();
    
    require 'template/contentStart.php';
    
    $all = ' class="active"';
    $cur = '';


    
    $show = 'game';
    $game = ' active';
    $server = $forum = '';
    if($system->issetGet('show')){
        $showInfo = $system->switchGet('show', 'game', 'server', 'forum');
        
        if($showInfo['ISSET_GET'] == 1){
            switch($showInfo['GET_VALUE']){
                case 'game':
                    $show = 'game';
                    break;
                case 'server':
                    $show = 'server';
                    $game = '';
                    $server = ' active';
                    break;
                case 'forum':
                    $show = 'forum';
                    $game = '';
                    $forum = ' active';
                    break;
            }
        }
        
    }
    
    ?>
    
        <div class="span12">
<?php
    if($session->issetMsg()){
        $session->returnMsg();
    }
?>
            <div class="widget-box ">
                <div class="widget-title">
                    <ul class="nav nav-tabs">                                  
                        <li class="link <?php echo $game; ?>"><a href="stats.php"><span class="icon-tab he16-stats"></span>Game Stats</a></li>
                        <li class="link<?php echo $server; ?>"><a href="?show=server"><span class="icon-tab he16-server_stats"></span>Server Stats</a></li>
                        <a href="#"><span class="label label-info"><?php echo _("Help"); ?></span></a>
                    </ul>
                </div>
                <div class="widget-content padding noborder">

    <?php


    
    switch($show){
        case 'game':
                $display = '';
                if($system->issetGet('round')){
                    if($_GET['round'] == 'all'){
                        $display = 'all';
                    }
                }

                $ranking->serverStats_list($display);
            break;
        case 'server':
            $pdo = PDO_DB::factory();

            // Live server metrics
            $onlineUsers = $pdo->query("SELECT COUNT(*) FROM users_online")->fetchColumn();
            $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $totalNPCs = $pdo->query("SELECT COUNT(*) FROM npc")->fetchColumn();
            $activeProcesses = $pdo->query("SELECT COUNT(*) FROM processes WHERE TIMESTAMPDIFF(SECOND, NOW(), pTimeEnd) > 0")->fetchColumn();
            $totalSoftware = $pdo->query("SELECT COUNT(*) FROM software")->fetchColumn();
            $totalRunning = $pdo->query("SELECT COUNT(*) FROM software_running")->fetchColumn();
            $totalMissions = $pdo->query("SELECT COUNT(*) FROM missions WHERE status = 1")->fetchColumn();
            $activeMissions = $pdo->query("SELECT COUNT(*) FROM missions WHERE status IN (2,3)")->fetchColumn();
            $totalClans = $pdo->query("SELECT COUNT(*) FROM clan")->fetchColumn();
            $totalBankMoney = $pdo->query("SELECT COALESCE(SUM(bankMoney), 0) FROM bankAccounts WHERE bankUser > 0")->fetchColumn();
            $totalHacks = $pdo->query("SELECT COALESCE(SUM(hackCount), 0) FROM users_stats")->fetchColumn();
            $totalDDoS = $pdo->query("SELECT COALESCE(SUM(ddosCount), 0) FROM users_stats")->fetchColumn();
            $totalMissionsCompleted = $pdo->query("SELECT COALESCE(SUM(missionCount), 0) FROM users_stats")->fetchColumn();

            // Round info
            $roundInfo = $pdo->query("SELECT id, name, startDate, status FROM round ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_OBJ);

            // Server uptime / PHP version
            $phpVersion = phpversion();
            $dbVersion = $pdo->query("SELECT VERSION()")->fetchColumn();

            // Queries counter
            $queryFile = BASE_PATH . 'status/queries.txt';
            $totalQueries = file_exists($queryFile) ? number_format((int)file_get_contents($queryFile)) : '0';
?>
            <h4><i class="fa fa-server"></i> <?php echo _('Server Information'); ?></h4>
            <table class="table table-cozy table-bordered table-striped">
                <tbody>
                    <tr><td><strong><?php echo _('PHP Version'); ?></strong></td><td><?php echo $phpVersion; ?></td></tr>
                    <tr><td><strong><?php echo _('Database Version'); ?></strong></td><td><?php echo $dbVersion; ?></td></tr>
                    <tr><td><strong><?php echo _('Total Queries Served'); ?></strong></td><td><?php echo $totalQueries; ?></td></tr>
                    <tr><td><strong><?php echo _('Current Round'); ?></strong></td><td>#<?php echo $roundInfo->id; ?> — <?php echo $roundInfo->name; ?> (<?php echo $roundInfo->status == 1 ? '<font color="green">Active</font>' : '<font color="red">Ended</font>'; ?>)</td></tr>
                    <tr><td><strong><?php echo _('Round Started'); ?></strong></td><td><?php echo $roundInfo->startdate; ?></td></tr>
                </tbody>
            </table>

            <h4><i class="fa fa-users"></i> <?php echo _('Player Stats'); ?></h4>
            <table class="table table-cozy table-bordered table-striped">
                <tbody>
                    <tr><td><strong><?php echo _('Online Users'); ?></strong></td><td><font color="green"><strong><?php echo number_format($onlineUsers); ?></strong></font></td></tr>
                    <tr><td><strong><?php echo _('Registered Users'); ?></strong></td><td><?php echo number_format($totalUsers); ?></td></tr>
                    <tr><td><strong><?php echo _('Total Clans'); ?></strong></td><td><?php echo number_format($totalClans); ?></td></tr>
                    <tr><td><strong><?php echo _('Active Processes'); ?></strong></td><td><?php echo number_format($activeProcesses); ?></td></tr>
                </tbody>
            </table>

            <h4><i class="fa fa-globe"></i> <?php echo _('World Stats'); ?></h4>
            <table class="table table-cozy table-bordered table-striped">
                <tbody>
                    <tr><td><strong><?php echo _('Total NPCs'); ?></strong></td><td><?php echo number_format($totalNPCs); ?></td></tr>
                    <tr><td><strong><?php echo _('Total Software'); ?></strong></td><td><?php echo number_format($totalSoftware); ?></td></tr>
                    <tr><td><strong><?php echo _('Software Running'); ?></strong></td><td><?php echo number_format($totalRunning); ?></td></tr>
                    <tr><td><strong><?php echo _('Available Missions'); ?></strong></td><td><?php echo number_format($totalMissions); ?></td></tr>
                    <tr><td><strong><?php echo _('Active Missions'); ?></strong></td><td><?php echo number_format($activeMissions); ?></td></tr>
                </tbody>
            </table>

            <h4><i class="fa fa-bar-chart"></i> <?php echo _('Economy & Activity'); ?></h4>
            <table class="table table-cozy table-bordered table-striped">
                <tbody>
                    <tr><td><strong><?php echo _('Money in Circulation'); ?></strong></td><td><font color="green">$<?php echo number_format($totalBankMoney); ?></font></td></tr>
                    <tr><td><strong><?php echo _('Total Hacks'); ?></strong></td><td><?php echo number_format($totalHacks); ?></td></tr>
                    <tr><td><strong><?php echo _('Total DDoS Attacks'); ?></strong></td><td><?php echo number_format($totalDDoS); ?></td></tr>
                    <tr><td><strong><?php echo _('Missions Completed'); ?></strong></td><td><?php echo number_format($totalMissionsCompleted); ?></td></tr>
                </tbody>
            </table>
<?php
            break;
    }
    

    
?>
            </div>
            <div class="nav nav-tabs" style="clear: both;"></div>
<?php
    
    require 'template/contentEnd.php';

} else {
    header("Location:index.php");
}

?>