<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

$stmt = $pdo->query("
    SELECT id, boughtDate, premiumUntil, totalPaid
    FROM users_premium
    WHERE TIMESTAMPDIFF(SECOND, NOW(), premiumUntil) < 0
");

$expiredUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current round for badge insert
$roundStmt = $pdo->query("SELECT id FROM round ORDER BY id DESC LIMIT 1");
$curRound = $roundStmt->fetchColumn();

$insertHistory = $pdo->prepare("
    INSERT INTO premium_history (userID, boughtDate, premiumUntil, paid)
    VALUES (?, ?, ?, ?)
");

$deletePremium = $pdo->prepare("
    DELETE FROM users_premium WHERE id = ? AND premiumUntil = ?
");

$updateWebserver = $pdo->prepare("
    UPDATE internet_webserver SET active = 0 WHERE id = ?
");

$checkBadge = $pdo->prepare("
    SELECT COUNT(*) FROM users_badge WHERE userID = ? AND badgeID = 80 LIMIT 1
");

$insertBadge = $pdo->prepare("
    INSERT INTO users_badge (userID, badgeID, round, dateAdd)
    VALUES (?, 80, ?, NOW())
");

foreach ($expiredUsers as $row) {
    $userID = $row['id'];
    $bought = $row['boughtDate'];
    $premium = $row['premiumUntil'];
    $paid = $row['totalPaid'];

    // Award donator badge (80) if user doesn't already have it
    $checkBadge->execute([$userID]);
    $hasBadge = (int)$checkBadge->fetchColumn();

    if ($hasBadge === 0 && $curRound !== false) {
        $insertBadge->execute([$userID, $curRound]);
    }

    $insertHistory->execute([$userID, $bought, $premium, $paid]);
    $deletePremium->execute([$userID, $premium]);
    $updateWebserver->execute([$userID]);
}

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
