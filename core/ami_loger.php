#!/usr/bin/php
<?php
    /**
     * Created by PhpStorm.
     * User: slava
     * Date: 20.05.15
     * Time: 14:51
     */
    $config = array(
        'host'=>"localhost",
        'user'=>"test",
        'password'=>"test",
        'database'=>"callwaytest",
        'manager_login'=>"dialmanager",
        'manager_password'=>"dialmanager",
        'manager_host'=>"95.141.192.26",
        'manager_port'=>"5038",
        'logfile'=>"./checker.log",
        'debug'=>false, //if true log will show to desktop
        'monitor'=>"/var/spool/asterisk/monitor/",
        'context'=> "managerd",
        'recordcontext' => "cwc_playwa"
    );
    include('mysqli.php');
    include('ami.php');

    $errno="";
    $errstr="";
    //$call=array("1"=>"iax2/1111@from-internal");
    //$data=array("1","2","3","4");
    $db= new db($config);
    $routename=$argv[1];
    $socket = fsockopen($config['manager_host'],$config['manager_port'], $errno, $errstr, 10);
    if (!$socket){
        echo "$errstr ($errno)\n";
    }
    else{
        date_default_timezone_set('Europe/Moscow');
        $ami=new Ami();
        $login_data=array(
            "UserName"=>$config['manager_login'],
            "Secret"=>$config['manager_password']
        );
        $login=$ami->Login($login_data);
        logger($login,'',$config['debug']);
        fputs($socket, $login);
        $data="";
        $event="";
        $action=false;
        //logger("start","",$config['debug']);

        while (true) {

            $data = fgets($socket);
            //echo $data;
            if ($data == "\r\n") {
                logger($event,"");
                $event="";
            }
            $event .= $data;
            $last = $data;
        }
        //}

        fclose($socket);
    }    //echo $data;

    function getnumb($db){
        $result = $db->select("`id`,`number` from `processing` where  `checkstart`=1 and `status`=0");

        return $result;
    }
    function channeltonumnber($c){

    }
    function process($data){

    }
    function logger1($data,$id="",$view=false){
        $file=$GLOBALS['config']['logfile'];
        $td=microtime(true);//date('Y-m-d H:i:s');
        $scriptname="checker.php";

        $head="$td $scriptname $id ";
        $data=$head.$data;
        $data=str_replace("\n","\n".$head,$data);
        $data=trim($data)."\n";
        if($data==""){
            $data="'' - empty";
        }

        if ($view) {
            echo $data;
        } else {
            file_put_contents($file, $data, FILE_APPEND);
        }
    }
    function logger($data,$id="",$view=false){
        if(is_array($data)){
            foreach($data as $key=>$value){
                logger1($key."=>",$id,$view);
                if(is_array($value)){
                    logger1("array",$id,$view);
                    logger($value,$id,$view);
                }
                else{
                    logger1($value,$id,$view);
                }
            }
        }else {
            logger1($data,$id,$view);
        }
    }
?>
