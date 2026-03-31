<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = PDO_DB::factory();

// Allow access from admin panel login OR from staff game users
$isAdmin = isset($_SESSION['admin_id']);
if (!$isAdmin && isset($_SESSION['id'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users_admin WHERE userID = ?");
    $chk->execute([$_SESSION['id']]);
    $isAdmin = $chk->fetchColumn() > 0;
}
if (!$isAdmin) { header('Location: index.php'); exit; }

// Total money in circulation (sum of all bank accounts)
$totalMoney = $pdo->query("SELECT COALESCE(SUM(cash), 0) FROM bankAccounts")->fetchColumn();

// Money distribution (top 10 richest)
$richest = $pdo->query("
    SELECT u.login, SUM(b.cash) as total
    FROM bankAccounts b
    INNER JOIN users u ON b.bankUser = u.id
    GROUP BY b.bankUser
    ORDER BY total DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Total users, active (online in last 24h)
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users_online")->fetchColumn();

// Mission stats
$totalMissions = $pdo->query("SELECT COUNT(*) FROM missions WHERE status = 1")->fetchColumn();
$completedToday = $pdo->query("SELECT COUNT(*) FROM missions_history WHERE date >= CURDATE()")->fetchColumn();
$totalEarned = $pdo->query("SELECT COALESCE(SUM(prize), 0) FROM missions_history WHERE date >= CURDATE()")->fetchColumn();

// Software research stats
$totalResearch = $pdo->query("SELECT COUNT(*) FROM software_research")->fetchColumn();
$topResearched = $pdo->query("
    SELECT softName, softVersion, COUNT(*) as researchers
    FROM software_research
    GROUP BY softName, softVersion
    ORDER BY researchers DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Hack activity
$totalHacks = $pdo->query("SELECT COALESCE(SUM(hackCount), 0) FROM users_stats")->fetchColumn();
$totalDDoS = $pdo->query("SELECT COALESCE(SUM(ddosCount), 0) FROM users_stats")->fetchColumn();

// Hardware economy
$hwSpending = $pdo->query("SELECT COALESCE(SUM(moneyHardware), 0) FROM users_stats")->fetchColumn();
$researchSpending = $pdo->query("SELECT COALESCE(SUM(moneyResearch), 0) FROM users_stats")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head><title>Economy Dashboard - Admin</title>
<style>
body { font-family: Arial, sans-serif; background: #1a1a2e; color: #eee; margin: 0; padding: 0; }
.nav { background: #16213e; padding: 10px 20px; display: flex; gap: 20px; align-items: center; }
.nav a { color: #e94560; text-decoration: none; font-weight: bold; }
.nav a:hover { text-decoration: underline; }
.nav .spacer { flex-grow: 1; }
.container { padding: 20px; max-width: 1000px; margin: 0 auto; }
.stats { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
.stat-box { background: #16213e; padding: 20px; border-radius: 8px; flex: 1; min-width: 150px; text-align: center; }
.stat-box .number { font-size: 28px; font-weight: bold; color: #e94560; }
.stat-box .label { font-size: 13px; color: #aaa; margin-top: 5px; }
table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #0f3460; }
th { background: #0f3460; color: #e94560; }
h2 { color: #e94560; }
.section { margin-bottom: 30px; }
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
    <h2>Economy Dashboard</h2>

    <div class="stats">
        <div class="stat-box">
            <div class="number">$<?= number_format($totalMoney) ?></div>
            <div class="label">Total Money in Circulation</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($activeUsers) ?></div>
            <div class="label">Active Users</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($completedToday) ?></div>
            <div class="label">Missions Completed Today</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($totalHacks) ?></div>
            <div class="label">Total Hacks</div>
        </div>
    </div>

    <div class="section">
        <h2>Top 10 Richest Players</h2>
        <table>
            <tr><th>#</th><th>Player</th><th>Total Money</th></tr>
            <?php if (empty($richest)): ?>
            <tr><td colspan="3" style="text-align:center;color:#888;">No data available</td></tr>
            <?php else: ?>
            <?php foreach ($richest as $i => $r): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($r['login']) ?></td>
                <td>$<?= number_format($r['total']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <div class="section">
        <h2>Top 10 Most Researched Software</h2>
        <table>
            <tr><th>#</th><th>Software</th><th>Version</th><th>Researchers</th></tr>
            <?php if (empty($topResearched)): ?>
            <tr><td colspan="4" style="text-align:center;color:#888;">No data available</td></tr>
            <?php else: ?>
            <?php foreach ($topResearched as $i => $s): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($s['softname'] ?? $s['softName'] ?? '') ?></td>
                <td><?= htmlspecialchars($s['softversion'] ?? $s['softVersion'] ?? '') ?></td>
                <td><?= number_format($s['researchers']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <div class="section">
        <h2>Spending &amp; Activity Stats</h2>
        <div class="stats">
            <div class="stat-box">
                <div class="number">$<?= number_format($hwSpending) ?></div>
                <div class="label">Hardware Spending (All Time)</div>
            </div>
            <div class="stat-box">
                <div class="number">$<?= number_format($researchSpending) ?></div>
                <div class="label">Research Spending (All Time)</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= number_format($totalDDoS) ?></div>
                <div class="label">Total DDoS Attacks</div>
            </div>
        </div>
        <div class="stats">
            <div class="stat-box">
                <div class="number"><?= number_format($totalMissions) ?></div>
                <div class="label">Available Missions</div>
            </div>
            <div class="stat-box">
                <div class="number">$<?= number_format($totalEarned) ?></div>
                <div class="label">Mission Earnings Today</div>
            </div>
            <div class="stat-box">
                <div class="number"><?= number_format($totalResearch) ?></div>
                <div class="label">Active Research Projects</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
