#!/usr/bin/php
<?php
/**
 * Created by PhpStorm.
 * User: y
 * slava
 * Date: 16.07.15
 * Time: 17:21
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
        'debug'=>true, //if true log will show to desktop, false write to file
        'monitor'=>"/var/spool/asterisk/monitor/",
        'context'=> "managerd",
        'recordcontext' => "cwc_playwa",
        'CallerID'=>"7451674500",
        'log_file' => "/var/www/html/dialmanager/core/checker.log",
        'log_write' => "file",
    );

    include('mysqli.php');
include('ami.php');
include('log.php');
include('Parcer.php');
$parcer= new Parcer();
$log = new Log($config);
$ami= new Ami();
    ini_set('default_charset', 'utf-8');

    $file="/var/log/asterisk/full";
    //$file="/var/log/asterisk/full-20151016";
    $db= new db($config);
    $routename=$argv[1];
    logger($routename,"",$config['debug']);
    $socket = fsockopen($config['manager_host'],$config['manager_port'], $errno, $errstr, 10);
    if (!$socket){
        echo "$errstr ($errno)\n";
    }
    else {


        $task = $db->select("`id`,`number` from `processing` where  `routename`='".$routename."' AND `number` <> ''",1);
        print_r($task);
        echo sizeof( $task);
        $stop=0;

        //die;

        $size = filesize($file)-10;
        logger( "start size = ".$size."\n",'',$config['debug']);
        $time1=microtime(true);
        $starttime=$time1-50;
        $maxworktime=sizeof($task)*40;
        $stop=array();
        echo "timeout after ".$maxworktime;
        $finalquery="";
        $resultarray=array();
        $progressok=array();

        while (true) {
            $timecurrent=microtime(true);
            clearstatcache();
            while(true) {
                clearstatcache();
                $currentSize = filesize($file);

                if ($size < $currentSize) {
                    //echo $currentSize."\n";
                    break;
                }
                usleep(1000);

            }
            if($timecurrent-5>$oldtime){
                $oldtime=$timecurrent;
                $log->info("SDP online","status");
                $query="`id`,`number` from `processing` where `checkstart` = 0 and `routename` like '".$routename."' AND `number` <>''";
                $data1=$db->select($query, 1);
                $log->debug($db->query->last);
                if(!is_array($data1)){
                    $log->info("Have no data from tables, process will die !!!!!!!!!","SDP");
                    $break++;
                    if($break>2)break;
                }
            }


            $fh = fopen($file, "r");
            fseek($fh, $size);
//echo $size;
            $size=$currentSize;
            while ($d = fgets($fh)) {
               $a= explode("\n",$d);
                foreach($a as $key=>$value) {
                    $pos = strripos($value, "is making progress passing it to");
                    if ($pos === false) {
                        ;
                    }else{
//echo $value;
                        $value1 = explode("--",$value);
                        $t2=explode("is making progress passing it to",$value1[1]);
                        //echo $t2[0]." ====   ".$t2[1]."\n";
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
                                    $progressline = $parcer->setlogline($value);
                                    $log->debug($progressline,"SDP_progresslist");
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
                                        $log->debug($db->query->last,"SDP125");
                                    }
                                }
                                $stop[$chanid]=1;
                                echo "number = ".$value2['number']."\n";
                                logger( "number = ".$value2['number'],'',$config['debug']);
                                echo "channel = ".$t2[1]."\n";
                                logger( "channel = ".$t2[1],'',$config['debug']);
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
                                    logger( $finalquery,'',$config['debug']);
                                    logger( "End by STOP",'',$config['debug']);
                                    die;
                                }

                            }


                        }
                    }
                }

                $data=$a;
               // print_r($data);
//die;
            }


            fclose($fh);
           // die;
            if($time1<$timecurrent-5){
                $time1=$timecurrent;
                echo "Check db status\n";
                echo "timeout after".round($maxworktime-($timecurrent- $starttime))."\n";
                logger( "Check db status",'',$config['debug']);
                $task1 = $db->select("`id`,`number` from `processing` where  `status`=0 and `routename`='".$routename."' and `number`<>''",0);
                if (!is_array($task1) ) {
                    echo "have no task";
                    foreach($resultarray as $channelid=>$eventtime){
                        $db->update("processing","progress,".$eventtime,"channel='".$channelid."'");
                        logger($db->query->last,"fromarray",$config['debug']);
                    }
                    $log->debug( $finalquery,'onlyquery');
                    $log->info( "have no task");
                    die;
                }
                /*
                $data = exec ("ps -ef | grep php",$mas);
                //$d= explode("\n",$data);
                //echo "find - ".$routename."\n";
                $count=0;
                $finddata=array();
                foreach($mas as $key=>$value){
                    $v=strripos($value,$routename);
                    //echo " v= ".$v."\n";
                    if($v!==false){
                        $finddata[]=$value;

                        $count++;
                    }
                }
                if($count<=2){
                    logger( "Main proccess die.",'',$config['debug']);
                    die;
                }
                */
            }
           // $q1=$timecurrent- $starttime;
           // $q2=sizeof($task)*40;
           // echo $q1."  ==   ".$q2;
            if($timecurrent- $starttime>$maxworktime){
                //echo "time out";
                $db->query($finalquery);
                $log->debug( $finalquery,"line206");
                $log->info( "time out");
                die;
            }
        }
    }

    function logger1($data,$id="",$view=false){
        $file=$GLOBALS['config']['logfile'];
        $td=microtime(true);//date('Y-m-d H:i:s');
        if($id=="") {
            $scriptname = "SDP check";
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
