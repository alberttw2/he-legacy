<?php

class SES {

    public function __construct(){
    }

    public function send($action, $info, $lang = false){
        $to = isset($info['to']) ? $info['to'] : 'unknown';
        Logger::info("SES stub: sending email", ['action' => $action, 'to' => $to]);
        return TRUE;
    }

}
