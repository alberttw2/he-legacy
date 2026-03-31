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
$message = '';

// Handle actions
if (isset($_POST['action'])) {
    $uid = (int)($_POST['uid'] ?? 0);

    switch ($_POST['action']) {
        case 'ban':
            // Set a ban by inserting into users_admin with a negative convention,
            // or we use a simpler approach: delete user's session-related data
            // For simplicity, we'll use a banned_users table concept via a flag approach
            // Since the schema doesn't have a ban column, we'll create a convention:
            // Insert a record into a tracking mechanism. Let's use the admin_reports table
            // or just lock the account by setting password to a known-bad value.
            // Safest: we'll add to a simple lock mechanism via gamePass
            $stmt = $pdo->prepare("UPDATE users SET gamePass = 'BANNED' WHERE id = ?");
            $stmt->execute([$uid]);
            $message = "User #$uid has been banned.";
            break;

        case 'unban':
            // Restore gamePass to a random string
            $newPass = substr(md5(mt_rand()), 0, 8);
            $stmt = $pdo->prepare("UPDATE users SET gamePass = ? WHERE id = ?");
            $stmt->execute([$newPass, $uid]);
            $message = "User #$uid has been unbanned.";
            break;

        case 'grant_staff':
            $stmt = $pdo->prepare("INSERT IGNORE INTO users_admin (userID) VALUES (?)");
            $stmt->execute([$uid]);
            $message = "User #$uid granted staff privileges.";
            break;

        case 'revoke_staff':
            $stmt = $pdo->prepare("DELETE FROM users_admin WHERE userID = ?");
            $stmt->execute([$uid]);
            $message = "User #$uid staff privileges revoked.";
            break;

        case 'edit_money':
            $accId = (int)($_POST['acc_id'] ?? 0);
            $newMoney = (int)($_POST['new_money'] ?? 0);
            if ($accId > 0) {
                $stmt = $pdo->prepare("UPDATE bankAccounts SET cash = ? WHERE id = ? AND bankUser = ?");
                $stmt->execute([$newMoney, $accId, $uid]);
                $message = "Bank account #$accId balance updated to \$$newMoney.";
            }
            $viewUser = $uid;
            $_GET['view'] = $uid;
            break;

        case 'add_money':
            $amount = (int)($_POST['amount'] ?? 0);
            // Find user's first bank account, or create one
            $stmt = $pdo->prepare("SELECT id FROM bankAccounts WHERE bankUser = ? LIMIT 1");
            $stmt->execute([$uid]);
            $acc = $stmt->fetch(PDO::FETCH_OBJ);
            if ($acc) {
                $pdo->prepare("UPDATE bankAccounts SET cash = cash + ? WHERE id = ?")->execute([$amount, $acc->id]);
                $message = "Added \$$amount to user #$uid's bank account.";
            } else {
                $message = "User #$uid has no bank account.";
            }
            $viewUser = $uid;
            $_GET['view'] = $uid;
            break;
    }
}

// Search
$search = $_GET['q'] ?? '';
$viewUser = isset($_GET['view']) ? (int)$_GET['view'] : 0;

// View single user detail
if ($viewUser > 0) {
    $stmt = $pdo->prepare('SELECT u.id, u.login, u.email, u.gamePass, u.lastLogin,
                           INET_NTOA(u.gameIP) AS gameip_str,
                           (SELECT 1 FROM users_admin WHERE userID = u.id) AS is_staff
                           FROM users u WHERE u.id = ?');
    $stmt->execute([$viewUser]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);

    // Get bank accounts
    $stmt = $pdo->prepare('SELECT ba.id, ba.bankAcc, ba.cash, ba.bankPass, ba.bankID,
                           INET_NTOA(n.npcIP) as bankip, ni.name as bankname
                           FROM bankAccounts ba
                           LEFT JOIN npc n ON ba.bankID = n.id
                           LEFT JOIN npc_info_en ni ON ni.npcID = n.id
                           WHERE ba.bankUser = ? ORDER BY ba.id');
    $stmt->execute([$viewUser]);
    $bankAccounts = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Get user stats
    $stmt = $pdo->prepare('SELECT * FROM users_stats WHERE uid = ?');
    $stmt->execute([$viewUser]);
    $userStats = $stmt->fetch(PDO::FETCH_OBJ);
}

// List users
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $pdo->prepare("SELECT id, login, email, gamePass, lastLogin FROM users WHERE login LIKE ? OR email LIKE ? ORDER BY id DESC LIMIT $perPage OFFSET $offset");
    $stmt->execute([$like, $like]);
    $users = $stmt->fetchAll(PDO::FETCH_OBJ);
} else {
    $users = $pdo->query("SELECT id, login, email, gamePass, lastLogin FROM users ORDER BY id DESC LIMIT $perPage OFFSET $offset")->fetchAll(PDO::FETCH_OBJ);
}
?>
<!DOCTYPE html>
<html>
<head><title>Admin - Users</title>
<style>
body { font-family: Arial, sans-serif; background: #1a1a2e; color: #eee; margin: 0; padding: 0; }
.nav { background: #16213e; padding: 10px 20px; display: flex; gap: 20px; align-items: center; }
.nav a { color: #e94560; text-decoration: none; font-weight: bold; }
.nav a:hover { text-decoration: underline; }
.nav .spacer { flex-grow: 1; }
.container { padding: 20px; max-width: 1000px; margin: 0 auto; }
table { width: 100%; border-collapse: collapse; background: #16213e; border-radius: 8px; overflow: hidden; }
th, td { padding: 10px 14px; text-align: left; border-bottom: 1px solid #0f3460; }
th { background: #0f3460; color: #e94560; }
h2 { color: #e94560; }
.search-form { margin-bottom: 15px; }
.search-form input { padding: 8px; background: #0f3460; border: 1px solid #333; color: #eee; border-radius: 4px; width: 250px; }
.search-form button { padding: 8px 16px; background: #e94560; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
.message { background: #0f3460; padding: 10px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #e94560; }
.user-detail { background: #16213e; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
.user-detail p { margin: 5px 0; }
.btn { padding: 4px 10px; border: none; border-radius: 3px; cursor: pointer; color: #fff; font-size: 12px; text-decoration: none; }
.btn-danger { background: #c0392b; }
.btn-success { background: #27ae60; }
.btn-info { background: #2980b9; }
.btn-warn { background: #f39c12; }
.pagination { margin-top: 15px; }
.pagination a { color: #e94560; margin-right: 10px; }
.banned { color: #c0392b; font-weight: bold; }
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
    <h2>User Management</h2>

    <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($viewUser > 0 && $user): ?>
    <div class="user-detail">
        <h3>User Detail: <?= htmlspecialchars($user->login) ?> (#<?= $user->id ?>)</h3>
        <p><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></p>
        <p><strong>Game IP:</strong> <?= $user->gameip_str ?></p>
        <p><strong>Last Login:</strong> <?= $user->lastlogin ?></p>
        <p><strong>Status:</strong> <?= $user->gamepass === 'BANNED' ? '<span class="banned">BANNED</span>' : 'Active' ?></p>
        <p><strong>Staff:</strong> <?= $user->is_staff ? 'Yes' : 'No' ?></p>
        <br>
        <form method="post" style="display:inline">
            <input type="hidden" name="uid" value="<?= $user->id ?>">
            <?php if ($user->gamepass === 'BANNED'): ?>
                <button class="btn btn-success" name="action" value="unban">Unban</button>
            <?php else: ?>
                <button class="btn btn-danger" name="action" value="ban">Ban</button>
            <?php endif; ?>
            <?php if ($user->is_staff): ?>
                <button class="btn btn-warn" name="action" value="revoke_staff">Revoke Staff</button>
            <?php else: ?>
                <button class="btn btn-info" name="action" value="grant_staff">Grant Staff</button>
            <?php endif; ?>
        </form>
        <br><br>

        <!-- Quick add money -->
        <form method="post" style="display:inline">
            <input type="hidden" name="uid" value="<?= $user->id ?>">
            <input type="hidden" name="action" value="add_money">
            <input type="number" name="amount" value="1000" style="width:100px;padding:4px;background:#0f3460;border:1px solid #333;color:#eee;border-radius:3px;">
            <button class="btn btn-success">+ Add Money</button>
        </form>

        <h3 style="margin-top:20px;color:#e94560;">Bank Accounts</h3>
        <?php if (empty($bankAccounts)): ?>
            <p style="color:#888;">No bank accounts.</p>
        <?php else: ?>
        <table>
            <tr><th>ID</th><th>Account #</th><th>Bank</th><th>Balance</th><th>Edit</th></tr>
            <?php foreach ($bankAccounts as $ba): ?>
            <tr>
                <td><?= $ba->id ?></td>
                <td><?= $ba->bankacc ?></td>
                <td><?= htmlspecialchars($ba->bankname ?? 'Unknown') ?> (<?= $ba->bankip ?? '?' ?>)</td>
                <td>$<?= number_format($ba->cash ?? 0) ?></td>
                <td>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="uid" value="<?= $user->id ?>">
                        <input type="hidden" name="action" value="edit_money">
                        <input type="hidden" name="acc_id" value="<?= $ba->id ?>">
                        <input type="number" name="new_money" value="<?= $ba->cash ?? 0 ?>" style="width:100px;padding:3px;background:#0f3460;border:1px solid #333;color:#eee;border-radius:3px;">
                        <button class="btn btn-info">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <?php if ($userStats): ?>
        <h3 style="margin-top:20px;color:#e94560;">Player Stats</h3>
        <table>
            <tr><td><strong>EXP</strong></td><td><?= number_format($userStats->exp ?? 0) ?></td>
                <td><strong>Hack Count</strong></td><td><?= number_format($userStats->hackcount ?? 0) ?></td></tr>
            <tr><td><strong>Money Earned</strong></td><td>$<?= number_format($userStats->moneyearned ?? 0) ?></td>
                <td><strong>Money on Hardware</strong></td><td>$<?= number_format($userStats->moneyhardware ?? 0) ?></td></tr>
            <tr><td><strong>Missions Done</strong></td><td><?= number_format($userStats->missioncount ?? 0) ?></td>
                <td><strong>DDoS Count</strong></td><td><?= number_format($userStats->ddoscount ?? 0) ?></td></tr>
            <tr><td><strong>IP Resets</strong></td><td><?= number_format($userStats->ipresets ?? 0) ?></td>
                <td><strong>Joined</strong></td><td><?= $userStats->datejoined ?? 'N/A' ?></td></tr>
        </table>
        <?php endif; ?>

        <br>
        <a href="users.php<?= $search ? '?q=' . urlencode($search) : '' ?>">&larr; Back to list</a>
    </div>
    <?php endif; ?>

    <div class="search-form">
        <form method="get">
            <input type="text" name="q" placeholder="Search by login or email..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
            <?php if ($search): ?><a href="users.php" style="color:#e94560;margin-left:10px;">Clear</a><?php endif; ?>
        </form>
    </div>

    <table>
        <tr><th>ID</th><th>Login</th><th>Email</th><th>Status</th><th>Last Login</th><th>Actions</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= $u->id ?></td>
            <td><?= htmlspecialchars($u->login) ?></td>
            <td><?= htmlspecialchars($u->email) ?></td>
            <td><?= $u->gamepass === 'BANNED' ? '<span class="banned">BANNED</span>' : 'Active' ?></td>
            <td><?= $u->lastlogin ?></td>
            <td><a class="btn btn-info" href="users.php?view=<?= $u->id ?><?= $search ? '&q=' . urlencode($search) : '' ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?>
        <tr><td colspan="6" style="text-align:center;">No users found.</td></tr>
        <?php endif; ?>
    </table>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="users.php?page=<?= $page - 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>">&laquo; Previous</a>
        <?php endif; ?>
        <?php if (count($users) == $perPage): ?>
            <a href="users.php?page=<?= $page + 1 ?><?= $search ? '&q=' . urlencode($search) : '' ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
