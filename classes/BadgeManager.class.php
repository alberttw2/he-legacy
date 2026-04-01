<?php

require_once BASE_PATH . 'classes/PDO.class.php';
require_once BASE_PATH . 'classes/SES.class.php';

class BadgeManager {

    /**
     * Award a badge to a user or clan.
     *
     * Ported from python/badge_add.py - replicates all validation logic.
     *
     * @param string $type    'user' or 'clan'
     * @param int    $id      userID or clanID
     * @param int    $badgeID Badge identifier
     */
    public static function award($type, $id, $badgeID) {

        $pdo = PDO_DB::factory();

        $id      = (int) $id;
        $badgeID = (int) $badgeID;

        // Determine table and field based on type
        if ($type === 'user') {
            $isUser     = true;
            $badgeTable = 'users_badge';
            $field      = 'userID';
        } else {
            $isUser     = false;
            $badgeTable = 'clan_badge';
            $field      = 'clanID';
        }

        // Load badge metadata from JSON
        $jsonPath  = BASE_PATH . 'json/badges.json';
        $badgeList = json_decode(file_get_contents($jsonPath), true);
        $badgeKey  = (string) $badgeID;

        if (!isset($badgeList[$badgeKey])) {
            return;
        }

        $badgeInfo = array(
            'name'        => $badgeList[$badgeKey]['name'],
            'desc'        => $badgeList[$badgeKey]['desc'],
            'collectible' => $badgeList[$badgeKey]['collectible'],
            'per_round'   => $badgeList[$badgeKey]['per_round'],
        );

        $issetExtra = false;
        if (isset($badgeList[$badgeKey]['extra'])) {
            $badgeInfo['extra'] = $badgeList[$badgeKey]['extra'];
            $issetExtra = true;
        }

        // Check if badge already exists for this user/clan
        $badgeIsset = self::badgeIsset($pdo, $badgeTable, $field, $id, $badgeID);

        // Only proceed if badge is not set OR badge is collectible
        if ($badgeIsset && !$badgeInfo['collectible']) {
            return;
        }

        // Get current round
        $curRound = self::getCurrentRound($pdo);

        $valid = true;

        // Per-round restriction: if badge already exists and is per_round, check this round
        if ($badgeIsset && $badgeInfo['per_round']) {
            if (self::badgeHaveThisRound($pdo, $badgeTable, $badgeID, $curRound)) {
                $valid = false;
            }
        }

        // Extra delay constraint
        if ($issetExtra) {
            if (isset($badgeInfo['extra']['delay'])) {
                $delay = $badgeInfo['extra']['delay'];
                if (!self::badgeValidDelay($pdo, $badgeID, $id, $delay)) {
                    $valid = false;
                }
            }
        }

        if (!$valid) {
            return;
        }

        // Insert badge record
        $sql = "INSERT INTO {$badgeTable} ({$field}, badgeID, round, dateAdd)
                VALUES (:id, :badgeID, :round, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            ':id'      => $id,
            ':badgeID' => $badgeID,
            ':round'   => $curRound,
        ));

        // User-specific post-insert logic
        if ($isUser) {

            // Send notification mail (skip for badge 13 if already owned)
            if (!$badgeIsset || $badgeID != 13) {

                $awardedPlayers = self::badgeCount($pdo, 1, $badgeID, $id);
                $myBadges       = self::badgeCount($pdo, 2, $badgeID, $id);
                $userName       = self::getUserName($pdo, $id);

                $subject = 'You earned a new badge!';
                $text    = 'Hello there, ' . $userName . '. You earned a new badge named <strong>' . $badgeInfo['name'] . '</strong>, go check it in <a href="profile">your profile</a>.<br/>';

                if ($awardedPlayers <= 0) {
                    $text .= 'Feel special: you are the first player to receive this badge! ';
                } elseif ($awardedPlayers == 1) {
                    $text .= 'Only one other player received this badge. ';
                } else {
                    $text .= 'Other ' . $awardedPlayers . ' players received this badge. ';
                }

                if ($myBadges == 1) {
                    $text .= 'Enjoy your first badge :)';
                } else {
                    $text .= 'You now have a total of <strong>' . $myBadges . '</strong> badges.';

                    // Auto-award "30 badges" achievement (badge 50) when user reaches 30 badges
                    if ($myBadges == 30) {
                        self::award('user', $id, 50);
                    }
                }

                // Insert notification mail (from = -7 = Badge Advisor)
                self::sendMail($pdo, $id, $subject, $text);

                // Send in-game notification
                require_once BASE_PATH . 'classes/Notification.class.php';
                Notification::send($id, 'badge', 'You earned a new badge: ' . $badgeInfo['name'] . '!', 'profile');
            }

            // TODO: call ProfileGenerator
        }
    }

    /**
     * Check if badge already exists for user/clan.
     */
    private static function badgeIsset($pdo, $badgeTable, $field, $id, $badgeID) {

        $sql = "SELECT COUNT(*) AS total
                FROM {$badgeTable}
                WHERE {$field} = :id AND badgeID = :badgeID
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':id' => $id, ':badgeID' => $badgeID));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($row['total'] > 0);
    }

    /**
     * Check if badge was already awarded this round.
     */
    private static function badgeHaveThisRound($pdo, $badgeTable, $badgeID, $curRound) {

        $sql = "SELECT COUNT(*) AS total
                FROM {$badgeTable}
                WHERE badgeID = :badgeID AND round = :round
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':badgeID' => $badgeID, ':round' => $curRound));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($row['total'] > 0);
    }

    /**
     * Count badges based on search type.
     *
     * @param int $searchType 1 = count distinct badges for a badgeID,
     *                        2 = count distinct badges for a userID,
     *                        other = count all instances of a badgeID
     */
    private static function badgeCount($pdo, $searchType, $badgeID, $userID) {

        if ($searchType == 1) {
            $select = 'COUNT(DISTINCT badgeID)';
            $where  = 'badgeID';
            $search = $badgeID;
        } elseif ($searchType == 2) {
            $select = 'COUNT(DISTINCT badgeID)';
            $where  = 'userID';
            $search = $userID;
        } else {
            $select = 'COUNT(badgeID)';
            $where  = 'badgeID';
            $search = $badgeID;
        }

        $sql = "SELECT {$select} AS total
                FROM users_badge
                WHERE {$where} = :search";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':search' => $search));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $row['total'];
    }

    /**
     * Validate delay constraint: ensure enough days have passed since last award.
     */
    private static function badgeValidDelay($pdo, $badgeID, $userID, $delay) {

        $sql = "SELECT COUNT(*) AS total
                FROM users_badge
                WHERE TIMESTAMPDIFF(DAY, dateAdd, NOW()) < :delay
                  AND badgeID = :badgeID
                  AND userID = :userID";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            ':delay'   => $delay,
            ':badgeID' => $badgeID,
            ':userID'  => $userID,
        ));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($row['total'] <= 0);
    }

    /**
     * Get the current (latest) round ID.
     */
    private static function getCurrentRound($pdo) {

        $sql  = "SELECT id FROM round ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->query($sql);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $row['id'];
    }

    /**
     * Get a user's login name by ID.
     */
    private static function getUserName($pdo, $userID) {

        $sql  = "SELECT login FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':id' => $userID));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['login'] : 'Unknown';
    }

    /**
     * Insert a notification mail from the Badge Advisor (from = -7).
     */
    private static function sendMail($pdo, $userID, $subject, $message) {

        $sql = "INSERT INTO mails (mails.from, mails.to, mails.type, subject, mails.text, dateSent)
                VALUES (:fromID, :toID, '', :subject, :message, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
            ':fromID'  => -7,
            ':toID'    => $userID,
            ':subject' => $subject,
            ':message' => $message,
        ));
    }

}

?>
