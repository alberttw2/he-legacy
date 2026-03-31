<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

$expireInterval = 3600;

$stmt = $pdo->query("
    SELECT userID
    FROM cache_profile
    WHERE TIMESTAMPDIFF(SECOND, expireDate, NOW()) > " . (int)$expireInterval
);

$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($users as $userID) {
    $filePath = BASE_PATH . 'html/profile/' . (int)$userID . '.html';

    if (file_exists($filePath)) {
        @unlink($filePath);
    }

    $del = $pdo->prepare("DELETE FROM cache_profile WHERE userID = ?");
    $del->execute([$userID]);
}

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
