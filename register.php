<?php
require_once __DIR__ . "/config.php";

require BASE_PATH . 'classes/Session.class.php';
$session = new Session();

if ($_SERVER['REQUEST_METHOD'] != 'POST' || $session->issetLogin()) {

    header("Location:index.php");
    exit();

}

if (!CSRF::verify()) {
    $_SESSION['MSG'] = 'Invalid request. Please try again.';
    $_SESSION['TYP'] = 'REG';
    $_SESSION['MSG_TYPE'] = 'error';
    header("Location:index.php");
    exit();
}

require BASE_PATH . 'classes/Database.class.php';

$regLogin = $_POST['username'];
$regPass = $_POST['password'];
$regEmail = $_POST['email'];

$database = new LRSys();

if ($database->register($regLogin, $regPass, $regEmail)) {

    //Todo: header to email confirmation.

}

$_SESSION['TYP'] = 'REG';

header('Location:index.php');

?>
