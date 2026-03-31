<?php
/**
 * seed.php - Initialize database with required data for the game to function
 *
 * Usage: php scripts/seed.php
 *
 * This creates:
 * - First game round (status=1, active)
 * - Admin account
 * - NPCs from json/npc.json
 * - NPC software from json/npcsoftware.json and json/riddle_software.json
 * - Initial mission seeds
 * - Required directories (html/profile, html/ranking, html/fame, status)
 */

require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();

echo "=== Hacker Experience Legacy - Database Seed ===\n\n";

// 1. Create required directories
echo "[1/6] Creating directories...\n";
$dirs = ['html/profile', 'html/ranking', 'html/fame', 'status'];
foreach ($dirs as $dir) {
    $path = BASE_PATH . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "  Created: $dir/\n";
    } else {
        echo "  Exists: $dir/\n";
    }
}

// Initialize query counter
$counterFile = BASE_PATH . 'status/queries.txt';
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, '0');
    echo "  Created: status/queries.txt\n";
}

// 2. Create first round
echo "\n[2/6] Creating first game round...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM round");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO round (name, startDate, status) VALUES ('Round 1', NOW(), 1)");
    echo "  Round 1 created (active)\n";

    // Create round_stats entry (all counters default to 0)
    $roundId = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO round_stats (id, totalUsers, activeUsers, warezSent, spamSent, bitcoinSent, mailSent, ddosCount, hackCount, clans, timePlaying, totalListed, totalVirus, totalMoney, researchCount, moneyResearch, moneyHardware, moneyEarned, moneyTransfered, usersClicks, missionCount, totalConnections, totalTasks, totalSoftware, totalRunning, totalServers, clansWar, clansMembers, clansClicks, onlineUsers) VALUES ($roundId, 0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0)");
    echo "  Round stats initialized\n";
} else {
    echo "  Rounds already exist, skipping\n";
}

// 3. Create admin account (both in admin table and as a game player)
echo "\n[3/6] Creating admin account...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM admin");
if ($stmt->fetchColumn() == 0) {
    // Admin table uses varchar(36) — fits MD5 (legacy format)
    $adminPass = 'admin123'; // Change this!
    $hash = md5($adminPass);

    $stmt = $pdo->prepare("INSERT INTO admin (user, email, password, lastLogin) VALUES (?, ?, ?, NOW())");
    $stmt->execute(['admin', 'admin@localhost', $hash]);
    echo "  Admin panel account created\n";
} else {
    echo "  Admin panel account already exists, skipping\n";
}
// Also create admin as a game player so they can log into the game
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login = ?");
$stmt->execute(['admin']);
if ($stmt->fetchColumn() == 0) {
    require_once BASE_PATH . 'classes/UserCreator.class.php';
    require_once BASE_PATH . 'classes/BCrypt.class.php';
    require_once BASE_PATH . 'classes/Session.class.php';

    $bcrypt = new BCrypt();
    $adminHash = $bcrypt->hash('admin123');
    $adminIP = rand(1,254) . '.' . rand(0,255) . '.' . rand(0,255) . '.' . rand(1,254);

    $creator = new UserCreator();
    $creator->create('admin', $adminHash, 'admin@localhost', $adminIP);
    echo "  Admin game player created (user: admin, pass: admin123)\n";
    echo "  WARNING: Change the admin password immediately!\n";
} else {
    echo "  Admin game player already exists, skipping\n";
}

// 4. Create first player account (for testing)
echo "\n[4/6] Creating test player account...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
if ($stmt->fetchColumn() == 0) {
    require_once BASE_PATH . 'classes/UserCreator.class.php';
    require_once BASE_PATH . 'classes/BCrypt.class.php';
    require_once BASE_PATH . 'classes/Session.class.php';

    $bcrypt = new BCrypt();
    $testPass = 'test123';
    $hash = $bcrypt->hash($testPass);
    $testIP = rand(1,254) . '.' . rand(0,255) . '.' . rand(0,255) . '.' . rand(1,254);

    $creator = new UserCreator();
    $userId = $creator->create('testplayer', $hash, 'test@localhost', $testIP);

    if ($userId) {
        echo "  Test player created (user: testplayer, pass: test123, IP: $testIP)\n";
        echo "  WARNING: Change or remove this account for production!\n";
    } else {
        echo "  Failed to create test player (check errors above)\n";
    }
} else {
    echo "  Players already exist, skipping\n";
}

// 5. Generate NPCs
echo "\n[5/6] Generating NPCs...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM npc");
if ($stmt->fetchColumn() == 0) {
    require_once BASE_PATH . 'classes/NPCGenerator.class.php';
    $npcGen = new NPCGenerator();
    $npcGen->generate();

    $count = $pdo->query("SELECT COUNT(*) FROM npc")->fetchColumn();
    echo "  $count NPCs generated\n";

    $softCount = $pdo->query("SELECT COUNT(*) FROM software_original")->fetchColumn();
    echo "  $softCount software entries created\n";
} else {
    echo "  NPCs already exist, skipping\n";
}

// 6. Seed missions
echo "\n[6/6] Seeding missions...\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM missions_seed");
if ($stmt->fetchColumn() == 0) {
    // Mission seeds define the template for random mission generation
    // Based on the mission types defined in info/mission.txt
    $seeds = [
        // missionID, greeting, intro, victim_call, payment, victim_location, warning, action
        [1, 1, 1, 1, 1, 1, 0, 0],
        [2, 1, 1, 1, 1, 1, 0, 0],
        [3, 1, 1, 1, 1, 1, 0, 0],
        [4, 1, 1, 1, 1, 1, 1, 0],
        [5, 1, 1, 1, 1, 1, 1, 0],
        [6, 1, 1, 1, 1, 1, 1, 1],
        [7, 1, 1, 1, 1, 1, 1, 1],
        [8, 1, 1, 1, 1, 1, 1, 1],
    ];

    $stmt = $pdo->prepare("INSERT INTO missions_seed (missionID, greeting, intro, victim_call, payment, victim_location, warning, action) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($seeds as $seed) {
        $stmt->execute($seed);
    }
    echo "  " . count($seeds) . " mission seeds created\n";
} else {
    echo "  Mission seeds already exist, skipping\n";
}

echo "\n=== Seed complete! ===\n";
echo "\nYou can now:\n";
echo "  1. Start the server:  php -S 0.0.0.0:8080 -t " . BASE_PATH . "\n";
echo "  2. Log in as:         testplayer / test123\n";
echo "  3. Admin panel:       admin / admin123\n";
echo "\nRemember to change default passwords for production!\n";
