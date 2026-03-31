<?php

class PDO_DB {

    public $dbh;
    private static $instance = null;
    private static $dsn  = 'mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=game;charset=utf8mb4';
    private static $user = 'he';
    private static $pass = 'helegacy2024';
    private static $dbOptions = array(
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    );

    public static function factory() {
        if(self::$instance === null){
            self::$instance = new PDO(self::$dsn, self::$user, self::$pass, self::$dbOptions);
            // Disable strict mode for compatibility with legacy schema (no default values on many columns)
            self::$instance->exec("SET sql_mode = ''");
        }
        return self::$instance;
    }
    
}

?>
