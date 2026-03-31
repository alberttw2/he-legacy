<?php

class NPCGenerator {

    private $pdo;
    private $npcList;

    public function __construct() {
        $this->pdo = PDO_DB::factory();
        $this->loadJSON();
    }

    private function loadJSON() {
        $jsonPath = dirname(__DIR__) . '/json/npc.json';
        $jsonData = file_get_contents($jsonPath);
        $this->npcList = json_decode($jsonData, true);
    }

    private function ipGenerator() {
        return rand(0, 254) . '.' . rand(0, 254) . '.' . rand(0, 254) . '.' . rand(1, 254);
    }

    private function pwdGenerator($size = 8) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $size; $i++) {
            $password .= $chars[rand(0, $max)];
        }
        return $password;
    }

    private function emptyDB() {
        $this->pdo->exec("
            DELETE npc, hardware, software, log
            FROM npc
            LEFT JOIN hardware
            ON
                hardware.userID = npc.id AND
                hardware.isNPC = 1
            LEFT JOIN software
            ON
                software.userID = npc.id AND
                software.isNPC = 1
            LEFT JOIN log
            ON
                log.userID = npc.id AND
                log.isNPC = 1
            WHERE
                npc.npcType != 80
        ");

        $this->pdo->exec("DELETE FROM software_original");
        $this->pdo->exec("DELETE FROM software_running");
        $this->pdo->exec("DELETE FROM npc_key");
        $this->pdo->exec("DELETE FROM npc_info_en");
        $this->pdo->exec("DELETE FROM npc_info_pt");
        $this->pdo->exec("DELETE FROM npc_reset");
    }

    private function add($npcType, $npcInfo, $key) {
        try {
            $this->pdo->beginTransaction();

            // Add to npc
            $npcIP = isset($npcInfo['ip']) ? $npcInfo['ip'] : $this->ipGenerator();

            $stmt = $this->pdo->prepare("
                INSERT INTO npc
                    (npcType, npcIP, npcPass)
                VALUES
                    (:npcType, INET_ATON(:npcIP), :npcPass)
            ");
            $stmt->execute([
                ':npcType' => $npcType,
                ':npcIP' => $npcIP,
                ':npcPass' => $this->pwdGenerator(),
            ]);

            $npcID = $this->pdo->lastInsertId();

            // Add to npc_info_lang
            foreach ($npcInfo['name'] as $language => $npcName) {
                $npcWeb = $npcInfo['web'][$language];
                $table = 'npc_info_' . $language;

                $stmt = $this->pdo->prepare("
                    INSERT INTO {$table}
                        (npcID, name, web)
                    VALUES
                        (:npcID, :name, :web)
                ");
                $stmt->execute([
                    ':npcID' => $npcID,
                    ':name' => $npcName,
                    ':web' => $npcWeb,
                ]);
            }

            // Add to npc_key
            $stmt = $this->pdo->prepare("
                INSERT INTO npc_key
                    (npcID, `key`)
                VALUES
                    (:npcID, :key)
            ");
            $stmt->execute([
                ':npcID' => $npcID,
                ':key' => $key,
            ]);

            // Add to hardware
            $cpu = $npcInfo['hardware']['cpu'];
            $hdd = $npcInfo['hardware']['hdd'];
            $ram = $npcInfo['hardware']['ram'];
            $net = $npcInfo['hardware']['net'];

            $stmt = $this->pdo->prepare("
                INSERT INTO hardware
                    (userID, name, cpu, hdd, ram, net, isNPC)
                VALUES
                    (:userID, '', :cpu, :hdd, :ram, :net, '1')
            ");
            $stmt->execute([
                ':userID' => $npcID,
                ':cpu' => $cpu,
                ':hdd' => $hdd,
                ':ram' => $ram,
                ':net' => $net,
            ]);

            // Add log
            $stmt = $this->pdo->prepare("
                INSERT INTO log
                    (userID, isNPC)
                VALUES
                    (:userID, 1)
            ");
            $stmt->execute([':userID' => $npcID]);

            // Add npc_reset with random scan interval
            $nextScan = rand(1, 50);
            $stmt = $this->pdo->prepare("
                INSERT INTO npc_reset
                    (npcID, nextScan)
                VALUES
                    (:npcID, DATE_ADD(NOW(), INTERVAL :nextScan HOUR))
            ");
            $stmt->execute([
                ':npcID' => $npcID,
                ':nextScan' => $nextScan,
            ]);

            $this->pdo->commit();
        } catch (Exception $e) {
            error_log("NPCGenerator error for key=$key: " . $e->getMessage());
            $this->pdo->rollBack();
        }
    }

    public function generate() {
        $this->emptyDB();

        foreach ($this->npcList as $npcType => $entry) {
            // Direct NPCs with hardware: MD, FBI, NSA, ISP, EVILCORP, SAFENET, DC, etc.
            if (isset($entry['hardware'])) {
                $this->add($entry['type'], $entry, $npcType);
                continue;
            }

            // Grouped NPCs with type: WHOIS, BANK, NPC, PUZZLE
            if (isset($entry['type'])) {
                $numType = $entry['type'];
                foreach ($entry as $key => $subEntry) {
                    if ($key !== 'type') {
                        $this->add($numType, $subEntry, $npcType . '/' . $key);
                    }
                }
                continue;
            }

            // Nested NPCs: HIRER, WHOIS_MEMBER
            foreach ($entry as $level => $levelEntry) {
                $numType = $levelEntry['type'];
                if ($numType != 61) {
                    foreach ($levelEntry as $key => $subEntry) {
                        if ($key !== 'type') {
                            $this->add($numType, $subEntry, $npcType . '/' . $level . '/' . $key);
                        }
                    }
                }
            }
        }

        // Call SoftwareGenerator and RiddleSoftwareGenerator
        require_once BASE_PATH . 'classes/SoftwareGenerator.class.php';
        $softGen = new SoftwareGenerator();
        $softGen->generate();

        require_once BASE_PATH . 'classes/RiddleSoftwareGenerator.class.php';
        $riddleGen = new RiddleSoftwareGenerator();
        $riddleGen->generate();

        // Call NPCWebGenerator to process web templates
        require_once BASE_PATH . 'classes/NPCWebGenerator.class.php';
        $webGen = new NPCWebGenerator();
        $webGen->generate();
    }
}

?>
