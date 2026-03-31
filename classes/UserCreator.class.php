<?php

class UserCreator {

    private $db;

    public function __construct() {
        $this->db = PDO_DB::factory();
    }

    /**
     * Generate a random game password (8 chars: uppercase + lowercase + digits).
     */
    private function generateGamePassword($length = 8) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
     * Create a new user and initialize all related tables.
     *
     * @param string      $login
     * @param string      $password  Hashed password
     * @param string      $email
     * @param string      $ip
     * @param array|null  $social    Optional array with keys 'type' ('fb'/'tt') and 'socialID'
     * @return int|false  User ID on success, false on failure
     */
    public function create($login, $password, $email, $ip, $social = null) {

        try {

            $this->db->beginTransaction();

            // INSERT into users
            $stmt = $this->db->prepare(
                "INSERT INTO users (login, password, gamePass, email, gameIP, realIP, homeIP)
                 VALUES (:login, :password, :gamePass, :email, INET_ATON(:gameIP), 0, INET_ATON(:gameIP))"
            );
            $stmt->execute([
                ':login'    => $login,
                ':password' => $password,
                ':gamePass' => $this->generateGamePassword(),
                ':email'    => $email,
                ':gameIP'   => $ip
            ]);

            $userID = (int) $this->db->lastInsertId();

            // INSERT into users_stats
            $stmt = $this->db->prepare(
                "INSERT INTO users_stats (uid, dateJoined) VALUES (:uid, NOW())"
            );
            $stmt->execute([':uid' => $userID]);

            // INSERT into hardware
            $stmt = $this->db->prepare(
                "INSERT INTO hardware (userID, name) VALUES (:userID, 'VPS Node #1')"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into log
            $stmt = $this->db->prepare(
                "INSERT INTO log (userID, log.text)
                 VALUES (:userID, CONCAT(SUBSTRING(NOW(), 1, 16), ' - localhost installed current operating system'))"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into cache
            $stmt = $this->db->prepare(
                "INSERT INTO cache (userID) VALUES (:userID)"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into cache_profile
            $stmt = $this->db->prepare(
                "INSERT INTO cache_profile (userID, expireDate) VALUES (:userID, NOW())"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into hist_users_current
            $stmt = $this->db->prepare(
                "INSERT INTO hist_users_current (userID) VALUES (:userID)"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into ranking_user
            $stmt = $this->db->prepare(
                "INSERT INTO ranking_user (userID, rank) VALUES (:userID, '-1')"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into certifications (start at 0, player must complete them)
            $stmt = $this->db->prepare(
                "INSERT INTO certifications (userID, certLevel) VALUES (:userID, 0)"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into users_puzzle
            $stmt = $this->db->prepare(
                "INSERT INTO users_puzzle (userID) VALUES (:userID)"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into users_learning
            $stmt = $this->db->prepare(
                "INSERT INTO users_learning (userID) VALUES (:userID)"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into users_language
            $stmt = $this->db->prepare(
                "INSERT INTO users_language (userID) VALUES (:userID)"
            );
            $stmt->execute([':userID' => $userID]);

            // INSERT into users_onboarding
            $stmt = $this->db->prepare(
                "INSERT INTO users_onboarding (userID) VALUES (:userID)"
            );
            $stmt->execute([':userID' => $userID]);

            // Social login: INSERT into users_facebook or users_twitter
            if ($social !== null && !empty($social['socialID'])) {

                if ($social['type'] === 'fb') {
                    $stmt = $this->db->prepare(
                        "INSERT INTO users_facebook (userID, gameID) VALUES (:socialID, :gameID)"
                    );
                    $stmt->execute([
                        ':socialID' => $social['socialID'],
                        ':gameID'   => $userID
                    ]);
                } elseif ($social['type'] === 'tt') {
                    $stmt = $this->db->prepare(
                        "INSERT INTO users_twitter (userID, gameID) VALUES (:socialID, :gameID)"
                    );
                    $stmt->execute([
                        ':socialID' => $social['socialID'],
                        ':gameID'   => $userID
                    ]);
                }

            }

            $this->db->commit();

            // TODO: call ProfileGenerator

            return $userID;

        } catch (Exception $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            error_log("UserCreator: Rolling back create_user {$login} using {$email} - " . $e->getMessage());

            return false;

        }

    }

}

?>
