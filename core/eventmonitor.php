<?php

error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
include_once ('mysqli.php');
include_once ('../internal_config.php');
include_once ('log.php');
class EventMonitor{
        private $socket="";
        private $callStatusBuffer=array();
        private $config ="";
        private $activeQueues=array();
        private $db = "";
        private $log = "";

    /**
     * EventMonitor constructor.
     * @param string $socket
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->log = new Log($this->config['log']);
        $this->log->debug($this->config);
        $this->db = new db($config['mysql']);

        $this->log->info( "Loading configuration" );
        $this->db->update("eventm_settings", array("propertyvalue" => 0),"`propertyname` = 'restart'");// "`propertyvalue` FROM `eventm_settings` WHERE `propertyname` = 'restart'" , false);
        $activatelog = $this->db->select("`propertyvalue` FROM `eventm_settings` WHERE `propertyname` = 'activatelog'" , false);
        if($activatelog==0){
            $this->log->setConfig(array(
                'log_level' => "disable"
            ));
        }
        $activeQueues = $this->db->select("`extension` FROM `eventm_current`");
        foreach ($activeQueues as $key => $queue){
            $this->activeQueues[$queue] = true;
            $this->createFolders($queue);
        }
        $this->log->debug( $this->db->query->last);
        $this->log->debug( "Loaded queues list");
        $this->log->debug($this->activeQueues);
    }
    private function createFolders($queueName){
        $queueFolderName = $this->config['log']['log_folder'].$queueName."/";
        if(!file_exists($queueFolderName)){
            $this->log->debug("Create folder :".$queueFolderName);
            if(! mkdir($queueFolderName , 0777, true)){
                $this->log->error( "Error creating the folder :".$queueFolderName);
            }
        }
    }

    public function getConnection(){
        $managerConfig = $this->config['manager'];
        $socket = fsockopen($managerConfig['host'],$managerConfig['port']);
        if (!$socket){
            $this->log->error( "Connection error");
            die;
        }else{
            $this->socket=$socket;
            $this->log->debug( "Socket connected");
            $login_command = "Action: Login\r\nUserName: ".$managerConfig['login']."\r\nSecret: ".$managerConfig['password']."\r\n\r\n";
            fputs($socket,$login_command);
            $access=true;
            $event = "";
            while($access){
                $dataline=fgets($socket);
                if($dataline == "\r\n"){
                    $eventar =$this->amiToArray($event);
                    if(isset($eventar['Response']) && $eventar['Response']=="Success"){
                        $this->log->info( "Authentication success");
                        $access=false;
                    }else{
                        $this->log->error( "Error need check login or password");
                        die;
                    }
                }
                $event .= $dataline;
            }
            }
        }
        
        private function amiToArray($eventstring){
            $eventar=array();
            foreach(explode("\n",$eventstring) as $key=>$value){
                if(trim($value)!=""){
                    $aline = explode(":",$value);
                    $keyinline = trim($aline[0]);
                    if(isset($aline[1])){
                        $valueinline=trim($aline[1]);

                    }else{
                        $valueinline="";
                    }
                    $eventar[$keyinline]=$valueinline;
                }
            }
            return $eventar;
        }

        public function monitor(){
            $this->getConnection();
            $event="";
            $oldtime =microtime(true);
            while(true){
                $currentTime =microtime(true);
                if($currentTime - 5 > $oldtime){
                    $oldtime = $currentTime;
                    $this->checkForStopService();
                }
                $dataline=fgets($this->socket);
                if($dataline == "\r\n"){
                    $eventar =$this->amiToArray($event);
                    $this->makeAction($eventar);
                    $event="";
                }
                $event .= $dataline;
            }
        }
        private function checkForStopService(){
        $this->log->debug("ping");
            $restart = $this->db->select("`propertyvalue` FROM `eventm_settings` WHERE `propertyname` = 'restart'" , false);
            $this->log->debug($this->db->query->last);
            $this->log->debug($restart);
            if($restart == 1){
                $this->log->warning( "Found stop request. The service will stop");
                die;
            }
        }
        private function makeAction($eventar){
            $queue="";
            //print_r($eventar);
// for test, if you need.
            switch($eventar['Event']){
                case "AgentCalled":
                    $cid=$eventar['CallerIDNum'];
                    $t1=explode("/",$eventar['AgentCalled']);
                    $t2=explode("@",$t1[1]); //get agent number from channel name
//                    $ext=$eventar['AgentName'];
                    $ext=$t2[0];
                    $status="RINGING";
                    $this->callStatusBuffer[$cid]['inc'] = true;
                    $this->callStatusBuffer[$cid]['Queue'] = $eventar['Queue'];
                    
                    $queue=$eventar['Queue'];
                    break;
                case "Bridge":
                    
                    if(!isset($this->callStatusBuffer[$eventar['CallerID1']] ) &&  ! isset( $this->callStatusBuffer[$eventar['CallerID2']]['UNPARKEDCALL']) ) {
                    //    echo "Test for excluding the outgoing call";
                        
                        return;
                    }
                    if(isset($this->callStatusBuffer[$cid]['UNHOLD']) && $this->callStatusBuffer[$cid]['UNHOLD'] && isset($this->callStatusBuffer[$eventar['CallerID2']]) && $this->callStatusBuffer[$eventar['CallerID2']]['UNPARKEDCALL'] ){
                        return;
                    }
                    
                    if(isset($eventar['Bridgestate']) && $eventar['Bridgestate'] != "Link" ||  isset($this->callStatusBuffer[$eventar['CallerID1']]["ANSWERED"]) ){
                        return ;
                    }
                    $cid ="";
                    $ext="";
                    $status="";
                    if(isset($this->callStatusBuffer[$eventar['CallerID2']]) && $this->callStatusBuffer[$eventar['CallerID2']]['UNPARKEDCALL'] ){
                        
                        $cid=$eventar['CallerID2'];
                        $ext=$eventar['CallerID1'];
                        $status="UNPARKEDCALL";
                        if(! $this->callStatusBuffer[$cid]['PARKEDCALL']){
                            //echo "wrong unparked events";
                            //print_r($eventar);
                            return;
                        }
                        $this->callStatusBuffer[$cid]['PARKEDCALL'] = false;
                        //this is bridge event for new B number after unparkedcall event
                    }else{
                        
                        $cid=$eventar['CallerID1'];
                        $ext=$eventar['CallerID2'];
                    
                        $status="ANSWERED";
                        if( $this->callStatusBuffer[$cid][$status] ){
                            //the call was answered
                            return;
                        }
                    }
                    $this->callStatusBuffer[$cid][$status]=true;
                    $this->callStatusBuffer[$cid]['ext']=$ext;
                    $queue=$this->callStatusBuffer[$cid]['Queue'];
                    break;
                case "Hangup":
//                    print_r($eventar);
                    $cid=$eventar['CallerIDNum'];
                    $ext=$eventar['ConnectedLineNum'];
                    $status="HANGUP";
                    //echo "Hangu event!!!!!!!";
                    if(!isset($this->callStatusBuffer[$cid]) || $this->callStatusBuffer[$cid]['ext'] != $ext && ! isset ($this->callStatusBuffer[$cid]['UNPARKEDCALL'])){
                    //    print_r($eventar);
                    //    echo "=======";
                    //    print_r($this->callStatusBuffer[$cid]);
                        return;
                    }
                    $queue=$this->callStatusBuffer[$cid]['Queue'];
                    
                    unset($this->callStatusBuffer[$cid]);
                    break;
                case "ParkedCall":
//                    print_r($eventar);
                    $cid = $eventar['CallerIDNum'];
                    $ext=$eventar['ConnectedLineNum'];
                    $status="PARKEDCALL";
                    $queue=$this->callStatusBuffer[$cid]['Queue'];
                    $this->callStatusBuffer[$cid][$status]=true;
                    $this->callStatusBuffer[$cid]['UNPARKEDCALL']=false;
                    $this->callStatusBuffer[$cid]['UNPARKEDFROMCHANNEL'] = "";
                    
                    
                    break;
                case "UnParkedCall":
//                    print_r($eventar);
                    //this events for confirmation unparkedcall , it do not send webhook
                    $cid = $eventar['CallerIDNum'];
                    $ext=$eventar['ConnectedLineNum'];
                    $status="UNPARKEDCALL_ORIGIN";
                    $queue=$this->callStatusBuffer[$cid]['Queue'];
                    $this->callStatusBuffer[$cid]['UNPARKEDCALL'] = true;
                    $this->callStatusBuffer[$cid]['UNPARKEDFROMCHANNEL'] = $eventar['Channel'];
                    return;
                    break;
                case "MusicOnHold":
//                    print_r($eventar);
                    $cid="";
                    $ext=$this->getNumFromChannel($eventar['Channel']);
                    if($ext === false) {
                        return;
                    }
                    $queue="";
                    foreach ($this->callStatusBuffer as $tcid => $cidData){
                        if($cidData['ext'] == $ext){
                            $cid=$tcid;
                            $queue=$cidData['Queue'];
                            break;
                        }else if($cidData['UNPARKEDFROMCHANNEL'] == $eventar['Channel']){
                            $cid= $tcid;
                            $ext= $cidData['ext']; //new ext got from unparkedcall event
                            $queue=$cidData['Queue'];
                            break;
                            
                        }
                    }
                    if($cid=="" || $this->callStatusBuffer[$cid]['PARKEDCALL']){
                        //echo "Wrong MOH!!!!!!!! cid='".$cid."' OR PARKED CALL";
                        return;
                    }
                    //for debug
                    //echo "cid =" .$cid. " ext=".$ext." queue=".$queue;
            
                    if($eventar['State'] == "Start"){
                        $status="HOLD";
                        $this->callStatusBuffer[$cid][$status]=true;
                        $this->callStatusBuffer[$cid]['UNHOLD']=false;
                        
                    }else{
                        $status="UNHOLD";

                        if(isset($this->callStatusBuffer[$cid][$status]) && $this->callStatusBuffer[$cid][$status] ){
                            return; //hide second unhold evetns
                        }

                        $this->callStatusBuffer[$cid][$status]=true;
                        $this->callStatusBuffer[$cid]['HOLD']=false;

                    }
                    break;
                
                default:
                    //for debug
                    //var_dump($eventar);
                    return;
            }
            if($cid[0] == "+" ){
                $cid = str_replace("+1","",$cid);
            }elseif ($cid[0] == "1"){
                $cid = substr($cid,1);
            }
            
            if(isset($this->activeQueues[$queue])) {
                $url = "https://prod.operr.com/v2/phone/dispatcher_webhook?base_number=" . $queue . "&customer_number=" . $cid . "&ext=" . $ext . "&status=" . $status;
                $response = file_get_contents($url);
                $this->log->SetGlobalIndex($queue);
                $this->log->debug( $url . " RespCode=" . $http_response_header[0]);
                $this->log->SetGlobalIndex("");
            }
        }
    private function getNumFromChannel($channel){
        $temp1 = explode("@",$channel);
        $temp2 = explode("/",$temp1[0]);
        $num = $temp2[1];
        if(strlen($num) != 3){$num=false;}
        return $num;
    }

}

$eventm = new EventMonitor($_config);
$eventm->monitor();


?>
