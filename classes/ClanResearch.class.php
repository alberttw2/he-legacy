<?php
class ClanResearch {
    private $pdo;

    public function __construct() {
        $this->pdo = PDO_DB::factory();
    }

    // Get active research for a clan
    public function getActive($clanID) {
        $stmt = $this->pdo->prepare(
            "SELECT cr.*, COUNT(crc.userID) as contributors,
             SUM(COALESCE(crc.cpuContributed, 0)) as totalCpu
             FROM clan_research cr
             LEFT JOIN clan_research_contributors crc ON cr.id = crc.researchID
             WHERE cr.clanID = ? AND cr.status = 1
             GROUP BY cr.id
             LIMIT 1"
        );
        $stmt->execute([$clanID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Start a new research (clan leader/officer only)
    public function start($clanID, $softType, $targetVersion, $startedBy) {
        // Check no active research
        if ($this->getActive($clanID)) return false;

        // Calculate base time (in seconds) - similar to software research
        $baseTime = $targetVersion * 60; // 1 minute per version level

        $stmt = $this->pdo->prepare(
            "INSERT INTO clan_research (clanID, softType, targetVersion, startedBy, estimatedEnd)
             VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))"
        );
        $stmt->execute([$clanID, $softType, $targetVersion, $startedBy, $baseTime]);
        return $this->pdo->lastInsertId();
    }

    // Join research as contributor
    public function contribute($researchID, $userID) {
        // Get research info
        $research = $this->getResearchById($researchID);
        if (!$research || $research['status'] != 1) return false;

        // Check if already contributing
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clan_research_contributors WHERE researchID = ? AND userID = ?");
        $stmt->execute([$researchID, $userID]);
        if ($stmt->fetchColumn() > 0) return false;

        // Get player's personal CPU (this will be "blocked" while contributing)
        $stmt = $this->pdo->prepare("SELECT SUM(cpu) as totalCpu FROM hardware WHERE userID = ? AND isNPC = 0");
        $stmt->execute([$userID]);
        $playerCpu = (int)$stmt->fetchColumn();

        // Add contributor — store the player's CPU contribution
        $this->pdo->prepare(
            "INSERT INTO clan_research_contributors (researchID, userID, cpuContributed) VALUES (?, ?, ?)"
        )->execute([$researchID, $userID, $playerCpu]);

        // Recalculate estimated end — more CPU contributed = faster
        $this->recalculateTime($researchID);

        return true;
    }

    /**
     * Check if a user is currently contributing to clan research.
     * While contributing, their CPU processes run slower (50% CPU penalty).
     */
    public static function isContributing($userID) {
        $pdo = PDO_DB::factory();
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM clan_research_contributors crc
             INNER JOIN clan_research cr ON crc.researchID = cr.id
             WHERE crc.userID = ? AND cr.status = 1"
        );
        $stmt->execute([$userID]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Leave/stop contributing to active research.
     */
    public function stopContributing($researchID, $userID) {
        $this->pdo->prepare(
            "DELETE FROM clan_research_contributors WHERE researchID = ? AND userID = ?"
        )->execute([$researchID, $userID]);
        $this->recalculateTime($researchID);
    }

    // Recalculate research time based on number of contributors
    private function recalculateTime($researchID) {
        // Get research info + contributor count + elapsed time all from DB
        $stmt = $this->pdo->prepare("
            SELECT cr.targetVersion,
                   TIMESTAMPDIFF(SECOND, cr.startDate, NOW()) as elapsed,
                   (SELECT COUNT(*) FROM clan_research_contributors WHERE researchID = cr.id) as contributors
            FROM clan_research cr WHERE cr.id = ?
        ");
        $stmt->execute([$researchID]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) return;

        $baseTime = (int)$data['targetversion'] * 60;
        $contributors = max(1, (int)$data['contributors']);
        $elapsed = max(0, (int)$data['elapsed']);

        // More contributors = faster: base / (1 + N * 0.3)
        $totalTime = $baseTime / (1 + $contributors * 0.3);
        $remaining = max(1, round($totalTime - $elapsed));

        $this->pdo->prepare(
            "UPDATE clan_research SET estimatedEnd = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE id = ?"
        )->execute([$remaining, $researchID]);
    }

    // Check and complete research if time is up
    public function checkComplete($clanID) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM clan_research WHERE clanID = ? AND status = 1 AND estimatedEnd <= NOW()"
        );
        $stmt->execute([$clanID]);
        $research = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($research) {
            // Mark as complete
            $this->pdo->prepare("UPDATE clan_research SET status = 2 WHERE id = ?")->execute([$research['id']]);

            // Get all clan members and give them the software
            $stmt = $this->pdo->prepare("SELECT userID FROM clan_users WHERE clanID = ?");
            $stmt->execute([$clanID]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get software name from type
            require_once BASE_PATH . 'classes/PC.class.php';
            $software = new SoftwareVPC();
            $softName = $software->int2stringSoftwareType($research['softtype']);

            foreach ($members as $member) {
                // Insert software for each member
                $this->pdo->prepare(
                    "INSERT INTO software (userID, softName, softVersion, softSize, softRam, softType, softLastEdit, isNPC)
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)"
                )->execute([
                    $member['userid'],
                    $softName,
                    $research['targetversion'],
                    max(1, round($research['targetversion'] / 2)),
                    max(1, round($research['targetversion'] / 3)),
                    $research['softtype']
                ]);

                // Send notification
                require_once BASE_PATH . 'classes/Notification.class.php';
                Notification::send($member['userid'], 'clan',
                    "Clan research complete: $softName v" . floor($research['targetversion']/10) . '.' . ($research['targetversion']%10),
                    'clan'
                );
            }

            return true;
        }
        return false;
    }

    private function getResearchById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM clan_research WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Cancel research (leader only)
    public function cancel($researchID, $userID) {
        $this->pdo->prepare(
            "UPDATE clan_research SET status = 3 WHERE id = ? AND startedBy = ?"
        )->execute([$researchID, $userID]);
    }

    // Get completed research history for a clan
    public function getHistory($clanID) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM clan_research WHERE clanID = ? AND status = 2 ORDER BY startDate DESC LIMIT 20"
        );
        $stmt->execute([$clanID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Render the Research Lab tab
    public function renderLab($clanID, $isLeader) {
        $active = $this->getActive($clanID);
        $this->checkComplete($clanID);

        require_once BASE_PATH . 'classes/PC.class.php';
        $software = new SoftwareVPC();

        echo '<div class="widget-box"><div class="widget-title"><span class="icon"><i class="fa fa-flask"></i></span><h5>Research Lab</h5></div>';
        echo '<div class="widget-content padding">';

        if ($active) {
            $remaining = max(0, strtotime($active['estimatedend']) - time());
            $mins = floor($remaining / 60);
            $secs = $remaining % 60;
            $softName = $software->int2stringSoftwareType($active['softtype']);
            $version = floor($active['targetversion']/10) . '.' . ($active['targetversion']%10);

            echo '<h4><i class="fa fa-cog fa-spin"></i> Researching: ' . htmlspecialchars($softName) . ' v' . $version . '</h4>';
            echo '<div class="progress progress-striped active" style="margin:10px 0;"><div class="progress-bar" style="width:' .
                 ($remaining > 0 ? max(5, 100 - round($remaining / max(1, $active['targetversion'] * 60) * 100)) : 100) . '%"></div></div>';
            echo '<p>Time remaining: <strong>' . ($remaining > 0 ? $mins . 'm ' . $secs . 's' : 'Complete!') . '</strong></p>';
            echo '<p>Contributors: <strong>' . ($active['contributors'] ?? 0) . '</strong> members</p>';
            echo '<p>Started by: user #' . $active['startedby'] . '</p>';

            // Check if current user is already contributing
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clan_research_contributors WHERE researchID = ? AND userID = ?");
            $stmt->execute([$active['id'], $_SESSION['id']]);
            $isContributing = $stmt->fetchColumn() > 0;

            if (!$isContributing && $remaining > 0) {
                echo '<form method="POST" action="clan"><input type="hidden" name="clan_action" value="contribute_research"><input type="hidden" name="research_id" value="' . $active['id'] . '">';
                echo '<button class="btn btn-success" type="submit"><i class="fa fa-plus"></i> Contribute CPU</button></form>';
            } elseif ($isContributing) {
                echo '<span class="label label-success">You are contributing!</span> ';
                echo '<span class="label label-warning">Your CPU processes run at 50% while contributing</span> ';
                echo '<form method="POST" action="clan" style="display:inline;"><input type="hidden" name="clan_action" value="stop_contributing"><input type="hidden" name="research_id" value="' . $active['id'] . '">';
                echo '<button class="btn btn-small btn-warning" type="submit"><i class="fa fa-stop"></i> Stop Contributing</button></form>';
            }

            if ($isLeader) {
                echo ' <form method="POST" action="clan" style="display:inline;"><input type="hidden" name="clan_action" value="cancel_research"><input type="hidden" name="research_id" value="' . $active['id'] . '">';
                echo '<button class="btn btn-danger btn-small" type="submit">Cancel</button></form>';
            }
        } else {
            echo '<p>No active research. ';
            if ($isLeader) {
                echo 'Start a new one!</p>';
                // Show form to start research
                $softTypes = [1=>'Cracker', 2=>'Hasher', 3=>'Port Scanner', 4=>'Firewall', 5=>'Hidder', 6=>'Seeker', 8=>'Spam', 11=>'Collector', 13=>'FTP Exploit', 14=>'SSH Exploit'];
                echo '<form method="POST" action="clan">';
                echo '<input type="hidden" name="clan_action" value="start_research">';
                echo '<select name="soft_type" class="span3">';
                foreach ($softTypes as $type => $name) {
                    echo '<option value="' . $type . '">' . $name . '</option>';
                }
                echo '</select> ';
                echo '<select name="target_version" class="span2">';
                for ($v = 20; $v <= 100; $v += 10) {
                    echo '<option value="' . $v . '">v' . ($v/10) . '.0</option>';
                }
                echo '</select> ';
                echo '<button class="btn btn-primary" type="submit"><i class="fa fa-flask"></i> Start Research</button>';
                echo '</form>';
            } else {
                echo 'Ask your clan leader to start one.</p>';
            }
        }

        // History
        $history = $this->getHistory($clanID);
        if (!empty($history)) {
            echo '<h5 style="margin-top:15px;">Research History</h5>';
            echo '<table class="table table-cozy table-bordered table-striped"><tr><th>Software</th><th>Version</th><th>Date</th></tr>';
            foreach ($history as $h) {
                echo '<tr><td>' . htmlspecialchars($software->int2stringSoftwareType($h['softtype'])) . '</td>';
                echo '<td>v' . floor($h['targetversion']/10) . '.' . ($h['targetversion']%10) . '</td>';
                echo '<td>' . $h['startdate'] . '</td></tr>';
            }
            echo '</table>';
        }

        echo '</div></div>';
    }
}
