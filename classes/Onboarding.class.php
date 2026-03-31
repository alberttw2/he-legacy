<?php
class Onboarding {
    private static $steps = [
        1 => [
            'title' => 'Welcome, Hacker!',
            'text' => 'Welcome to the game! Let\'s get you started. First, navigate to the <strong>Internet</strong> page using the menu on the left.',
            'target' => 'internet',
            'check' => 'visited_internet',
        ],
        2 => [
            'title' => 'Visit the Download Center',
            'text' => 'Great! Now enter this IP address to visit the Download Center: <code>%DC_IP%</code>. You can download software from there.',
            'target' => 'internet',
            'check' => 'visited_dc',
        ],
        3 => [
            'title' => 'Download a Cracker',
            'text' => 'Login to the Download Center (user: <strong>download</strong>, password: <strong>download</strong>), then download the <strong>Basic Cracker</strong>. You\'ll need it to hack servers.',
            'target' => 'internet',
            'check' => 'has_cracker',
        ],
        4 => [
            'title' => 'Visit Your Software',
            'text' => 'Check your <strong>Software</strong> page to see your downloaded programs. Make sure your Cracker is installed and running!',
            'target' => 'software',
            'check' => 'cracker_running',
        ],
        5 => [
            'title' => 'Hack Your First Server!',
            'text' => 'Go to <strong>Internet</strong>, navigate to any server from your Hacked Database, and try to login. Your Cracker will do the rest!',
            'target' => 'internet',
            'check' => 'first_hack',
        ],
        6 => [
            'title' => 'Accept a Mission',
            'text' => 'Now check the <strong>Missions</strong> page. Accept a mission to earn money and experience!',
            'target' => 'missions',
            'check' => 'has_mission',
        ],
    ];

    public static function getCurrentStep($userID) {
        $pdo = PDO_DB::factory();
        $stmt = $pdo->prepare("SELECT step, completedAt FROM users_onboarding WHERE userID = ?");
        $stmt->execute([$userID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['completedat'] !== null) return null; // completed or not started
        return (int)$row['step'];
    }

    public static function getStepInfo($step) {
        if (!isset(self::$steps[$step])) return null;
        $info = self::$steps[$step];
        // Replace DC IP placeholder
        $pdo = PDO_DB::factory();
        $stmt = $pdo->query("SELECT INET_NTOA(npc.npcIP) as ip FROM npc INNER JOIN npc_key ON npc.id = npc_key.npcID WHERE npc_key.key = 'DC' LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $dcIP = $row ? $row['ip'] : '???';
        $info['text'] = str_replace('%DC_IP%', $dcIP, $info['text']);
        return $info;
    }

    public static function checkAndAdvance($userID) {
        $step = self::getCurrentStep($userID);
        if ($step === null) return;

        $pdo = PDO_DB::factory();
        $advanced = false;

        switch($step) {
            case 1: // visited internet
                if (isset($_SESSION['CUR_IP']) || isset($_SESSION['LOGGED_IN'])) $advanced = true;
                break;
            case 2: // visited DC
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM lists l INNER JOIN npc n ON l.ip = n.npcIP INNER JOIN npc_key nk ON n.id = nk.npcID WHERE l.userID = ? AND nk.key = 'DC'");
                $stmt->execute([$userID]);
                if ($stmt->fetchColumn() > 0) $advanced = true;
                break;
            case 3: // has cracker
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM software WHERE userID = ? AND isNPC = 0 AND softType = 1");
                $stmt->execute([$userID]);
                if ($stmt->fetchColumn() > 0) $advanced = true;
                break;
            case 4: // cracker running
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM software_running sr INNER JOIN software s ON sr.softID = s.id WHERE sr.userID = ? AND s.softType = 1 AND sr.isNPC = 0");
                $stmt->execute([$userID]);
                if ($stmt->fetchColumn() > 0) $advanced = true;
                break;
            case 5: // first hack (has a server with user+pass in list)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM lists WHERE userID = ? AND user != '' AND pass != '' AND pass != 'unknown'");
                $stmt->execute([$userID]);
                if ($stmt->fetchColumn() > 0) $advanced = true;
                break;
            case 6: // has mission
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM missions WHERE userID = ? AND status IN (2,3)");
                $stmt->execute([$userID]);
                if ($stmt->fetchColumn() > 0) $advanced = true;
                break;
        }

        if ($advanced) {
            $nextStep = $step + 1;
            if ($nextStep > count(self::$steps)) {
                $stmt = $pdo->prepare("UPDATE users_onboarding SET completedAt = NOW() WHERE userID = ?");
                $stmt->execute([$userID]);
            } else {
                $stmt = $pdo->prepare("UPDATE users_onboarding SET step = ? WHERE userID = ?");
                $stmt->execute([$nextStep, $userID]);
            }
        }
    }

    public static function renderBanner($userID) {
        self::checkAndAdvance($userID);
        $step = self::getCurrentStep($userID);
        if ($step === null) return; // completed

        $info = self::getStepInfo($step);
        if (!$info) return;

        echo '<div class="onboarding-banner" style="background:linear-gradient(135deg,#1a3a1a,#0d260d);border:1px solid #259D1C;border-radius:4px;padding:12px 15px;margin:0 0 15px 0;color:#ccc;">';
        echo '<div style="display:flex;align-items:center;gap:10px;">';
        echo '<span style="font-size:24px;">&#127919;</span>';
        echo '<div style="flex:1;">';
        echo '<strong style="color:#259D1C;">Step '.$step.'/'.count(self::$steps).': '.$info['title'].'</strong><br/>';
        echo '<span style="font-size:13px;">'.$info['text'].'</span>';
        echo '</div>';
        echo '<a href="#" onclick="skipOnboarding();return false;" style="color:#888;font-size:12px;text-decoration:underline;white-space:nowrap;">Skip tutorial</a>';
        echo '</div>';
        echo '</div>';
        echo '<script>function skipOnboarding(){if(!confirm("Skip the tutorial? You cannot undo this."))return;var x=new XMLHttpRequest();x.open("POST","ajax.php",true);x.setRequestHeader("Content-Type","application/x-www-form-urlencoded");x.onload=function(){location.reload();};x.send("func=skipOnboarding");}</script>';
    }

    public static function skip($userID) {
        $pdo = PDO_DB::factory();
        $stmt = $pdo->prepare("UPDATE users_onboarding SET completedAt = NOW() WHERE userID = ?");
        $stmt->execute([$userID]);
    }
}
