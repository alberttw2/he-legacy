<?php
/**
 * Script to add PUZZLE NPCs 29-38 (WHOIS 3 chain) to the database.
 * Run once: php scripts/add_whois3_puzzles.php
 */

require_once dirname(__DIR__) . '/config.php';
require_once BASE_PATH . 'classes/PDO.class.php';

$pdo = PDO_DB::factory();

// Check if these PUZZLE NPCs already exist
$sql = "SELECT COUNT(*) AS total FROM npc_key WHERE `key` LIKE 'PUZZLE/29' OR `key` LIKE 'PUZZLE/30' OR `key` LIKE 'PUZZLE/31' OR `key` LIKE 'PUZZLE/32' OR `key` LIKE 'PUZZLE/33' OR `key` LIKE 'PUZZLE/34' OR `key` LIKE 'PUZZLE/35' OR `key` LIKE 'PUZZLE/36' OR `key` LIKE 'PUZZLE/37' OR `key` LIKE 'PUZZLE/38'";
$existing = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;

if ($existing > 0) {
    echo "WHOIS3 chain PUZZLE NPCs already exist ($existing found). Skipping.\n";
    exit(0);
}

// Puzzle definitions for 29-38
$puzzles = array(
    29 => array(
        'name_en' => 'Puzzle #29 - Domain Resolution',
        'name_pt' => 'Enigma #29 - Resolucao de Dominio',
        'web_en' => 'Welcome to Puzzle #29. In the world of networking, understanding how domain names translate to IP addresses is fundamental. Solve this puzzle to continue your journey.',
        'web_pt' => 'Bem-vindo ao Enigma #29. No mundo das redes, entender como nomes de dominio se traduzem em enderecos IP e fundamental. Resolva este enigma para continuar sua jornada.',
    ),
    30 => array(
        'name_en' => 'Puzzle #30 - Minesweeper',
        'name_pt' => 'Enigma #30 - Campo Minado',
        'web_en' => 'Puzzle #30: Navigate through the minefield carefully. One wrong step and it is game over.',
        'web_pt' => 'Enigma #30: Navegue pelo campo minado com cuidado. Um passo em falso e acabou.',
    ),
    31 => array(
        'name_en' => 'Puzzle #31 - PHP Gotcha',
        'name_pt' => 'Enigma #31 - Pegadinha PHP',
        'web_en' => 'Puzzle #31: Programming languages can be tricky. Do you know the quirks of PHP floating-point arithmetic?',
        'web_pt' => 'Enigma #31: Linguagens de programacao podem ser traicoeiras. Voce conhece as peculiaridades da aritmetica de ponto flutuante do PHP?',
    ),
    32 => array(
        'name_en' => 'Puzzle #32 - Linux Basics',
        'name_pt' => 'Enigma #32 - Basico de Linux',
        'web_en' => 'Puzzle #32: Every hacker should know their way around the command line. How well do you know Linux?',
        'web_pt' => 'Enigma #32: Todo hacker deve saber usar a linha de comando. Quao bem voce conhece o Linux?',
    ),
    33 => array(
        'name_en' => 'Puzzle #33 - Sudoku',
        'name_pt' => 'Enigma #33 - Sudoku',
        'web_en' => 'Puzzle #33: Logic and patience. Complete the Sudoku grid to advance.',
        'web_pt' => 'Enigma #33: Logica e paciencia. Complete o Sudoku para avancar.',
    ),
    34 => array(
        'name_en' => 'Puzzle #34 - Web Security',
        'name_pt' => 'Enigma #34 - Seguranca Web',
        'web_en' => 'Puzzle #34: Understanding vulnerabilities is the first step to defending against them. What do you know about injection attacks?',
        'web_pt' => 'Enigma #34: Entender vulnerabilidades e o primeiro passo para se defender delas. O que voce sabe sobre ataques de injecao?',
    ),
    35 => array(
        'name_en' => 'Puzzle #35 - Hex Convert',
        'name_pt' => 'Enigma #35 - Conversao Hex',
        'web_en' => 'Puzzle #35: Number systems are the foundation of computing. Can you convert between hexadecimal and decimal?',
        'web_pt' => 'Enigma #35: Sistemas numericos sao a base da computacao. Voce consegue converter entre hexadecimal e decimal?',
    ),
    36 => array(
        'name_en' => 'Puzzle #36 - File Permissions',
        'name_pt' => 'Enigma #36 - Permissoes de Arquivo',
        'web_en' => 'Puzzle #36: File permissions are critical for system security. Do you understand the octal permission system?',
        'web_pt' => 'Enigma #36: Permissoes de arquivo sao criticas para a seguranca do sistema. Voce entende o sistema de permissoes octal?',
    ),
    37 => array(
        'name_en' => 'Puzzle #37 - 2048',
        'name_pt' => 'Enigma #37 - 2048',
        'web_en' => 'Puzzle #37: Combine the tiles and reach 2048. Strategy and foresight are key.',
        'web_pt' => 'Enigma #37: Combine as pecas e alcance 2048. Estrategia e previsao sao essenciais.',
    ),
    38 => array(
        'name_en' => 'Puzzle #38 - Network Attack',
        'name_pt' => 'Enigma #38 - Ataque de Rede',
        'web_en' => 'Puzzle #38: The final puzzle before the Third Whois. Do you know the classic network interception attack?',
        'web_pt' => 'Enigma #38: O ultimo enigma antes do Terceiro Whois. Voce conhece o classico ataque de interceptacao de rede?',
    ),
);

// Generate unique IPs for each puzzle NPC
function generateUniqueIP($pdo) {
    do {
        $ip = rand(10, 250) . '.' . rand(1, 254) . '.' . rand(1, 254) . '.' . rand(1, 254);
        $ipLong = ip2long($ip);
        $sql = "SELECT COUNT(*) AS total FROM npc WHERE npcIP = '$ipLong'";
        $count = $pdo->query($sql)->fetch(PDO::FETCH_OBJ)->total;
        $sql2 = "SELECT COUNT(*) AS total FROM users WHERE gameIP = '$ipLong'";
        $count2 = $pdo->query($sql2)->fetch(PDO::FETCH_OBJ)->total;
    } while ($count > 0 || $count2 > 0);
    return $ipLong;
}

$npcType = 7; // PUZZLE type

$pdo->beginTransaction();

try {
    foreach ($puzzles as $num => $entry) {
        $npcIP = generateUniqueIP($pdo);

        // Insert NPC
        $sql = "INSERT INTO npc (npcIP, npcType) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcIP, $npcType));
        $npcID = $pdo->lastInsertId();

        echo "Created NPC #$npcID: {$entry['name_en']} (IP: " . long2ip($npcIP) . ")\n";

        // Insert npc_info_en
        $sql = "INSERT INTO npc_info_en (npcID, name, web) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, $entry['name_en'], $entry['web_en']));

        // Insert npc_info_pt
        $sql = "INSERT INTO npc_info_pt (npcID, name, web) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, $entry['name_pt'], $entry['web_pt']));

        // Insert npc_key
        $key = "PUZZLE/$num";
        $sql = "INSERT INTO npc_key (npcID, `key`) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, $key));

        // Insert hardware
        $sql = "INSERT INTO hardware (userID, isNPC, cpu, hdd, ram, net) VALUES (?, 1, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, 2500, 10000, 1024, 10));

        // Insert log entry
        $sql = "INSERT INTO log (userID, isNPC, text) VALUES (?, 1, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID, 'localhost logged in'));

        // Insert npc_reset
        $sql = "INSERT INTO npc_reset (npcID) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($npcID));

        echo "  -> npc_info, npc_key ($key), hardware, log, npc_reset created.\n";
    }

    $pdo->commit();
    echo "\nDone! 10 WHOIS3 chain PUZZLE NPCs (29-38) created successfully.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
