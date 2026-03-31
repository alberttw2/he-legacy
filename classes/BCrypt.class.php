<?php

/**
 * BCrypt wrapper using native PHP password_hash/password_verify.
 * Maintains backward compatibility with legacy $2a$ hashes.
 */
class BCrypt {
    private $options;

    public function __construct($rounds = 13) {
        $this->options = ['cost' => $rounds];
    }

    public function hash($input) {
        return password_hash($input, PASSWORD_BCRYPT, $this->options);
    }

    public function verify($input, $existingHash) {
        return password_verify($input, $existingHash);
    }
}
