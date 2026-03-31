<?php
require_once __DIR__ . "/config.php";

require BASE_PATH . 'classes/Session.class.php';
require BASE_PATH . 'classes/Ranking.class.php';
require BASE_PATH . 'classes/Forum.class.php';

$session  = new Session();
$ranking = new Ranking();
$forum = new Forum();

$ranking->updateTimePlayed();

$forum->logout();


$session->logout();



if($session->issetFBLogin()){
    
    require_once BASE_PATH . 'classes/Facebook.class.php';

    $facebook = new Facebook(array(
        'appId' => 'REDACTED',
        'secret' => 'REDACTED'
    ));

    $facebook->destroySession();
    
}

header("Location:index.php");
exit();