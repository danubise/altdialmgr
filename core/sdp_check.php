#!/bin/php
<?php
    $config = array(
        'asterisk_logfile' => "/var/log/asterisk/full",
        'logfile'=>"/var/log/asterisk/sdp_checker.log",
        'debug'=>true, //if true log will show to desktop, false write to file
        'monitor'=>"/var/spool/asterisk/monitor/",
        'context'=> "managerd",
        'recordcontext' => "cwc_playwa",
        'CallerID'=>"7451674500",
        'log_file' => "/var/log/asterisk/sdp_checker.log",
        'log_write' => "file",
        'maxworktime' => 400,
    );
date_default_timezone_set("Europe/Samara");
ini_set('default_charset', 'utf-8');
include ('/var/www/html/dialmanager/internal_config.php');
include('mysqli.php');
include('ami.php');
include('log.php');
include('Parcer.php');
$parcer= new Parcer();
$log = new Log($config);
$ami= new Ami();
$routemd5hash=$argv[1];
$log->SetGlobalIndex("SDP");
$log->info("Start with md5hash ".$routemd5hash);
$log->debug($config);
$log->debug($_config);

$db= new db($_config['mysql']);

$task = $db->select("`id`,`number` from `processing` where  `md5hash`='".$routemd5hash."' AND `number` <> ''",1);
$countOfTasks = sizeof( $task);
$log->debug($db->query->last);
$log->debug($task);
$log->debug("Count of the tasks:". $countOfTasks);
$stop=0;

$size = filesize($config['asterisk_logfile'])-10;
$log->info( "start size = ".$size."\n");
$time1=microtime(true);
$starttime=$time1-50;
$maxworktime= $countOfTasks * $config['maxworktime'];
$log->debug("Start time :".$starttime);
$log->debug("Max working time :".$maxworktime);
$stop=array();

$finalquery="";
$resultarray=array();
$progressok=array();
$oldtime=microtime(true);
while (true) {
    $timecurrent=microtime(true);
    clearstatcache();
    while(true) {
        clearstatcache();
        $currentSize = filesize($config['asterisk_logfile']);

        if ($size < $currentSize) {
            //echo $currentSize."\n";
            break;
        }
        usleep(1000);

    }
    if($timecurrent-5>$oldtime){
        $oldtime=$timecurrent;
        $log->info("SDP online","status");
        $query="`id`,`number` from `processing` where `checkstart` = 0 and `md5hash`='".$routemd5hash."' AND `number` <>''";
        $data1=$db->select($query, 1);
        $log->debug($db->query->last);
        if(!is_array($data1)){
            $log->info("Have no data from tables, process will die !!!!!!!!!","SDP");
            $break++;
            if($break>2)break;
        }
    }


    $fh = fopen($config['asterisk_logfile'], "r");
    fseek($fh, $size);
//echo $size;
    $size=$currentSize;
    while ($d = fgets($fh)) {
       $a= explode("\n",$d);
        foreach($a as $key=>$value) {
            //[2018-07-19 13:47:46] VERBOSE[5704][C-0004405a] app_dial.c: SIP/stelton-0008cc26 is making progress passing it to SIP/bpot.251-0008cc24
            $pos = strripos($value, "is making progress passing it to");
            if ($pos === false) {
                ;
            }else{
//echo $value;
                $value1 = explode("app_dial.c: ",$value);
                //SIP/stelton-0008cc26 is making progress passing it to SIP/bpot.251-0008cc24
                $t2=explode("is making progress passing it to",$value1[1]);
                $log->debug( $t2,"SDP112");
                $pos2 = strripos($value, "Local");
                if($pos2 === false){
                    ;
                }else{
                    //echo $value."\n";
                }
                foreach($task as $key => $value2){
                    $pos = strripos($t2[1], $value2['number']);
                    //echo $value['number']."\n";
                    if($pos === false){
                        ;
                    }
                    else{

                        $log->info( "number = ".$value,"SDP");
                        $chanid=$ami->GetChannel($t2[1]);
                        if(!isset($stop[$chanid])) {
                            //$db->update("processing", "progress," . microtime(true), "id=" . $value2['id']);
                            $log->info( "AMI channel =".$ami->GetChannel($t2[1]),"SDP_channel");
                            //$eventtime=microtime(true);
                            $log->debug( $value,"SDP133");
                            $progressline = $parcer->setlogline($value);
                            $log->debug($progressline,"SDP_progresslist135");
                            $eventtime=$progressline['unixtime'];
                            $eventtime=time();
                            $query = "UPDATE  `processing` SET  `progress` =  '".$eventtime."' WHERE  `channel` LIKE '".$chanid."';";
                            $log->debug( $query,"SDP117");
                            $db->query($query);
                            if(!isset($progressok[$chanid])) {
                                $resultarray[$chanid] = $eventtime;
                                $progressok[$chanid]=true;
                            }

                            $starttime=$timecurrent;
                            $finalquery.=$query."\n";
                            //$db->update("processing", "progress," . microtime(true), "channel='" .$ami->GetChannel($t2[1])."'");
                            foreach($resultarray as $channelid=>$eventtime1){
                                $db->update("processing","progress,".$eventtime1,"channel='".$channelid."'");
                                $log->debug($db->query->last,"SDP151");
                            }
                        }
                        $stop[$chanid]=1;

                        $log->debug( "number = ".$value2['number']);

                        $log->debug( "channel = ".$t2[1]);
                        $stopkey=0;
                        foreach ($stop as $value) {
                            $stopkey++;
                        }

                        if($stopkey==sizeof( $task)){
                           // $db->query($finalquery);
                            foreach($resultarray as $channelid=>$eventtime){
                                $db->update("processing","progress,".$eventtime,"channel='".$channelid."'");
                                $log->debug($db->query->last,"SDP142");
                            }
                            $log->info( $finalquery);
                            $log->info( "End by STOP");
                            die;
                        }

                    }


                }
            }
        }

        $data=$a;
    }


    fclose($fh);

    if($time1<$timecurrent-5){
        $time1=$timecurrent;

        $log->info(  "timeout after".round($maxworktime-($timecurrent- $starttime)));
        $log->info( "Check db status");
        $task1 = $db->select("`id`,`number` from `processing` where  `status`=0 and `md5hash`='".$routemd5hash."' and `number`<>''",0);
        if (!is_array($task1) ) {
            echo "have no task";
            foreach($resultarray as $channelid=>$eventtime){
                $db->update("processing","progress,".$eventtime,"channel='".$channelid."'");
                $log->debug($db->query->last,"fromarray");
            }
            $log->debug( $finalquery,'onlyquery');
            $log->info( "have no task");
            die;
        }
    }

    if($timecurrent - $starttime > $maxworktime){
        if(trim($finalquery) != "" ) {
            $db->query($finalquery);
            $log->debug($finalquery, "line206");
        }
        $log->debug( "Current time is :".$timecurrent." start time is :".$starttime." diff ".($timecurrent-$starttime));
        $log->info( "Work time is out ".$maxworktime.". Script will die.");
        die;
    }
}

?>
