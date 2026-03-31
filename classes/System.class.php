<?php

class System {

    private $pdo;
    private $session;
    
    function __construct(){

        //

    }
    
    public function changeHTML($id, $content){
        
        ?>
        
        <script>
        document.getElementById("<?php echo $id; ?>").innerHTML="<?php echo $content; ?>";
        </script>

        <?php

    }
    
    function issetGet($get){

        if(isset($_GET[$get])){

            if(!empty($_GET[$get])){
                return TRUE;
            }
                
        }
        
        return FALSE;


    }

    public function switchGet($get, $a, $b, $c = '', $d = '', $e = '', $f = '', $g = '', $h = '', $i = ''){

        $args = func_get_args();

        $tries = '0';

        for($i=0;$i<func_num_args();$i++){

            if($_GET[$get] == $args[$i]){

                $return = Array(
                    'ISSET_GET' => '1',
                    'GET_NAME' => $get,
                    'GET_VALUE' => $args[$i],
                );

                return $return;

            }

            $tries++;

        }

        if($tries == func_num_args()){

            $return = Array(
                'ISSET_GET' => '0',
                'GET_NAME' => '',
                'GET_VALUE' => '',
            );

        }

        return $return;

    }

    public function verifyNumericGet($get){
        
        if(self::issetGet($get)){
            
            $value = (int)$_GET[$get];
            
            if(is_int($value) && strlen($value != '0')){

                $return = Array(
                    'IS_NUMERIC' => '1',
                    'GET_VALUE' => $value,
                );

            } else {

                $return = Array(
                    'IS_NUMERIC' => '0',
                    'GET_VALUE' => '',
                );

            }

            return $return;

        } else {

            return FALSE;

        }

    }

    public function verifyStringGet($get){

        if(self::issetGet($get) && strlen($_GET[$get]) != '0'){

            $return = Array(
                'IS_NUMERIC' => '0',
                'GET_VALUE' => $_GET[$get],
            );

        } else {

            $return = Array(
                'IS_NUMERIC' => '',
                'GET_VALUE' => '',
            );

        }

        return $return;

    }

    public function handleError($error, $redirect = ''){

        require_once BASE_PATH . 'classes/ErrorMessages.class.php';

        if($error == ''){
            $msg = '';
        } else {
            $msg = ErrorMessages::get($error);
        }

        if($msg != ''){

            if($this->session == NULL){
                $this->session = new Session();
            }

            $this->session->addMsg($msg, 'error');

        }

        if($redirect != ''){

            header("Location:$redirect");
            exit();        
  
        }

    }
    
    public function validate($var, $type){
        
        switch($type){
            
            case 'ip':
            case 'IP':
                
                //ipv4
                return filter_var($var, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);                
                
            case 'hintip':
                
                //XXX.XXX.XXX.XXX
                return preg_match('/^[0-9.xX]{7,15}$/', $var);
                
            case 'user':
            case 'username':
                
                //<caracter> ou ._-
                return preg_match('/^[a-zA-Z0-9_.-]{1,15}$/', $var);
            
            case 'soft':
            case 'software':
               
                //<caracter>+(<caracter> ou _-<espaço>) (não permite ponto)
                return preg_match("/^[a-zA-Z0-9][a-zA-Z0-9_ -]{1,}$/", $var);
                
            case 'subject':
                
                //<caracter>+(<caracter> ou _!,-$.<espaço>)
                return preg_match("/^[a-zA-Z0-9áÁÀàóÓêÊõíÍúÚçÇñã][a-zA-Z0-9çÇáÁÀàóÓêÊõíÍúÚñã.,_$!?():'\" -]{1,}$/", $var);
                
            case 'text': 
                //<caracter>+(<caracter> ou _!,-$.<espaço>@#$%*(()+={}<>)
                return preg_match("/^[a-zA-Z0-9áÁÀàóÓêÊõíÍúÚñãçÇ][a-zA-Z0-9çÇáÁÀàóÓêÊõíÍúÚñã.,_$!()'\"@#%*+={}<> -?!]{1,}$/", $var);
                
            case 'email':
                
                return filter_var($var, FILTER_VALIDATE_EMAIL);
                
            case 'clan_name':
                
                return preg_match("/^[a-zA-Z0-9áÁÀàóÓêÊõíçÇÍúÚñã][a-zA-Z0-9áÁÀàóÓêÊçÇõíÍúÚñã_.! -]{1,}$/", $var);
                
            case 'clan_tag':
                
                return preg_match('/^[a-zA-Z0-9_.-]{1,3}$/', $var);
                
            case 'qa-answer':
                
                return preg_match('/^[a-zA-Z0-9öéáóíõçáÁÀàóÓêÊõíÍúÚñãÇ ,.=+-\/*]{1,}$/', $var);
                
            case 'pricing_plan':
                
                return preg_match('/^[a-zA-Z0-9]{1,}$/', $var);
                
                
        }
        
        echo 'Undefined type';
        
    }

}

?>
