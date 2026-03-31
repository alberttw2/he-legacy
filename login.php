<?php
require_once __DIR__ . "/config.php";

require BASE_PATH . 'classes/Session.class.php';
$session = new Session();

if ($_SERVER['REQUEST_METHOD'] != 'POST' || $session->issetLogin()) {
    header("Location:index.php");
    exit();
}

// Rate limit: 10 login attempts per 5 minutes per IP
if (!RateLimiter::check('login', 10, 300)) {
    $retry = RateLimiter::retryAfter('login', 300);
    $_SESSION['MSG'] = "Too many login attempts. Try again in {$retry} seconds.";
    $_SESSION['TYP'] = 'LOG';
    $_SESSION['MSG_TYPE'] = 'error';
    header("Location:index.php");
    exit();
}

require BASE_PATH . 'classes/Database.class.php';

$user = htmlentities($_POST['username'] ?? '');
$pass = htmlentities($_POST['password'] ?? '');

$db = new LRSys();

if(isset($_POST['keepalive'])){
    $db->set_keepalive(TRUE);
}

if(!$db->login($user, $pass)){
    $_SESSION['TYP'] = 'LOG';
}

header("Location:login.php");

?>