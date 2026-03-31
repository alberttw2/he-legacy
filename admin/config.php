<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';
require_once BASE_PATH . 'classes/GameConfig.class.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = PDO_DB::factory();
$isAdmin = isset($_SESSION['admin_id']);
if (!$isAdmin && isset($_SESSION['id'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM users_admin WHERE userID = ?");
    $chk->execute([$_SESSION['id']]);
    $isAdmin = $chk->fetchColumn() > 0;
}
if (!$isAdmin) { header('Location: index.php'); exit; }

$message = '';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config'])) {
    $grouped = GameConfig::getAllGrouped();
    $allKeys = [];
    foreach ($grouped as $items) {
        foreach ($items as $item) {
            $allKeys[$item['config_key']] = $item['config_value'];
        }
    }
    $changed = 0;
    foreach ($_POST['config'] as $key => $value) {
        if (isset($allKeys[$key]) && $allKeys[$key] !== $value) {
            GameConfig::set($key, $value);
            $changed++;
        }
    }
    $message = $changed > 0 ? "Saved $changed configuration value(s)." : "No changes detected.";
}

$grouped = GameConfig::getAllGrouped();
?>
<!DOCTYPE html>
<html>
<head><title>Game Config - Admin</title>
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
.message { background: #0f3460; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #27ae60; }
input[type="text"] { padding: 6px 10px; background: #0f3460; border: 1px solid #333; color: #eee; border-radius: 4px; width: 300px; }
.save-btn { padding: 10px 30px; background: #e94560; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 15px; margin-top: 10px; }
.save-btn:hover { background: #c0392b; }
.category-name { text-transform: uppercase; letter-spacing: 1px; }
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
    <h2>Game Configuration</h2>

    <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <?php foreach ($grouped as $category => $items): ?>
        <h3 class="category-name"><?= htmlspecialchars($category) ?></h3>
        <table>
            <tr><th>Key</th><th>Value</th><th>Description</th></tr>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['config_key']) ?></td>
                <td><input type="text" name="config[<?= htmlspecialchars($item['config_key']) ?>]" value="<?= htmlspecialchars($item['config_value']) ?>"></td>
                <td><?= htmlspecialchars($item['description']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endforeach; ?>

        <button type="submit" class="save-btn">Save Changes</button>
    </form>
</div>
</body>
</html>
