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
    'logfile'=>"/var/log/checker.log",
    'debug'=>true, //if true log will show to desktop
    'monitor'=>"/var/spool/asterisk/monitor/",
    'context'=> "checker", //"managerd",
    'recordcontext' => "cwc_playwa",
    'CallerID'=>"74991674500",
    'log_file' => "checker.log",
    'log_write' => "file",

);
include('mysqli.php');
include('ami.php');
include('log.php');
$log = new Log($config);

    ini_set('default_charset', 'utf-8');
date_default_timezone_set("Europe/Moscow");
$errno="";
$errstr="";
//$call=array("1"=>"iax2/1111@from-internal");
//$data=array("1","2","3","4");
$log->debug($config);
//die;
$db= new db($config);
$routename=$argv[1];
    $log->info("Checker start for route ".$routename);
    system("/usr/bin/php -f /var/www/html/dialmanager/core/sdp_check.php ".$routename." >> /var/log/checker.log & 2>/dev/null");
sleep(2);
//echo "start done";
//die;
logger($routename,"test argument",$config['debug']);
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
    $query="`id`,`number` from `processing` where `checkstart` = 0 and `routename` like '".$routename."' AND `number` <>''";
    logger($query,"select",$config['debug']);

    $data=$db->select($query, 1);
    logger($db->query->last,"query",$config['debug']);
    logger($data,'data from tables',$config['debug']);
    if(!is_array($data)){
        logger("Have no data from tables, process will die !!!!!!!!!",'',$config['debug']);
        die;
    }
    foreach ($data as $key=> $value){
        //>update('table', 'row,new_value', 'id=0')
        //$db->update("processing",'checkstart,1','id='.$value['id'] );
        //$tasks[]
    }

    logger($db->query->last);
    $state="";
    //die;
    //foreach($data as $key=>$value) {
$i=0;
foreach($data as $key=>$value){
    $data[$key]['recordfile']=strtolower($routename)."_".str_replace(array("-",":"," "),"_",date('Y-m-d H:i:s'))."_".$data[$key]['number']."_".$data[$key]['id'];
    $data[$key]['recordfile2']=str_replace(array("-",":"," "),"_",date('Y-m-d H:i:s'))."_".$data[$key]['number']."_answer"."_".$data[$key]['id'];
    $data[$key]['recfile']=explode(" ",php_uname());
    $data_originate = array(
        "Channel" => "local/" . $data[$key]['number'] . "@".$config['context'],
        "Exten" => "s",
        "CallerID" => $config['CallerID'],
        "Context" => $config['recordcontext'],
        "ActionId" =>(microtime(true)+$i),
        "Variable" => array(
            "__recordfile" => $data[$key]['recordfile'],
            "__recordfile2" => $data[$key]['recordfile2']
        )

    );
    $data[$key]['originate'] = $ami->Originate($data_originate);
    $i++;
    //logger($data[$action]['originate'], "", $config['debug']);
}


        $process = array();
    $action=0;
    print_r($data);
    //die;
        while (true) {
            if (isset($data[$action]['number'])){

                //$task = $db->select("`id`,`number` from `processing` where  `checkstart`=1 and `status`=0 and `routename`='".$routename."'",0);
                //print_r($task);
                //die;
                //logger($task,'',$config['debug']);
                logger("Number found " . $data[$action]['number'], $data[$action]['number'], $config['debug']);

                fputs($socket, $data[$action]['originate']);
                $log->debug($data[$action]['originate']);


                $process['$number'] = array("Channel" => "");
                $db->update("processing","timestart,".microtime(true),"id=".$data[$action]['id']);
                $data[$key]['Uniqued']= stripos($data[$key]['recfile'][1],"t.al");
                $state="create";
                $action ++;
                //if($action>2) die;




            }  else {
                if(sizeof($data)==0) {
                    logger("Number for call not found", "", $config['debug']);
                    break;
                }
            }
            $dataevent = fgets($socket);
            //echo $data;
            if ($dataevent == "\r\n") {
                $evar = $ami->AmiToArray($event);
                if (isset($evar['Response'])) {
                    $log->debug($evar);
                    //logger(print_r($evar,true), "Response", $config['debug']);
                }
                if (isset($evar['Event'])) {
/*
                    switch ($evar['Event']) {
                        case "AGIExec":
                            break;

                        default:
                            foreach ($task['Hangup'] as $key => $value) {
                                if ($evar['Uniqueid'] === $value['Uniqueid']) {
                                    logger(print_r($evar, true), $task['number'], $config['debug']);
                                }
                                if (stripos($evar['Channel'], $task['number']) !== false) {
                                    logger(print_r($evar, true), $task['number'], $config['debug']);
                                    break;
                                }
                            }
                    }
*/
                    $needbreak=false;
                    if(isset($evar['Channel'])){
                        $evar['Channel']=$ami->GetChannel($evar['Channel']);
                    }
                    foreach ($data as $key => $task) {

                        switch ($evar['Event']) {
                            case "Newstate":
                                //print_r($evar);
                                switch ($evar['ChannelStateDesc']) {
                                    case "Up":
                                        //if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                        if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set UP ******";
                                            $db->update("processing", "timeup," . microtime(true), "id=" . $data[$key]['id']);
                                            $state = "Up";
                                            $needbreak=true;
                                        }
                                        if(isset($data[$key]['Hangup'])){
                                        foreach ($data[$key]['Hangup'] as $key1 => $value) {
                                            if ($evar['Uniqueid'] === $value['Uniqueid']) {
                                                $data[$key]['Hangup'][$key1]['Ringing'] = microtime(true);
                                                // logger(print_r($evar,true),"",$config['debug']);
                                                logger(print_r($task, true), $data[$key]['number'], $config['debug']);
                                            }
                                        }
                                        }




                                        break;
                                    case "Down":
                                        //if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                            if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set DOWN ******";
                                            $db->update("processing", "timeringing," . microtime(true), "id=" . $data[$key]['id']);
                                            $needbreak=true;
                                        }
                                        break;
                                    case "Ringing":
                                       // if (stripos($evar['Channel'], $data[$key]['number']) !== false) {

                                            if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set RINGING ******";
                                            //$db->update("processing", "timeringing," . microtime(true), "id=" . $data[$key]['id']);
                                            $state = "Ringing";
                                        }
                                        foreach ($data[$key]['Hangup'] as $key1 => $value) {
                                            if ($evar['Uniqueid'] === $value['Uniqueid']) {
                                                $db->update("processing", "timeringing," . microtime(true), "id=" . $data[$key]['id']);
                                                $data[$key]['Hangup'][$key1]['Ringing'] = microtime(true);
                                                //logger(print_r($evar,true),"",$config['debug']);
                                                logger(print_r($task, true), $data[$key]['number'], $config['debug']);
                                                $needbreak=true;
                                                break;
                                            }
                                        }
                                        break;
                                    case "Ring":
                                        //if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                        if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set RING ******";
                                            $db->update("processing", "timering," . microtime(true), "id=" . $data[$key]['id']);
                                            $needbreak=true;

                                        }

                                        break;
                                    case "Busy":

                                        break;

                                }
                                break;
                            case "Newchannel":
                                //print_r($evar);
                                switch ($evar['ChannelStateDesc']) {
                                    case "Up":
                                       // if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                        if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set UP ******";
                                            $db->update("processing", "timeup," . microtime(true), "id=" . $data[$key]['id']);
                                            $state = "Up";
                                            $needbreak=true;
                                        }

                                        break;
                                    case "Down":
                                        /*
                                         * 2015-06-23 13:21:18 checker.php  Event: Newchannel
                                            2015-06-23 13:21:18 checker.php  Privilege: call,all
                                            2015-06-23 13:21:18 checker.php  Channel: Local/78452674500@managerd-00000059;1
                                            2015-06-23 13:21:18 checker.php  ChannelState: 0
                                            2015-06-23 13:21:18 checker.php  ChannelStateDesc: Down
                                            2015-06-23 13:21:18 checker.php  CallerIDNum:
                                            2015-06-23 13:21:18 checker.php  CallerIDName:
                                            2015-06-23 13:21:18 checker.php  AccountCode:
                                            2015-06-23 13:21:18 checker.php  Exten: 78452674500
                                            2015-06-23 13:21:18 checker.php  Context: managerd
                                            2015-06-23 13:21:18 checker.php  Uniqueid: 1435054878.2858473

    */
                                        if (!isset($data[$key]['Channel']) && stripos($evar['Channel'], $data[$key]['number']) !== false) {//$evar['Exten']==$data[$key]['number']){
                                            $data[$key]['Uniqueid'] = $evar['Uniqueid'];
                                            $data[$key]['Hangup'][0]['Uniqueid'] = $evar['Uniqueid'];
                                            $data[$key]['Hangup'][0]['Exist'] = 0;
                                            $data[$key]['Channel'] =$ami->GetChannel($evar);
                                            $db->update("processing", "channel," . $data[$key]['Channel'], "id=" . $data[$key]['id']." AND `channel` IS NULL LIMIT 1");
                                            // logger(print_r($evar,true),"",$config['debug']);
                                            logger(print_r($data[$key], true), $data[$key]['number'], $config['debug']);
                                            $needbreak=true;
                                        }

                                        break;
                                    case "Ringing":
                                       // if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                            if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set RINGING ******";
                                            $t = microtime(true);
                                            $db->update("processing", "timeringing," . $t, "id=" . $data[$key]['id']);
                                            $state = "Ringing";
                                            if ($data[$key]['Uniqued'] === false) {
                                                $t = $t + 0.5;
                                                $db->update("processing", "timeringing," . $t, "id=" . $data[$key]['id']);
                                            }
                                            logger("**** set ringing " . $t, "", $config['debug']);
                                            logger(print_r($task, true), $data[$key]['number'], $config['debug']);
                                                $needbreak=true;
                                        }
                                        break;
                                    case "Ring":
                                       // if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                        if ($evar['Channel'] == $data[$key]['Channel']) {
                                            $state = "Ring";
                                            $data[$key]['Hangup'][1]['Uniqueid'] = $evar['Uniqueid'];
                                            $data[$key]['Hangup'][1]['Exist'] = 0;
                                            //  logger(print_r($evar,true),"",$config['debug']);
                                            logger(print_r($task, true), $data[$key]['number'], $config['debug']);
                                            $needbreak=true;
                                            //die;

                                        }
                                        //if ($evar['Exten']==$data[$key]['number']){
                                        //    $data[$key]['Uniqueid']=$evar['Uniqueid'];
                                        //}
                                        break;
                                }
                                break;
                            case "Hangup":
                                //logger(print_r($evar,true),"",$config['debug']);

                                foreach ($data[$key]['Hangup'] as $key1 => $value) {
                                    if ($evar['Uniqueid'] === $value['Uniqueid']) {
                                        $data[$key]['Hangup'][$key1]['Exist'] = 1;
                                        $data[$key]['Hangup'][$key1]['Cause'] = $evar['Cause'];
                                        $data[$key]['Hangup'][$key1]['Cause-txt'] = $evar['Cause-txt'];
                                        //  logger(print_r($evar,true),"",$config['debug']);
                                        //logger(print_r($task, true), $data[$key]['number'], $config['debug']);

                                    }
                                }
                                if ($data[$key]['Hangup'][1]['Exist'] + $data[$key]['Hangup'][0]['Exist'] == 2) {
                                    if ($data[$key]['Hangup'][1]['Cause'] < $data[$key]['Hangup'][0]['Cause']) {
                                        $channelid = 0;
                                    } else {
                                        $channelid = 1;
                                    }
                                    $ar = array(
                                        "timehangup" => microtime(true),
                                        "callstatus" => $data[$key]['Hangup'][$channelid]['Cause'] . "/" . $data[$key]['Hangup'][$channelid]['Cause-txt'],//$evar['Cause']."/".$evar['Cause-txt'],
                                        "status" => 1,
                                        "recordfile" => $config['monitor'] . $data[$key]['recordfile'] . ".wav",
                                        "recordfile2" => $config['monitor'] . $data[$key]['recordfile'] . "-in.wav"

                                    );
                                    $db->update("processing", $ar, "id=" . $data[$key]['id']);
                                    if (isset($data[$key]['Hangup'][2]['Exist'])) {
                                        if ($data[$key]['Hangup'][2]['Exist'] == 1) {
                                            //$needbreak=true;

                                            //$action = false;
                                            if ($data[$key]['Uniqued'] === false) {
                                                $t = $t + 0.5;
                                                $db->update("processing", "timering," . $t, "id=" . $data[$key]['id']);
                                            }
                                            $db->update("processing",'checkstart,1','id='.$data[$key]['id' ]);
                                            logger("1Hangup number ".$data[$key]['number' ], "$data[$key]['number'] ", $config['debug']);
                                            unset($data[$key]);
                                        }
                                    } else {
                                        $db->update("processing",'checkstart,1','id='.$data[$key]['id' ]);
                                        logger("2Hangup number ".$data[$key]['number' ], $data[$key]['number'], $config['debug']);
                                        unset($data[$key]);
                                        //$needbreak=true;
                                        //$action = false;
                                    }
                                    // $action = false;
                                    logger("size of array is -".sizeof($data), "size", $config['debug']);
                                }

                                //die;
                                break;
                            case "PeerStatus":
                                //print_r($evar);
                                break;
                            case "Bridge":
                                //print_r($evar);
                                /*
                                1435085716.714 checker.php  Event: Bridge
                                1435085716.714 checker.php  Privilege: call,all
                                1435085716.714 checker.php  Bridgestate: Link
                                1435085716.714 checker.php  Bridgetype: core
                                1435085716.714 checker.php  Channel1: Local/79878130785@managerd-00000069;2
                                1435085716.714 checker.php  Channel2: SIP/lensol-003df2bb
                                1435085716.714 checker.php  Uniqueid1: 1435085703.4060046
                                1435085716.714 checker.php  Uniqueid2: 1435085703.4060047
                                1435085716.714 checker.php  CallerID1: 8452674500
                                1435085716.714 checker.php  CallerID2: 79878130785
                                */
                                if (stripos($evar['Channel1'], $data[$key]['number']) !== false) {
                                    //$data[$key]['Hangup'][2]['Uniqueid'] = $evar['Uniqueid2'];
                                    //$data[$key]['Hangup'][2]['Exist'] = 0;
                                    //logger(print_r($evar,true),"",$config['debug']);
                                    logger(print_r($data[$key], true), $data[$key]['number'], $config['debug']);
                                }
                                break;
                            case "Dial":
                                /*
                                Array
        2015-06-23 15:00:26 checker.php  (
        2015-06-23 15:00:26 checker.php      [Event] => Dial
        2015-06-23 15:00:26 checker.php      [Privilege] => call,all
        2015-06-23 15:00:26 checker.php      [SubEvent] => Begin
        2015-06-23 15:00:26 checker.php      [Channel] => Local/79878130785@managerd-0000005f;2
        2015-06-23 15:00:26 checker.php      [Destination] => SIP/lensol-002ca644
        2015-06-23 15:00:26 checker.php      [CallerIDNum] => 8452674500
        2015-06-23 15:00:26 checker.php      [CallerIDName] => <unknown>
        2015-06-23 15:00:26 checker.php      [ConnectedLineNum] => 8452674500
        2015-06-23 15:00:26 checker.php      [ConnectedLineName] => <unknown>
        2015-06-23 15:00:26 checker.php      [UniqueID] => 1435060826.2926339
        2015-06-23 15:00:26 checker.php      [DestUniqueID] => 1435060826.2926340
        2015-06-23 15:00:26 checker.php      [Dialstring] => lensol/99979878130785
        2015-06-23 15:00:26 checker.php  )
                                */
                                if ($evar['SubEvent'] == "Begin") {
                                    foreach ($data[$key]['Hangup'] as $key1 => $value) {
                                        if ($evar['UniqueID'] === $value['Uniqueid']) {
                                            $t = microtime(true);
                                            $db->update("processing", "timering," . $t, "id=" . $data[$key]['id']);
                                            $data[$key]['Hangup'][2]['Uniqueid'] = $evar['DestUniqueID'];
                                            $data[$key]['Hangup'][2]['Exist'] = 0;
                                            logger("*** Set time " . $t, "", $config['debug']);

                                            //logger(print_r($evar,true),"",$config['debug']);
                                            logger(print_r($data[$key1], true), $config['debug']);
                                        }
                                    }
                                }

                                break;

                            default:
                                //print_r($evar);
                        }
                        if($needbreak){
                            //$needbreak=false;
                            //die;
                            break;
                        }

                    }
                }
                $event = "";
            }
            $event .= $dataevent;
            $last = $dataevent;
        }
    //}

    fclose($socket);
    $db= new db($config);
    $channels=$db->select("`channel` from `processing` where `routename`='".$routename."'");
    echo $db->query->last."\n";
    foreach($channels as $key=>$value){
        $logdata=logdata($value);
        $txt="";
        foreach($logdata as $key1=>$value1){
            $txt.=$value1."\n";
        }
        $txt=addslashes($txt);
        $db->update("processing",array('logdata'=>$txt),"`routename`='".$routename."' and `channel`='".$value."'");
        echo $db->query->last."\n";

    }
    echo "end";

}    //echo $data;
function logdata($chanel){
    $r=exec("grep \"".$chanel."\" /var/log/asterisk/full",$logdata);
    return $logdata;
}
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
    $log = new Log($GLOBALS['config']);
    $td=microtime(true);//date('Y-m-d H:i:s');
    if($id=="") {
        $scriptname = "checker.php";
    }
    else{
        $scriptname="";
    }

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
       //file_put_contents($file, $data, FILE_APPEND);
        $log->debug($data);
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
