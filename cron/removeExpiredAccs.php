<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

$stmt = $pdo->prepare("SELECT accID FROM bankaccounts_expire WHERE TIMESTAMPDIFF(SECOND, NOW(), expireDate) < 0");
$stmt->execute();

$expiredAccs = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

$deleteStmt = $pdo->prepare("DELETE FROM bankAccounts WHERE bankAcc = :accID");

foreach ($expiredAccs as $accID) {
    $deleteStmt->execute([':accID' => $accID]);
}

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
