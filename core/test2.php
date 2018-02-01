<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 29.07.15
 * Time: 11:59
 */

$data="
[Oct 16 00:28:10] VERBOSE[22284][C-00c60f18] pbx.c: -- Executing [998909601680@checker:1] NoOp(\"Local/998909601680@checker-000014f0;2\", \"\") in new stack
[Oct 16 00:28:10] VERBOSE[22284][C-00c60f18] pbx.c: -- Executing [998909601680@checker:2] MixMonitor(\"Local/998909601680@checker-000014f0;2\", \"/var/spool/asterisk/monitor/evouzbbeel_2015_10_16_00_28_07_998909601680_23659.wav\") in new stack
[Oct 16 00:28:10] VERBOSE[22284][C-00c60f18] pbx.c: -- Executing [998909601680@checker:3] Goto(\"Local/998909601680@checker-000014f0;2\", \"managerd,998909601680,1\") in new stack
[Oct 16 00:28:10] VERBOSE[22285][C-00c60f18] app_mixmonitor.c: == Begin MixMonitor Recording Local/998909601680@checker-000014f0;2
[Oct 16 00:28:10] VERBOSE[22284][C-00c60f18] pbx.c: -- Executing [998909601680@managerd:1] Dial(\"Local/998909601680@checker-000014f0;2\", \"SIP/evo/111998909601680,90,L(3600000)\") in new stack
[Oct 16 00:28:10] VERBOSE[22284][C-00c60f18] app_dial.c: -- SIP/evo-01608a78 is making progress passing it to Local/998909601680@checker-000014f0;2
[Oct 16 00:28:37] VERBOSE[22284][C-00c60f18] app_dial.c: -- SIP/evo-01608a78 answered Local/998909601680@checker-000014f0;2
[Oct 16 00:28:37] VERBOSE[23703][C-00c60f18] pbx.c: -- Executing [s@cwc_playwa:1] NoOp(\"Local/998909601680@checker-000014f0;1\", \"start to record\") in new stack
[Oct 16 00:28:37] VERBOSE[23703][C-00c60f18] pbx.c: -- Executing [s@cwc_playwa:2] NoOp(\"Local/998909601680@checker-000014f0;1\", \"file name evouzbbeel_2015_10_16_00_28_07_998909601680_23659\") in new stack
[Oct 16 00:28:37] VERBOSE[23703][C-00c60f18] pbx.c: -- Executing [s@cwc_playwa:3] Monitor(\"Local/998909601680@checker-000014f0;1\", \"wav,evouzbbeel_2015_10_16_00_28_07_998909601680_23659\") in new stack
[Oct 16 00:28:37] VERBOSE[23703][C-00c60f18] pbx.c: -- Executing [s@cwc_playwa:4] Answer(\"Local/998909601680@checker-000014f0;1\", \"\") in new stack
[Oct 16 00:28:37] VERBOSE[23703][C-00c60f18] pbx.c: -- Executing [s@cwc_playwa:5] Playback(\"Local/998909601680@checker-000014f0;1\", \"custom/20sec\") in new stack
[Oct 16 00:28:37] VERBOSE[23703][C-00c60f18] file.c: -- Playing 'custom/20sec.slin' (language 'en')
[Oct 16 00:28:37] VERBOSE[22284][C-00c60f18] pbx.c: -- Executing [h@managerd:1] AGI(\"Local/998909601680@checker-000014f0;2\", \"aaa/stopall1.py\") in new stack
[Oct 16 00:28:37] VERBOSE[22284][C-00c60f18] res_agi.c: -- AGI Script aaa/stopall1.py completed, returning 0
[Oct 16 00:28:37] VERBOSE[22284][C-00c60f18] pbx.c: == Spawn extension (managerd, 998909601680, 1) exited non-zero on 'Local/998909601680@checker-000014f0;2'
[Oct 16 00:28:58] VERBOSE[22285][C-00c60f18] app_mixmonitor.c: == End MixMonitor Recording Local/998909601680@checker-000014f0;2
";
$data="[Dec  1 16:13:41] VERBOSE[9275][C-0046ef1a] app_dial.c:     -- SIP/orphy-00843e89 is making progress passing it to Local/8801986430717@checker-00000c0f;2";
$p = new Parcer();
$p->SetLogData($data);

class Parcer{
    public function SetLogData($data){
        $logarray=explode("\n",$data);
        foreach($logarray as $line){
            if(strpos($line,"Dial")){
                $event=$this->DialDivide($line);
                $this->Compere($event);
                continue;
            }
            if(strpos($line,"progress")){
                $event=$this->ProgressDivide($line);
                $this->Compere($event);
                continue;
            }
            if(strpos($line,"answered")){
                $event=$this->AnsweredDivide($line);
                $this->Compere($event);
                continue;
            }
            if(strpos($line,"h@managerd")){
                $event=$this->HangupDivide($line);
                $this->Compere($event);
                continue;
            }
        }
    }
    private function HangupDivide($line){
        //[Oct 16 00:28:37] VERBOSE[22284][C-00c60f18] pbx.c: -- Executing [h@managerd:1] AGI(\"Local/998909601680@checker-000014f0;2\", \"aaa/stopall1.py\") in new stack
        $word= explode(" ",$line);
        $event['event']="hangup";
        $event['time']=date("Y")."-".substr($word[0],1)."-".$word[1]." ".substr($word[2],0,strlen($word[2])-1);
        $event['unixtime']=strtotime($event['time']);
        $t=explode("/",$word[8]);
        $t1=explode(";",$t[1]);
        $event['channel']=$t1[0];
        $t=explode("@",$t1[0]);
        $event['number']=$t[0];
        return $event;
    }

    private function AnsweredDivide($line){
        //[Oct 16 00:28:37] VERBOSE[22284][C-00c60f18] app_dial.c: -- SIP/evo-01608a78 answered Local/998909601680@checker-000014f0;2
        $word= explode(" ",$line);
        $event['event']="answered";
        $event['time']=date("Y")."-".substr($word[0],1)."-".$word[1]." ".substr($word[2],0,strlen($word[2])-1);
        $event['unixtime']=strtotime($event['time']);
        $t=explode("/",$word[8]);
        $t1=explode(";",$t[1]);
        $event['channel']=$t1[0];
        $t=explode("@",$t1[0]);
        $event['number']=$t[0];
        return $event;
    }
    private function ProgressDivide($line){
        //[Oct 16 00:28:10] VERBOSE[22284][C-00c60f18] app_dial.c: -- SIP/evo-01608a78 is making progress passing it to Local/998909601680@checker-000014f0;2
        //[Dec  1 16:13:41] VERBOSE[9275][C-0046ef1a] app_dial.c:     -- SIP/orphy-00843e89 is making progress passing it to Local/8801986430717@checker-00000c0f;2
        $this->datetime($line);
        //$this->datetime1($line);

        $word= explode(" ",$line);
        $event['event']="progress";
        $dt= $this->datetime($line);
        $event['time']=$dt['all'];
        //$event['time']=$dt[0]." ".$dt[1]." ".$dt[2]." ".$dt[3];

        //$event['time']=date("Y")."-".substr($word[0],1)."-".$word[1]." ".substr($word[2],0,strlen($word[2])-1);
        $event['unixtime']=strtotime($event['time']);
        $event['checktime']= date("Y m d H:i:s ",$event['unixtime']);
        $tt1= explode("is making progress passing it to ",$line);
        $tt2= explode("/",$tt1[1]);
        $event['proto']=$tt2[0];
        $tt3=explode(";",$tt2[1]);


        //print_r($tt3);
        $event['channel']=$tt3[0];
        $t=explode("@",$tt3[0]);
        $event['number']=$t[0];
        return $event;
    }
    private function datetime($text){
        $date =date("Y", time()).";". preg_replace('/^(\[)([a-zA-z]+) +([0-9]+) +([0-9]+:[0-9]+:[0-9]+)(\]).*/i', '$2;$3;$4', $text);
        $date=explode(";", $date);
        $date['time']=$date[3];
        $date['date']=date("Y-m-d",strtotime($date[0]."-".$date[1]."-".$date[2]));
        $date['all']=date("Y-m-d H:i:s",strtotime($date['date']." ".$date['time']));
        //print_r($date);
        return $date;
    }
    private function Compere($event){
        print_r($event);
    }
    private function DialDivide($line){
        //[Oct 16 00:28:10] VERBOSE[22284][C-00c60f18] pbx.c: -- Executing [998909601680@managerd:1] Dial(\"Local/998909601680@checker-000014f0;2\", \"SIP/evo/111998909601680,90,L(3600000)\") in new stack
        $word=explode(" ",$line);
        $event['event']="dial";
        $event['time']=date("Y")."-".substr($word[0],1)."-".$word[1]." ".substr($word[2],0,strlen($word[2])-1);
        $event['unixtime']=strtotime($event['time']);
        $t=explode("/",$word[8]);
        $t1=explode(";",$t[1]);
        $event['channel']=$t1[0];
        $t=explode("@",$t1[0]);
        $event['number']=$t[0];
        return $event;

    }
}