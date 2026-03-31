<?php

class NPCWebGenerator {

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

    private function matchSlash($txt) {
        $subArray = null;
        $subgroup = explode('/', $txt);

        foreach ($subgroup as $id => $key) {
            // HTML bug fix (</span>)
            if ($key === '<') {
                break;
            }

            if ($id === 0) {
                $subArray = null;
                $baseArray = $this->npcList;
            } else {
                $baseArray = $subArray;
            }

            if (is_array($baseArray) && isset($baseArray[$key])) {
                $subArray = $baseArray[$key];
            }
        }

        return $subArray;
    }

    private function getIP($key) {
        $stmt = $this->pdo->prepare("
            SELECT INET_NTOA(npc.npcIP)
            FROM npc_key
            INNER JOIN npc
            ON npc.id = npc_key.npcID
            WHERE npc_key.key = :key
            LIMIT 1
        ");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        if ($row) {
            return $row[0];
        }
        return 'Unknown IP';
    }

    private function chooseLanguage($info, $language) {
        if ($language === 'en') {
            return $info['en'];
        }
        return isset($info[$language]) ? $info[$language] : $info['en'];
    }

    private function getInfo($npcInfo, $match, $language = 'en') {
        if (!$npcInfo) {
            return null;
        }

        // Try to get language-specific value
        if (is_array($npcInfo) && (isset($npcInfo['en']) || isset($npcInfo[$language]))) {
            return $this->chooseLanguage($npcInfo, $language);
        }

        // Check if requesting an IP (match ends with /ip)
        if (substr($match, -3) === '/ip') {
            return $this->getIP(substr($match, 0, -3));
        }

        return null;
    }

    private function webFormat($txt, $language) {
        $parted = explode('::', $txt);
        if (count($parted) === 1) {
            return $txt;
        }

        foreach ($parted as $match) {
            if ($match === '') {
                continue;
            }

            $value = null;

            // Try direct npcList lookup
            if (isset($this->npcList[$match])) {
                $value = $this->getInfo($this->npcList[$match], $match, $language);
                if ($value !== null) {
                    $txt = str_replace('::' . $match . '::', $value, $txt);
                    continue;
                }
            }

            // Try slash-based lookup
            $slashResult = $this->matchSlash($match);
            $value = $this->getInfo($slashResult, $match, $language);

            if ($value !== null) {
                $txt = str_replace('::' . $match . '::', $value, $txt);
            }
        }

        return $txt;
    }

    private function update($npcType, $npcInfo, $key) {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("
                SELECT npcID
                FROM npc_key
                WHERE npc_key.key = :key
                LIMIT 1
            ");
            $stmt->execute([':key' => $key]);
            $row = $stmt->fetch(PDO::FETCH_NUM);

            if (!$row) {
                return;
            }

            $npcID = strval($row[0]);

            // Update npc_info_lang
            foreach ($npcInfo['name'] as $language => $npcName) {
                $npcWeb = $this->webFormat($npcInfo['web'][$language], $language);
                $table = 'npc_info_' . $language;

                $stmt = $this->pdo->prepare("
                    UPDATE {$table}
                    SET
                        web = :web,
                        name = :name
                    WHERE npcID = :npcID
                ");
                $stmt->execute([
                    ':web' => $npcWeb,
                    ':name' => $npcName,
                    ':npcID' => $npcID,
                ]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
        }
    }

    public function generate() {
        foreach ($this->npcList as $npcType => $entry) {
            // Direct NPCs with hardware: MD, FBI, NSA, ISP, EVILCORP, SAFENET, DC, etc.
            if (isset($entry['hardware'])) {
                $this->update($entry['type'], $entry, $npcType);
                continue;
            }

            // Grouped NPCs with type: WHOIS, BANK, NPC, PUZZLE
            if (isset($entry['type'])) {
                $numType = $entry['type'];
                foreach ($entry as $key => $subEntry) {
                    if ($key !== 'type') {
                        $this->update($numType, $subEntry, $npcType . '/' . $key);
                    }
                }
                continue;
            }

            // Nested NPCs: WHOIS_MEMBER, HIRER
            foreach ($entry as $level => $levelEntry) {
                $numType = $levelEntry['type'];
                if ($numType != 61) {
                    foreach ($levelEntry as $key => $subEntry) {
                        if ($key !== 'type') {
                            $this->update($numType, $subEntry, $npcType . '/' . $level . '/' . $key);
                        }
                    }
                }
            }
        }
    }
}

?>
