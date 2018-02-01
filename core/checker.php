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
    'debug'=>false, //if true log will show to desktop
    'monitor'=>"/var/spool/asterisk/monitor/",
    'context'=> "checker", //"managerd",
    'recordcontext' => "cwc_playwa",
#    'CallerID'=>"74951674500"
    'CallerID'=>"48786070906"
);
include('mysqli.php');
include('ami.php');
    ini_set('default_charset', 'utf-8');
$errno="";
$errstr="";
//$call=array("1"=>"iax2/1111@from-internal");
//$data=array("1","2","3","4");
$db= new db($config);
$routename=$argv[1];
system("/usr/bin/php -f /var/www/html/dialmanager/core/sdp_check.php ".$routename." >> /var/log/checker.log & 2>/dev/null");
sleep(2);
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
    $query="`id`,`number` from `processing` where `checkstart` = 0 and `routename` like '".$routename."'";
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
        $db->update("processing",'checkstart,1','id='.$value['id'] );
    }

    logger($db->query->last);
    $state="";
    //die;
    //foreach($data as $key=>$value) {
        $process = array();
        while (true) {
            if (!$action) {
                $task = $db->select("`id`,`number` from `processing` where  `checkstart`=1 and `status`=0 and `routename`='".$routename."'",0);
                logger($task,'',$config['debug']);

                if (is_array($task) ) {
                    logger("Number found " . $task['number'], $task['number'], $config['debug']);
                    $task['recordfile']=str_replace(array("-",":"," "),"_",date('Y-m-d H:i:s'))."_".$task['number'];
                    $task['recfile']=explode(" ",php_uname());
                    $data_originate = array(
                        "Channel" => "local/" . $task['number'] . "@".$config['context'],
                        "Exten" => "s",
                        "CallerID" => $config['CallerID'],
                        "Context" => $config['recordcontext'],
                        "Variable" => array(
                            "__recordfile" => $task['recordfile']
                        )

                    );
                    $originate = $ami->Originate($data_originate);
                    logger($originate, "", $config['debug']);
                    fputs($socket, $originate);
                    $action = true;
                    $process['$number'] = array("Channel" => "");
                    $db->update("processing","timestart,".microtime(true),"id=".$task['id']);
                    $task['Uniqued']= stripos($task['recfile'][1],"t.al");
                    $state="create";
                } else {
                    logger("Number for call not found", $task['number'], $config['debug']);
                    die;
                }
            }
            $data = fgets($socket);
            //echo $data;
            if ($data == "\r\n") {
                $evar = $ami->AmiToArray($event);
                if (isset($evar['Event'])) {

                        switch($evar['Event']) {
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
                    switch ($evar['Event']) {
                        case "Newstate":
                            //print_r($evar);
                            switch ($evar['ChannelStateDesc']) {
                                case "Up":
                                    if(stripos($evar['Channel'],$task['number'])!==false) {
                                        //echo $evar['Channel'] . " set UP ******";
                                        $db->update("processing", "timeup," . microtime(true), "id=" . $task['id']);
                                        $state="Up";
                                    }
                                    foreach($task['Hangup'] as $key=> $value) {
                                        if( $evar['Uniqueid']===$value['Uniqueid']) {
                                            $task['Hangup'][$key]['Ringing']=microtime(true);
                                           // logger(print_r($evar,true),"",$config['debug']);
                                            logger(print_r($task,true),$task['number'],$config['debug']);
                                        }
                                    }

                                    break;
                                case "Down":
                                    if(stripos($evar['Channel'],$task['number'])!==false) {
                                        //echo $evar['Channel'] . " set DOWN ******";
                                        $db->update("processing", "timeringing," . microtime(true), "id=" . $task['id']);
                                    }
                                    break;
                                case "Ringing":
                                    if(stripos($evar['Channel'],$task['number'])!==false) {
                                        //echo $evar['Channel'] . " set RINGING ******";
                                        //$db->update("processing", "timeringing," . microtime(true), "id=" . $task['id']);
                                        $state="Ringing";
                                    }
                                    foreach($task['Hangup'] as $key=> $value) {
                                        if( $evar['Uniqueid']===$value['Uniqueid']) {
                                            $db->update("processing", "timeringing," . microtime(true), "id=" . $task['id']);
                                            $task['Hangup'][$key]['Ringing']=microtime(true);
                                            //logger(print_r($evar,true),"",$config['debug']);
                                            logger(print_r($task,true),$task['number'],$config['debug']);
                                        }
                                    }
                                    break;
                                case "Ring":
                                    if(stripos($evar['Channel'],$task['number'])!==false) {
                                        //echo $evar['Channel'] . " set RING ******";
                                        $db->update("processing", "timering," . microtime(true), "id=" . $task['id']);
                                        
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
                                    if(stripos($evar['Channel'],$task['number'])!==false) {
                                        //echo $evar['Channel'] . " set UP ******";
                                        $db->update("processing", "timeup," . microtime(true), "id=" . $task['id']);
                                        $state="Up";
                                    }

                                    break;
                                case "Down":
                                    if(stripos($evar['Channel'],"Local/".$task['number']."@from-internal")!==false) {
                                        //echo $evar['Channel'] . " set DOWN ******";
                                       $task['Uniqueid']= $evar['Uniqueid'];
                                        $task['Hangup'][0]['Uniqueid']=$evar['Uniqueid'];
                                        $task['Hangup'][0]['Exist']=0;

                                        $db->update("processing","channel,".$ami->GetChannel($evar),"id=".$task['id']);
                                    }
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
                                    if (stripos($evar['Channel'],$task['number'])!==false){//$evar['Exten']==$task['number']){
                                        $task['Uniqueid']=$evar['Uniqueid'];
                                        $task['Hangup'][0]['Uniqueid']=$evar['Uniqueid'];
                                        $task['Hangup'][0]['Exist']=0;
                                        $db->update("processing","channel,".$ami->GetChannel($evar),"id=".$task['id']);
                                       // logger(print_r($evar,true),"",$config['debug']);
                                        logger(print_r($task,true),$task['number'],$config['debug']);
                                    }

                                    break;
                                case "Ringing":
                                    if(stripos($evar['Channel'],$task['number'])!==false) {
                                        //echo $evar['Channel'] . " set RINGING ******";
                                        $t= microtime(true);
                                        $db->update("processing", "timeringing," .$t, "id=" . $task['id']);
                                        $state="Ringing";
                                        if($task['Uniqued']===false){
                                            $t=$t+0.5;
                                            $db->update("processing", "timeringing," .$t, "id=" . $task['id']);
                                        }
                                       logger("**** set ringing ".$t,"",$config['debug']);
                                        logger(print_r($task,true),$task['number'],$config['debug']);
                                    }
                                    break;
                                case "Ring":
                                    if(stripos($evar['Channel'],$task['number'])!==false) {
                                        //echo $evar['Channel'] . " set RING ******";
                                        //$db->update("processing", "timering," . microtime(true), "id=" . $task['id']);
                                        $state="Ring";
                                        $task['Hangup'][1]['Uniqueid']=$evar['Uniqueid'];
                                        $task['Hangup'][1]['Exist']=0;
                                      //  logger(print_r($evar,true),"",$config['debug']);
                                        logger(print_r($task,true),$task['number'],$config['debug']);
                                        //die;

                                    }
                                    //if ($evar['Exten']==$task['number']){
                                    //    $task['Uniqueid']=$evar['Uniqueid'];
                                    //}
                                    break;
                            }
                            break;
                        case "Hangup":
                           //logger(print_r($evar,true),"",$config['debug']);

                            foreach($task['Hangup'] as $key=> $value) {
                                if( $evar['Uniqueid']===$value['Uniqueid']) {
                                    $task['Hangup'][$key]['Exist']=1;
                                    $task['Hangup'][$key]['Cause'] = $evar['Cause'];
                                    $task['Hangup'][$key]['Cause-txt'] = $evar['Cause-txt'];
                                  //  logger(print_r($evar,true),"",$config['debug']);
                                    logger(print_r($task,true),$task['number'],$config['debug']);

                                }
                            }
                            if( $task['Hangup'][1]['Exist']+ $task['Hangup'][0]['Exist']==2){
                                if($task['Hangup'][1]['Cause']<$task['Hangup'][0]['Cause']){
                                    $channelid=0;
                                }else{
                                    $channelid=1;
                                }
                                $ar = array(
                                    "timehangup" => microtime(true),
                                    "callstatus" => $task['Hangup'][$channelid]['Cause']."/".$task['Hangup'][$channelid]['Cause-txt'],//$evar['Cause']."/".$evar['Cause-txt'],
                                    "status" => 1,
                                    "recordfile" => $config['monitor'] . $task['recordfile'] . ".wav"
                                );
                                $db->update("processing", $ar, "id=" . $task['id']);
                                if(isset($task['Hangup'][2]['Exist'])){
                                    if($task['Hangup'][2]['Exist']==1){
                                        $action = false;
                                        if($task['Uniqued']===false){
                                            $t=$t+0.5;
                                            $db->update("processing", "timering," .$t, "id=" . $task['id']);
                                        }
                                    }
                                }else{$action = false;}
                               // $action = false;
                                logger(print_r($task,true),$task['number'],$config['debug']);
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
                            if(stripos($evar['Channel1'],$task['number'])!==false) {
                                //$task['Hangup'][2]['Uniqueid'] = $evar['Uniqueid2'];
                                //$task['Hangup'][2]['Exist'] = 0;
                                //logger(print_r($evar,true),"",$config['debug']);
                                logger(print_r($task,true),$task['number'],$config['debug']);
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
                            if($evar['SubEvent']=="Begin"){
                                foreach($task['Hangup'] as $key=> $value) {
                                    if( $evar['UniqueID']===$value['Uniqueid']) {
                                        $t=microtime(true);
                                        $db->update("processing", "timering," . $t, "id=" . $task['id']);
                                        $task['Hangup'][2]['Uniqueid'] = $evar['DestUniqueID'];
                                        $task['Hangup'][2]['Exist'] = 0;
                                        logger("*** Set time ".$t,"",$config['debug']);

                                        //logger(print_r($evar,true),"",$config['debug']);
                                        logger(print_r($task,true),$config['debug']);
                                    }
                                }
                            }

                            break;

                        default:
                            //print_r($evar);
                    }
                }
                $event = "";
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
       // file_put_contents($file, $data, FILE_APPEND);
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
