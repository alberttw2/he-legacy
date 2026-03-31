<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = PDO_DB::factory();
$isAdmin = isset($_SESSION['admin_id']);
if (!$isAdmin && isset($_SESSION['id'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users_admin WHERE userID = ?");
    $chk->execute([$_SESSION['id']]);
    $isAdmin = $chk->fetchColumn() > 0;
}
if (!$isAdmin) { header('Location: index.php'); exit; }
$output = '';

// Handle cron execution
if (isset($_POST['run_cron'])) {
    $allowedCrons = [
        'generateMissions' => 'cron/generateMissions.php',
        'updateRanking' => 'cron/updateRanking.php',
        'rankGenerator' => 'cron/rankGenerator.php',
        'restoreNPC' => 'cron/restoreNPC.php',
        'restoreSoftware' => 'cron/restoreSoftware.php',
        'removeExpiredLogins' => 'cron/removeExpiredLogins.php',
        'updateCurStats' => 'cron/updateCurStats.php',
        'fameGenerator' => 'cron/fameGenerator.php',
    ];

    $cron = $_POST['run_cron'];
    if (isset($allowedCrons[$cron])) {
        $cronPath = BASE_PATH . $allowedCrons[$cron];
        $result = shell_exec('php ' . escapeshellarg($cronPath) . ' 2>&1');
        $output = "Ran $cron:\n" . ($result ?: '(no output)');
    }
}

// Gather stats
$roundInfo = $pdo->query('SELECT id, name, startDate, endDate, status FROM round ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_OBJ);
$npcCount = $pdo->query('SELECT COUNT(*) AS c FROM npc')->fetch(PDO::FETCH_OBJ)->c;
$softwareCount = $pdo->query('SELECT COUNT(*) AS c FROM software_original')->fetch(PDO::FETCH_OBJ)->c;
$missionCount = $pdo->query("SELECT COUNT(*) AS c FROM missions WHERE status = 1")->fetch(PDO::FETCH_OBJ)->c;
$missionTaken = $pdo->query("SELECT COUNT(*) AS c FROM missions WHERE status = 2")->fetch(PDO::FETCH_OBJ)->c;
$seedCount = $pdo->query('SELECT COUNT(*) AS c FROM missions_seed')->fetch(PDO::FETCH_OBJ)->c;
?>
<!DOCTYPE html>
<html>
<head><title>Admin - Game Management</title>
<style>
body { font-family: Arial, sans-serif; background: #1a1a2e; color: #eee; margin: 0; padding: 0; }
.nav { background: #16213e; padding: 10px 20px; display: flex; gap: 20px; align-items: center; }
.nav a { color: #e94560; text-decoration: none; font-weight: bold; }
.nav a:hover { text-decoration: underline; }
.nav .spacer { flex-grow: 1; }
.container { padding: 20px; max-width: 1000px; margin: 0 auto; }
table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #0f3460; }
th { background: #0f3460; color: #e94560; }
h2, h3 { color: #e94560; }
.stats { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
.stat-box { background: #16213e; padding: 20px; border-radius: 8px; flex: 1; min-width: 140px; text-align: center; }
.stat-box .number { font-size: 28px; font-weight: bold; color: #e94560; }
.stat-box .label { font-size: 13px; color: #aaa; margin-top: 5px; }
.round-info { background: #16213e; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
.round-info span { margin-right: 20px; }
.cron-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
.cron-grid form { display: inline; }
.cron-grid button { padding: 8px 16px; background: #0f3460; color: #eee; border: 1px solid #333; border-radius: 4px; cursor: pointer; }
.cron-grid button:hover { background: #e94560; }
.output { background: #0d0d1a; padding: 15px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; margin-bottom: 20px; border: 1px solid #333; }
</style>
</head>
<body>
<div class="nav">
    <a href="index.php">Dashboard</a>
    <a href="users.php">Users</a>
    <a href="game.php">Game</a>
    <a href="economy.php">Economy</a>
    <a href="config.php">Config</a>
    <span class="spacer"></span>
    <span>Logged in as <?= htmlspecialchars($_SESSION['admin_user']) ?></span>
    <a href="index.php?logout=1">Logout</a>
</div>
<div class="container">
    <h2>Game Management</h2>

    <?php if ($roundInfo): ?>
    <div class="round-info">
        <strong>Current Round:</strong>
        <span>ID: <?= $roundInfo->id ?></span>
        <span>Name: <?= htmlspecialchars($roundInfo->name) ?></span>
        <span>Status: <?= $roundInfo->status == 1 ? 'Active' : 'Ended' ?></span>
        <span>Started: <?= $roundInfo->startdate ?></span>
        <?php if ($roundInfo->enddate && $roundInfo->enddate !== '0000-00-00 00:00:00'): ?>
        <span>Ended: <?= $roundInfo->enddate ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-box">
            <div class="number"><?= number_format($npcCount) ?></div>
            <div class="label">NPCs</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($softwareCount) ?></div>
            <div class="label">NPC Software</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($missionCount) ?></div>
            <div class="label">Available Missions</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($missionTaken) ?></div>
            <div class="label">Taken Missions</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($seedCount) ?></div>
            <div class="label">Mission Seeds</div>
        </div>
    </div>

    <h3>Run Cron Jobs</h3>
    <div class="cron-grid">
        <?php
        $crons = [
            'generateMissions' => 'Generate Missions',
            'updateRanking' => 'Update Ranking',
            'rankGenerator' => 'Rank Generator',
            'restoreNPC' => 'Restore NPCs',
            'restoreSoftware' => 'Restore Software',
            'removeExpiredLogins' => 'Remove Expired Logins',
            'updateCurStats' => 'Update Stats',
            'fameGenerator' => 'Fame Generator',
        ];
        foreach ($crons as $key => $label): ?>
        <form method="post">
            <button type="submit" name="run_cron" value="<?= $key ?>"><?= $label ?></button>
        </form>
        <?php endforeach; ?>
    </div>

    <?php if ($output): ?>
    <h3>Cron Output</h3>
    <div class="output"><?= htmlspecialchars($output) ?></div>
    <?php endif; ?>
</div>
</body>
</html>
