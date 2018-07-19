#!/usr/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 20.05.15
 * Time: 14:51
 */
ini_set('default_charset', 'utf-8');
date_default_timezone_set("Europe/Moscow");
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
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
    'dialcontext'=>"manager",
    'CallerID'=>"74951674500",
    'log_file' => "/var/www/html/dialmanager/core/checker.log",
    'log_write' => "file",
    //'log_level' => "a"

);
$configami= array(
    'log_file' => "/var/www/html/dialmanager/core/ami_checker.log",
    'log_write' => "file",
);
include('mysqli.php');
include('ami.php');
include('log.php');
$routename=$argv[1];
$log = new Log($config);
$amilog = new Log($configami);
$ami=new Ami();
$db= new db($config);


$log->SetGlobalIndex($routename);

$errno="";
$errstr="";
//$call=array("1"=>"iax2/1111@from-internal");
//$data=array("1","2","3","4");
$log->debug($config);
    $log->info("Checker start for route ".$routename);
$query="`id`,`number` from `processing` where `checkstart` = 0 and `routename` like '".$routename."' AND `number` <>''";


$data=$db->select($query, 1);
//logger($db->query->last,"query",$config['debug']);
//logger($data,'data from tables',$config['debug']);
if(!is_array($data)){
    $log->info("Have no data from tables, process will die !!!!!!!!!");
    die;
}
//logger($db->query->last);

$i=0;
$numberforami= array();
foreach($data as $key=>$value){
    $data[$key]['recordfile']=strtolower($routename)."_".str_replace(array("-",":"," "),"_",date('Y-m-d H:i:s'))."_".$data[$key]['number']."_".$data[$key]['id'];
    $data[$key]['recordfile2']=str_replace(array("-",":"," "),"_",date('Y-m-d H:i:s'))."_".$data[$key]['number']."_answer"."_".$data[$key]['id'];
    $data[$key]['recfile']=explode(" ",php_uname());

    //$actionid=$value['id'].$value['number'];
    $data[$key]['actionid']=$value['id'].$value['number'];
    $param=array(
        "actionid"=> $data[$key]['actionid'],
        "recordfile" => $config['monitor'] . $data[$key]['recordfile'] . ".wav",
        "recordfile2" => $config['monitor'] . $data[$key]['recordfile'] . "-in.wav"
    );
    $db->update("processing",$param,"`id`='".$value['id']."' and `number`='".$value['number']."'");
    $log->debug($db->query->last,"query93");
    $data_originate = array(
        "Channel" => "local/" . $data[$key]['number'] . "@".$config['context'],
        "Exten" => "s",
        "CallerID" => $config['CallerID'],
        "Context" => $config['recordcontext'],
        "ActionId" => $data[$key]['actionid'],
        "Variable" => array(
            "__recordfile" => $data[$key]['recordfile'],
            "__recordfile2" => $data[$key]['recordfile2']
        )

    );
    $data[$key]['originate'] = $ami->Originate($data_originate);
    $i++;
    $log->info("current ".$i,"query94");
    $numberforami[$key]['number']= $data[$key]['number'];
    $numberforami[$key]['actionid']=$value['id'].$value['number'];
    //logger($data[$action]['originate'], "", $config['debug']);
}

$socket = fsockopen($config['manager_host'],$config['manager_port'], $errno, $errstr, 10);
$log->info($socket,"socket");


if (!$socket){
    echo "$errstr ($errno)\n";
    $log->error( "$errstr ($errno)");
    die;
}
else{
    $log->info("start main module");
	date_default_timezone_set('Europe/Moscow');

    $login_data=array(
        "UserName"=>$config['manager_login'],
        "Secret"=>$config['manager_password']
    );
    $login=$ami->Login($login_data);
    $log->info($login,"Authentication");

    fputs($socket, $login);
    $access=true;
    $data1="";
    while ($access) {
        $data1 = fgets($socket);
        if ($data1 == "\r\n") {
            $evar = $ami->AmiToArray($event);
            if (isset($evar['Response'])) {
                switch ($evar['Response']) {
                    case "Success":
                        $log->debug(print_r($evar,true),"ResponseAuthenticationSuccess");
                        $access=false;
                        break;
                    case "Error":
                        $log->debug(print_r($evar,true),"ResponseAuthenticationError");
                        if($evar['Message']=="Authentication failed"){
                            $log->error("Authentication failed","ResponseAuthentication");
                            die;
                        }
                        break;

                }


            }

        }
        $event .= $data1;
        $last = $data1;
    }

    $event="";
    $action=false;
    //logger("start","",$config['debug']);

    $state="";
    $response = array(
        1 => "Other end has hungup",
        2 => "Local ring",
        3 => "Remote end is ringing",
        4 => "Remote end has answered",
        5 => "Remote end is busy",
        6 => "Make it go off hook",
        7 => "Line is off hook",
        8 => "Congestion (circuits busy)",
    );
    //die;
    //foreach($data as $key=>$value) {

    $log->info("start sdp monitor");
    system("/usr/bin/php -f /var/www/html/dialmanager/core/sdp_check.php ".$routename." >> /var/log/checker.log & 2>/dev/null");
    sleep(2);
    $log->info("start sdp monitor done");


    $process = array();
    $action=0;
    //print_r($data);
    //die;
    $log->info("Start main proccess");
    $oldtime=microtime();
    $break=0;
    $eventcount['all']=0;
    $eventcount['OriginateResponse']=0;
    $nextnumb=true;
    $eventoperation=array();
        while (true) {
            $ttime=microtime(true);
            if($ttime-5>$oldtime){
                $oldtime=$ttime;
                $log->info("Checker online all - [".$eventcount['all']."] Originate - [".$eventcount['OriginateResponse']."] ArrayCount - [".count($data)."]","status");
                //$log->info("Connection status ".print_r(stream_get_meta_data( $socket),true),"socketstatus");
                $log->info("Current Action - [".$action."] number - [".$data[$action]['number']."]","status");
                $log->debug("nextnumb - [".$nextnumb."]","status");
                $query="`id`,`number` from `processing` where `checkstart` = 0 and `routename` like '".$routename."' AND `number` <>''";
                $data1=$db->select($query, 1);
                if(!is_array($data1)){
                    $log->info("Have no data from tables, process will die !!!!!!!!!");
                    $break++;
                    if($break>2)break;
                }
            }
            if (count($data) >0 && $nextnumb){
                foreach($data as $ackey=>$acdata) {
                    if (!isset($acdata['execute'])) {

                        $action = $ackey;


                        $log->info("Number found " . $data[$action]['number']);

                        fputs($socket, $data[$action]['originate']);
                        $log->debug($data[$action]['originate'], "originateqwe");
                        $process['$number'] = array("Channel" => "");
                        $db->update("processing", "timestart," . $ttime, "id=" . $data[$action]['id']);
                        $data[$key]['Uniqued'] = stripos($data[$key]['recfile'][1], "t.al");
                        $state = "create";
                        //$action++;
                        $log->info("Next action " . $action, "line211");
                        $nextnumb = false;
                        $data[$action]['execute']=true;
                        break;
                    }
                }
            }  else {
                if(sizeof($data)==0) {
                    $log->info("Number for call not found","End process");
                    $log->info("Checker online all - [".$eventcount['all']."] Originate - [".$eventcount['OriginateResponse']."] ArrayCount - [".count($data)."]","End process");
                    break;
                }
            }
            $dataevent = fgets($socket);
            //echo $data;
            if ($dataevent == "\r\n") {
                $eventcount['all']++;
                $evar = $ami->AmiToArray($event);


                $br=false;
                foreach($numberforami as $key=>$checknumber){

                    if(stripos($evar['ActionID'], $checknumber['actionid']) !== false){
/*
 * 2015-11-27 12:21:35 TEST debug[nextnumber] Array
2015-11-27 12:21:35 TEST debug[nextnumber] (
2015-11-27 12:21:35 TEST debug[nextnumber]     [Response] => Success
2015-11-27 12:21:35 TEST debug[nextnumber]     [ActionID] => 3000278452227107
2015-11-27 12:21:35 TEST debug[nextnumber]     [Message] => Originate successfully queued
2015-11-27 12:21:35 TEST debug[nextnumber] )
2015-11-27 12:21:35 TEST debug[nextnumber]

 */
                        $nextnumb=true;

                    }

                    if(stripos($evar['Channel'], $checknumber['number']) !== false){
                        $amilog->debug($evar, $checknumber['number']);
                        $cdetail =$ami->getchanneldetail($evar['Channel']);
                        if(!isset($eventoperation[$cdetail['channelmain']])) {

                            switch ($evar['Event']) {
                                case "Newchannel":
                                    if ($evar['ChannelStateDesc'] == "Down") {
                                        $amilog->debug($evar, "NewchannelDown" . $evar['Exten']);
                                        $createdchannel = $ami->getchanneldetail($evar['Channel']);
                                        $db->update("processing", "channel," . $createdchannel['channelfull'], "`routename`='" . $routename . "' AND `number`='" . $createdchannel['number'] . "' AND `channel` IS NULL LIMIT 1");
                                        $amilog->debug($db->query->last, "NewchannelDown" . $evar['Exten']);
                                        $br = true;
                                        $nextnumb=true;
                                    }
                                    break;
                            }
                        }
                        if($evar['Event']=="Rename"){
//смена канала
/*
* [Rename] => Array
(
[Event] => Rename
[Privilege] => call,all
[Timestamp] => 1448617593.135729
[Channel] => Local/78452507718@checker-0000088e;1
[Newname] => SIP/astersar-0051c64c
[Uniqueid] => 1448617587.5363560
)
*/
                            $eventoperation[$cdetail['channelmain']]['external']['name']=$evar['Newname'];
                        }
                        $eventoperation[$cdetail['channelmain']][$cdetail['channelnumber']][$evar['Event']] = $evar;
                        if($br) break;
                    }
                    foreach($eventoperation as $channel=>$channeldata){
                        if(stripos($evar['Channel'], $channeldata['external']['name']) !== false){
                            $eventoperation[$channel]['external'][$evar['Event']]=$evar;
                        }
                    }
                }


                if (isset($evar['Response'])) {
                    //$log->debug($evar,"ResponseFound");
                    foreach($data as $ckey=>$cvalue) {
                        if ($evar['ActionID'] == $data[$ckey]['actionid']) {
                            $nextnumb = true;
                            $log->info("number action=" . $action, "nextnumber");
                            $log->debug($evar,"nextnumber");
                            //$log->debug($data[$action],"nextnumber");
                            $log->info("call next number " . $data[$action]['actionid'], "nextnumber");
                            break;
                        }
                    }

                    //logger(print_r($evar,true), "Response", $config['debug']);
                }
                if (isset($evar['Event'])) {
                    $needbreak=false;
                    if(isset($evar['Channel'])){
                        $evar['Channel']=$ami->GetChannel($evar['Channel']);
                    }
                    foreach ($data as $key => $task) {
                       if( $data[$key]['actionid']==$evar['ActionID']){
                          // $log->debug($evar,$data[$key]['actionid']);
                            //$nextnumb=true;
                        }
                        switch ($evar['Event']) {
                            case "OriginateResponse":

                                //$log->debug("Key ".$key,"OriginateResponse");
                                //$log->debug( $data[$key],"OriginateResponse");

                                if($evar['Response']=="Success" && $data[$key]['actionid']==$evar['ActionID']){
                                    $eventcount['OriginateResponse']++;
                                    $log->debug("Success",$data[$key]['actionid']);
                                    $nextnumb=true;
                                    $breakfor=true;
                                }
                                if($evar['Response']=="Failure" && $data[$key]['actionid']==$evar['ActionID']){
                                    $log->info("found Response failure",$data[$key]['actionid']);

                                    $nextnumb=true;
                                    $breakfor=true;
                                    $logdata=mysql_real_escape_string(trim(print_r($evar,true)));

                                    if($evar['Reason']==8){
                                        $ar = array(
                                            "timehangup" => $evar['Timestamp'],
                                            // "timehangup" => microtime(true),
                                            "callstatus" => $evar['Reason'] ."/".$response[$evar['Reason']],
                                            "status" => 1,
                                            "checkstart" =>1,
                                            "logdata"=>$logdata
                                        );
                                        $db->update("processing", $ar, "`actionid`='" . $data[$key]['actionid']."'");
                                        $log->debug($db->query->last,$data[$key]['actionid']." line262");
                                        unset($data[$key]);
                                        $nextnumb=true;
                                    }

                                        //
                                        //
                                        //$db->update("processing", $ar, "id=" . $data[$key]['id']);
                                    //$data[$key]['Hangup'][2]['Exist']=1;
                                       // $log->debug("unset ".$key." number [".$data[$key]['number']."]","unset");
                                       // unset($data[$key]);
                                    $data[$key]['Hangup'][1]['Exist']=1;
                                    //$nextnumb=true;
                                    break;


                                }
                                break;
                            case "Newstate":
                                //print_r($evar);
                                switch ($evar['ChannelStateDesc']) {
                                    case "Up":
                                        //if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                        if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set UP ******";
                                            //$db->update("processing", "timeup," . microtime(true), "id=" . $data[$key]['id']);
                                            $db->update("processing", "timeup," .$evar['Timestamp'], "`channel`='".$evar['Channel']."'");
                                            //$db->update("processing", "timeup," .$evar['Timestamp'], "id=" . $data[$key]['id']);
                                            $state = "Up";
                                            $needbreak=true;
                                        }
                                        if(isset($data[$key]['Hangup'])){
                                        foreach ($data[$key]['Hangup'] as $key1 => $value) {
                                            if ($evar['Uniqueid'] === $value['Uniqueid']) {
                                                $data[$key]['Hangup'][$key1]['Ringing'] = microtime(true);
                                                // logger(print_r($evar,true),"",$config['debug']);
                                               // logger(print_r($task, true), $data[$key]['number'], $config['debug']);
                                            }
                                        }
                                        }
                                        break;
                                    case "Down":
                                        //if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                            if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set DOWN ******";
                                            //$db->update("processing", "timeringing," . microtime(true), "id=" . $data[$key]['id']);
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
                                                //$db->update("processing", "timeringing," . $evar['Timestamp'], "id=" . $data[$key]['id']);
                                                $db->update("processing", "timeringing," . $evar['Timestamp'], "`channel`='".$evar['Channel']."'");
                                                $log->debug($db->query->last,"checkringing2");
                                                $log->debug($evar,"checkringing2");
                                               // $db->update("processing", "timeringing," . microtime(true), "id=" . $data[$key]['id']);
                                                $data[$key]['Hangup'][$key1]['Ringing'] = $evar['Timestamp'];
                                                //$data[$key]['Hangup'][$key1]['Ringing'] = microtime(true);
                                                //logger(print_r($evar,true),"",$config['debug']);
                                                //logger(print_r($task, true), $data[$key]['number'], $config['debug']);
                                                $needbreak=true;
                                                break;
                                            }
                                        }
                                        break;
                                    case "Ring":
                                        //if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                        if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set RING ******";
                                           // $db->update("processing", "timering," . $evar['Timestamp'], "id=" . $data[$key]['id']);
                                            $db->update("processing", "timering," . $evar['Timestamp'], "`channel`='".$evar['Channel']."'");
                                            $log->debug($db->query->last,"checkring");
                                            $log->debug($evar,"checkring");
                                            //$db->update("processing", "timering," . microtime(true), "id=" . $data[$key]['id']);
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
                                           // $db->update("processing", "timeup," . $evar['Timestamp'], "id=" . $data[$key]['id']);
                                            $db->update("processing", "timeup," . $evar['Timestamp'],"`channel`='".$evar['Channel']."'");
                                            //$db->update("processing", "timeup," . microtime(true), "id=" . $data[$key]['id']);
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
                                            //$db->update("processing", "channel," . $data[$key]['Channel'], "id=" . $data[$key]['id']." AND `channel` IS NULL LIMIT 1");
                                            // logger(print_r($evar,true),"",$config['debug']);
                                            //logger(print_r($data[$key], true), $data[$key]['number'], $config['debug']);
                                            $needbreak=true;
                                        }

                                        break;
                                    case "Ringing":
                                       // if (stripos($evar['Channel'], $data[$key]['number']) !== false) {
                                            if ($evar['Channel'] == $data[$key]['Channel']) {
                                            //echo $evar['Channel'] . " set RINGING ******";
                                            //$t = microtime(true);
                                            //$db->update("processing", "timeringing," .  $evar['Timestamp'], "id=" . $data[$key]['id']);
                                            $db->update("processing", "timeringing," .  $evar['Timestamp'], "`channel`='".$evar['Channel']."'");
                                                $log->debug($db->query->last,"checkringing");
                                                $log->debug($evar,"checkringing");
                                            //$db->update("processing", "timeringing," . $t, "id=" . $data[$key]['id']);
                                            $state = "Ringing";
                                            if ($data[$key]['Uniqued'] === false) {
                                                //$t = $t + 0.5;
                                                $db->update("processing", "timeringing," .  $evar['Timestamp'], "id=" . $data[$key]['id']);
                                            }
                                            //logger("**** set ringing " . $t, "", $config['debug']);
                                            //logger(print_r($task, true), $data[$key]['number'], $config['debug']);
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
                                            //logger(print_r($task, true), $data[$key]['number'], $config['debug']);
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
/*
2015-11-26 12:08:16 debug[784523125485] Array
2015-11-26 12:08:16 debug[784523125485] (
2015-11-26 12:08:16 debug[784523125485]     [Event] => Hangup
2015-11-26 12:08:16 debug[784523125485]     [Privilege] => call,all
2015-11-26 12:08:16 debug[784523125485]     [Timestamp] => 1448528895.914987
2015-11-26 12:08:16 debug[784523125485]     [Channel] => Local/784523125485@checker-000001ff;2
2015-11-26 12:08:16 debug[784523125485]     [Uniqueid] => 1448528895.4646629
2015-11-26 12:08:16 debug[784523125485]     [CallerIDNum] => 74991674500
2015-11-26 12:08:16 debug[784523125485]     [CallerIDName] => <unknown>
2015-11-26 12:08:16 debug[784523125485]     [ConnectedLineNum] => 74991674500
2015-11-26 12:08:16 debug[784523125485]     [ConnectedLineName] => <unknown>
2015-11-26 12:08:16 debug[784523125485]     [AccountCode] =>
2015-11-26 12:08:16 debug[784523125485]     [Cause] => 1
2015-11-26 12:08:16 debug[784523125485]     [Cause-txt] => Unallocated (unassigned) number
2015-11-26 12:08:16 debug[784523125485] )
*/

                                foreach ($data[$key]['Hangup'] as $key1 => $value) {
                                    if ($evar['Uniqueid'] === $value['Uniqueid']) {
                                        $log->debug($value,$data[$key1]['actionid']."Hangup");
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
                                        "timehangup" =>  $evar['Timestamp'],
                                       // "timehangup" => microtime(true),
                                        "callstatus" => $data[$key]['Hangup'][$channelid]['Cause'] . "/" . $data[$key]['Hangup'][$channelid]['Cause-txt'],//$evar['Cause']."/".$evar['Cause-txt'],
                                        "status" => 1,
                                        "recordfile" => $config['monitor'] . $data[$key]['recordfile'] . ".wav",
                                        "recordfile2" => $config['monitor'] . $data[$key]['recordfile'] . "-in.wav"

                                    );
                                    //$db->update("processing", $ar, "id=" . $data[$key]['id']);
                                    $channel=$ami->getchanneldetail($evar['Channel']);
                                    $db->update("processing", $ar,  "`channel`='".$channel['channelfull']."'");
                                    $log->debug($db->query->last,"hangup573");
                                    $log->debug($evar,"hangup573");
                                    if (isset($data[$key]['Hangup'][2]['Exist'])) {
                                        if ($data[$key]['Hangup'][2]['Exist'] == 1) {
                                            //$needbreak=true;

                                            //$action = false;
                                            if ($data[$key]['Uniqued'] === false) {
                                                //$t = $t + 0.5;
                                                $db->update("processing", "timering," . $t, "id=" . $data[$key]['id']);
                                            }
                                            $db->update("processing",'checkstart,1','id='.$data[$key]['id' ]);
                                            //logger("1Hangup number ".$data[$key]['number' ], "$data[$key]['number'] ", $config['debug']);
                                            unset($data[$key]);
                                        }
                                    } else {
                                        $db->update("processing",'checkstart,1','id='.$data[$key]['id' ]);
                                        //logger("2Hangup number ".$data[$key]['number' ], $data[$key]['number'], $config['debug']);
                                        unset($data[$key]);
                                        //$needbreak=true;
                                        //$action = false;
                                    }
                                    // $action = false;
                                    //logger("size of array is -".sizeof($data), "size", $config['debug']);
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
                                    //logger(print_r($data[$key], true), $data[$key]['number'], $config['debug']);
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
                                           // $t = microtime(true);
                                            $t =  $evar['Timestamp'];
                                            $db->update("processing", "timering," . $t, "id=" . $data[$key]['id']);
                                            $data[$key]['Hangup'][2]['Uniqueid'] = $evar['DestUniqueID'];
                                            $data[$key]['Hangup'][2]['Exist'] = 0;
                                            //logger("*** Set time " . $t, "", $config['debug']);

                                            //logger(print_r($evar,true),"",$config['debug']);
                                            //logger(print_r($data[$key1], true), $config['debug']);
                                        }
                                    }
                                }

                                break;

                            default:
                                //print_r($evar);
                        }
                        if($breakfor){
                            $breakfor=false;
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

    $amilog->debug($eventoperation,"eventarray");

    recalculate($db,$eventoperation, $amilog);
    fclose($socket);
    $db= new db($config);
    $log->info("Getting log of calls","logdata");
    $channels=$db->select("`channel` from `processing` where `routename`='".$routename."'");
    //echo $db->query->last."\n";
    $log->info("final work get asteirks data","final");
    $log->debug($db->query->last,"final");
    $log->debug($channels,"tested channels");
    foreach($channels as $key=>$value){
        $log->info("Get asterisk data for ".$value,"final");
        if(trim($value)!=""){
            $logdata=logdata($value);
            $txt="";
            foreach($logdata as $key1=>$value1){
                $txt.=$value1."\n";
            }
            $txt=addslashes($txt);
            $db->update("processing",array('logdata'=>$txt),"`routename`='".$routename."' and `channel`='".$value."'");
        }else{
            $log->error("Channel id empty","final");
        }


        //echo $db->query->last."\n";

    }
   // echo "end";
    $log->info("End of work","logdata");
    $log->info("");
    die;
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
function recalculate($db,$eventoperate,$log){
    foreach($eventoperate as $channel=>$channelgroup){
        if(isset($channelgroup[1]) && isset($channelgroup[2])){

            $ar = array(

                "callstatus" =>  $channelgroup[2]['Hangup']['Cause'] . "/" .$channelgroup[2]['Hangup']['Cause-txt']
            );
            if(isset($channelgroup[2]['Dial']['DialStatus'])){
                switch($channelgroup[2]['Dial']['DialStatus']){
                    case "ANSWER":
                        $ar['timeup'] = $channelgroup[2]['Dial']['Timestamp'];
                        break;
                }

            }
            if(isset($channelgroup['external'])){
                $ar['timehangup']=$channelgroup['external']['Hangup']['Timestamp'];
            }else{
                $ar['timehangup']=$channelgroup[2]['Hangup']['Timestamp'];
            }
            $db->update("processing", $ar, "`channel`='Local/" . $channel."'");
            $log->debug($db->query->last,"recalculate");
        }
    }
}
?>
