<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = PDO_DB::factory();

// Allow game staff users (users_admin) to access admin panel without separate login
if (!isset($_SESSION['admin_id']) && isset($_SESSION['id'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users_admin WHERE userID = ?");
    $chk->execute([$_SESSION['id']]);
    if ($chk->fetchColumn() > 0) {
        $_SESSION['admin_id'] = $_SESSION['id'];
        $_SESSION['admin_user'] = 'staff';
    }
}

// Handle login
if (isset($_POST['login'])) {
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $hash = md5($pass);

    $stmt = $pdo->prepare('SELECT id, user FROM admin WHERE user = ? AND password = ? LIMIT 1');
    $stmt->execute([$user, $hash]);
    $admin = $stmt->fetch(PDO::FETCH_OBJ);

    if ($admin) {
        $_SESSION['admin_id'] = $admin->id;
        $_SESSION['admin_user'] = $admin->user;
        $pdo->prepare('UPDATE admin SET lastLogin = NOW() WHERE id = ?')->execute([$admin->id]);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid credentials.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_id'], $_SESSION['admin_user']);
    header('Location: index.php');
    exit;
}

// If not logged in, show login form
if (!isset($_SESSION['admin_id'])) {
?>
<!DOCTYPE html>
<html>
<head><title>Admin Login</title>
<style>
body { font-family: Arial, sans-serif; background: #1a1a2e; color: #eee; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
.login-box { background: #16213e; padding: 30px; border-radius: 8px; width: 300px; }
.login-box h2 { margin-top: 0; text-align: center; }
.login-box input { width: 100%; padding: 8px; margin: 6px 0; box-sizing: border-box; border: 1px solid #333; background: #0f3460; color: #eee; border-radius: 4px; }
.login-box button { width: 100%; padding: 10px; background: #e94560; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
.error { color: #e94560; text-align: center; font-size: 14px; }
</style>
</head>
<body>
<div class="login-box">
    <h2>Admin Panel</h2>
    <?php if (isset($error)): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="post">
        <input type="text" name="user" placeholder="Username" required>
        <input type="password" name="pass" placeholder="Password" required>
        <button type="submit" name="login" value="1">Login</button>
    </form>
</div>
</body>
</html>
<?php
    exit;
}

// Dashboard
$totalUsers = $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch(PDO::FETCH_OBJ)->c;
$onlineUsers = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE lastLogin >= NOW() - INTERVAL 15 MINUTE")->fetch(PDO::FETCH_OBJ)->c;
$totalNPCs = $pdo->query('SELECT COUNT(*) AS c FROM npc')->fetch(PDO::FETCH_OBJ)->c;

$roundInfo = $pdo->query('SELECT id, name, startDate, endDate, status FROM round ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_OBJ);

$recentUsers = $pdo->query('SELECT id, login, email, lastLogin FROM users ORDER BY id DESC LIMIT 10')->fetchAll(PDO::FETCH_OBJ);
?>
<!DOCTYPE html>
<html>
<head><title>Admin Dashboard</title>
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
table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 8px; overflow: hidden; }
th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #0f3460; }
th { background: #0f3460; color: #e94560; }
h2 { color: #e94560; }
.round-info { background: #16213e; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
.round-info span { margin-right: 20px; }
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
    <h2>Dashboard</h2>

    <div class="stats">
        <div class="stat-box">
            <div class="number"><?= number_format($totalUsers) ?></div>
            <div class="label">Total Users</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($onlineUsers) ?></div>
            <div class="label">Online (15m)</div>
        </div>
        <div class="stat-box">
            <div class="number"><?= number_format($totalNPCs) ?></div>
            <div class="label">Total NPCs</div>
        </div>
    </div>

    <?php if ($roundInfo): ?>
    <div class="round-info">
        <strong>Current Round:</strong>
        <span>ID: <?= $roundInfo->id ?></span>
        <span>Name: <?= htmlspecialchars($roundInfo->name) ?></span>
        <span>Status: <?= $roundInfo->status == 1 ? 'Active' : 'Ended' ?></span>
        <span>Started: <?= $roundInfo->startdate ?></span>
    </div>
    <?php endif; ?>

    <h2>Recent Registrations</h2>
    <table>
        <tr><th>ID</th><th>Login</th><th>Email</th><th>Last Login</th></tr>
        <?php foreach ($recentUsers as $u): ?>
        <tr>
            <td><?= $u->id ?></td>
            <td><?= htmlspecialchars($u->login) ?></td>
            <td><?= htmlspecialchars($u->email) ?></td>
            <td><?= $u->lastlogin ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
