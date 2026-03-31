<?php

require 'config.php';
require BASE_PATH . 'classes/Session.class.php';
require BASE_PATH . 'classes/Player.class.php';
require BASE_PATH . 'classes/Internet.class.php';
require BASE_PATH . 'classes/System.class.php';

$session = new Session();
$system = new System();

require 'template/contentStart.php';

$player = new Player($_SESSION['id']);
$internet = new Internet();
$ranking = new Ranking();

// Cert check removed — Internet accessible to all players

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST)){

    $internet->handlePost();

}

if($system->issetGet('ip')){

    $getIP = trim($_GET['ip']);
    
    if(!$system->validate($getIP, 'ip')){
        exit("Invalid IP");
    }

    $internet->navigate(ip2long($getIP));

} else {

    if($session->isInternetLogged()){

        $internet->navigate($_SESSION['LOGGED_IN']);

    } elseif($session->issetInternetSession()){ 

        $internet->navigate($_SESSION['CUR_IP']);

    } else {

        $internet->navigate($internet->home_getIP());

    }

}

require 'template/contentEnd.php';

?>