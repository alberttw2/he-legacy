<?php

date_default_timezone_set('UTC');

if(isset($_SERVER['HTTP_REFERER'])){
    $ref = $_SERVER['HTTP_REFERER'];
} else {
    $ref = '';
}

if(isset($_SESSION['id'])){

//    $_SESSION['id'] = 1;
 

    if(!is_int((int)$_SESSION['id'])){
        exit('Invalid session id.');
    }
    
    require_once BASE_PATH . 'classes/Ranking.class.php';
    
    $ranking = new Ranking();
    $session = new Session();
    
    $pdo = PDO_DB::factory();
    
    $session->newQuery();
    $sql = "SELECT lang FROM users_language WHERE userID = :uid LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':uid' => $_SESSION['id']));
    $langResult = $stmt->fetch(PDO::FETCH_OBJ);
    $lang = $langResult ? $langResult->lang : 'en';
    
//    require BASE_PATH . 'classes/EmailVerification.class.php';
//    $emailVerification = new EmailVerification();
//    
//    if(!$emailVerification->isVerified($_SESSION['id'])){
//        header("Location:welcome");
//        exit();
//    }
//    
//    if((!$ranking->cert_have('1')) && ($_SERVER['SCRIPT_NAME'] != '/university.php' || ($_SERVER['SCRIPT_NAME'] == '/university.php' && !isset($_GET['opt']))) && $_SERVER['SCRIPT_NAME'] != '/welcome.php'){
//        header("Location:welcome");
//    }

    if(($_SESSION['ROUND_STATUS'] ?? 0) != 1){
        
        $redirect = TRUE;
        
        switch($_SERVER['SCRIPT_NAME']){
            case '/index.php':
            case '/index':
            case '/ajax.php':
            case '/ajax':
            case '/ranking.php':
            case '/ranking':
            case '/fame.php':
            case '/fame':
            case '/stats.php':
            case '/stats':
            case '/mail.php':
            case '/mail':
            case '/settings.php':
            case '/settings':
            case '/profile.php':
            case '/profile':
            case '/clan.php':
            case '/clan':
                $redirect = FALSE;
                break;
        }
        
        if($redirect){
            header("Location:index");
        }
        
    }
    
    if(!$session->validLogin()){
        $session->logout(0);
        header("Location:index");
        exit();
    }

    $curDate = new DateTime('now');
    $curDate->modify('-5 minutes');
    if(!isset($_SESSION['LAST_CHECK'])){
        $_SESSION['LAST_CHECK'] = new DateTime('now');
    }
    $checkDiff = $curDate->diff($_SESSION['LAST_CHECK']);
    
    if($checkDiff->invert == 1){
        
        if($checkDiff->i < 2){
            $ranking->updateTimePlayed();
        }

        $_SESSION['LAST_CHECK'] = new DateTime('now');
        
    }
    
} else {
    
    $_SESSION['GOING_TO'] = $_SERVER['REQUEST_URI'];
    
    if(!isset($_SESSION)){
        session_start();
    }
    
    $_SESSION['MSG'] = 'You are not logged in.';
    $_SESSION['TYP'] = 'index';
    $_SESSION['MSG_TYPE'] = 'error';
    
    header("Location:index");
    exit();

}

?>
