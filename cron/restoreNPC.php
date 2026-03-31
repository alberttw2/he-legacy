<?php
require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$start = microtime(true);

$pdo = PDO_DB::factory();

// Phase 1: Delete software that does not belong to the NPC (not in template)
$pdo->exec("
    DELETE software, software_running, software_texts, software_folders
    FROM software
    LEFT JOIN software_original ON (
        software.userID = software_original.npcID AND
        software.softName = software_original.softName AND
        software.softVersion = software_original.softVersion AND
        software.softHidden = 0
    )
    LEFT JOIN software_running ON software_running.softID = software.id
    LEFT JOIN software_texts ON software_texts.id = software.id
    LEFT JOIN software_folders ON software_folders.folderID = software.id
    LEFT JOIN npc_key ON npc_key.npcID = software.userID
    WHERE software_original.id IS NULL
        AND software.isNPC = 1
        AND software.softType < 32
        AND software.softType <> 26
        AND npc_key.npcID IS NOT NULL
");

// Phase 2: Insert software that belongs to the NPC but is missing
$stmt = $pdo->query("
    SELECT software_original.npcID, software_original.softName, software_original.softVersion,
           software_original.softSize, software_original.softRam, software_original.softType,
           software_original.running, software_original.licensedTo
    FROM software
    RIGHT JOIN software_original ON (
        software.userID = software_original.npcID AND
        software.softName = software_original.softName AND
        software.softVersion = software_original.softVersion
    )
    WHERE software.id IS NULL
");

$missing = $stmt->fetchAll(PDO::FETCH_ASSOC);

$insertSoft = $pdo->prepare("
    INSERT INTO software (userID, softName, softVersion, softSize, softRam, softType, licensedTo, isNPC)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
");

foreach ($missing as $row) {
    $size = ($row['softsize'] ?? 0) <= 0 ? 1 : $row['softsize'];
    $insertSoft->execute([
        $row['npcid'],
        $row['softname'],
        $row['softversion'] ?? 0,
        $size,
        $row['softram'],
        $row['softtype'],
        $row['licensedto'] ?? 0
    ]);
}

// Phase 3: Start software that should be running but is not
$stmt = $pdo->query("
    SELECT t.id, t.userID, t.softRam
    FROM (
        SELECT software.id, software.userID, software.softRam
        FROM software
        INNER JOIN software_original ON (
            software.userID = software_original.npcID AND
            software.softName = software_original.softName AND
            software.softVersion = software_original.softVersion
        )
        WHERE software_original.running = 1 AND software.isNPC = 1
    ) t
    LEFT JOIN software_running ON t.id = software_running.softID
    WHERE software_running.softID IS NULL
");

$notRunning = $stmt->fetchAll(PDO::FETCH_ASSOC);

$insertRunning = $pdo->prepare("
    INSERT INTO software_running (softID, userID, ramUsage, isNPC)
    VALUES (?, ?, ?, 1)
");

foreach ($notRunning as $row) {
    $insertRunning->execute([$row['id'], $row['userid'], $row['softram']]);
}

echo date('d/m/y H:i:s') . ' - ' . basename(__FILE__) . ' - ' . round(microtime(true) - $start, 4) . "s\n";
