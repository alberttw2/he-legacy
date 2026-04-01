<?php
/**
 * Run all cron jobs once (useful for development/testing).
 *
 * Usage: php scripts/run-crons.php [job_name]
 *
 * Examples:
 *   php scripts/run-crons.php              # run all
 *   php scripts/run-crons.php ranking      # run only updateRanking
 *   php scripts/run-crons.php list         # list available jobs
 */

require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$jobs = [
    'ranking'       => 'cron/updateRanking.php',
    'stats'         => 'cron/updateCurStats.php',
    'rankgen'       => 'cron/rankGenerator.php',
    'missions'      => 'cron/generateMissions.php',
    'badges'        => 'cron/badgeHunter.php',
    'badges-all'    => 'cron/badgeHunterAll.php',
    'fbi'           => 'cron/fbiUpdate.php',
    'logins'        => 'cron/removeExpiredLogins.php',
    'npc-restore'   => 'cron/restoreNPC.php',
    'npc-av'        => 'cron/antivirusNPC.php',
    'npc-down'      => 'cron/removeDownNPC.php',
    'npc-expire'    => 'cron/removeExpiredNPC.php',
    'html-cache'    => 'cron/removeExpiredHTMLPages.php',
    'premium'       => 'cron/removeExpiredPremium.php',
    'accounts'      => 'cron/removeExpiredAccs.php',
    'doom'          => 'cron/doomUpdater.php',
    'newround'      => 'cron/newRoundUpdater.php',
    'defcon'        => 'cron/defcon2.php',
    'war'           => 'cron/endWar2.php',
    'safenet'       => 'cron/safenetUpdate.php',
    'serverstats'   => 'cron/updateServerStats.php',
    'clan-income'   => 'cron/clanIncome.php',
];

$filter = $argv[1] ?? null;

if ($filter === 'list') {
    echo "Available jobs:\n";
    foreach ($jobs as $name => $file) {
        echo "  $name  →  $file\n";
    }
    exit(0);
}

$toRun = $jobs;
if ($filter && isset($jobs[$filter])) {
    $toRun = [$filter => $jobs[$filter]];
} elseif ($filter) {
    echo "Unknown job: $filter\nUse 'list' to see available jobs.\n";
    exit(1);
}

chdir(BASE_PATH);

foreach ($toRun as $name => $file) {
    $path = BASE_PATH . $file;
    if (!file_exists($path)) {
        echo "SKIP $name ($file not found)\n";
        continue;
    }
    echo "RUN  $name ... ";
    $start = microtime(true);
    try {
        // Run in isolated scope to avoid variable leaks between scripts
        (function() use ($path) {
            require $path;
        })();
        $elapsed = round(microtime(true) - $start, 3);
        echo "OK ({$elapsed}s)\n";
    } catch (Throwable $e) {
        $elapsed = round(microtime(true) - $start, 3);
        echo "ERROR ({$elapsed}s): " . $e->getMessage() . "\n";
    }
}
