<?php

class Notification {

    private static function pdo() {
        return PDO_DB::factory();
    }

    /**
     * Send a notification to a user.
     */
    public static function send($userID, $type, $message, $link = '') {
        $stmt = self::pdo()->prepare("INSERT INTO notifications (userID, type, message, link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userID, $type, $message, $link]);
    }

    /**
     * Get unread notification count for a user.
     */
    public static function getUnreadCount($userID) {
        $stmt = self::pdo()->prepare("SELECT COUNT(*) FROM notifications WHERE userID = ? AND isRead = 0");
        $stmt->execute([$userID]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get recent notifications for a user.
     */
    public static function getRecent($userID, $limit = 10) {
        $stmt = self::pdo()->prepare("SELECT * FROM notifications WHERE userID = ? ORDER BY createdAt DESC LIMIT ?");
        $stmt->bindValue(1, $userID, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark one or all notifications as read.
     */
    public static function markRead($userID, $id = null) {
        if ($id) {
            $stmt = self::pdo()->prepare("UPDATE notifications SET isRead = 1 WHERE id = ? AND userID = ?");
            $stmt->execute([$id, $userID]);
        } else {
            $stmt = self::pdo()->prepare("UPDATE notifications SET isRead = 1 WHERE userID = ?");
            $stmt->execute([$userID]);
        }
    }

    /**
     * Get FontAwesome icon class for a notification type.
     */
    public static function getTypeIcon($type) {
        $icons = [
            'mission' => 'fa-briefcase',
            'hack' => 'fa-terminal',
            'badge' => 'fa-trophy',
            'virus' => 'fa-bug',
            'money' => 'fa-usd',
            'clan' => 'fa-users',
            'system' => 'fa-info-circle',
            'attack' => 'fa-crosshairs',
        ];
        return $icons[$type] ?? 'fa-bell';
    }

}

?>
