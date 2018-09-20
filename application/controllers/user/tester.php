<?php
/**
 * Created by Unix develop team.
 * User: slava
 * Date: 26.05.15
 * Time: 22:39
 */
class Tester extends Core_controller {
    private $filename="tester.php ";

    public function __construct() {
        parent::__construct();

        $this->module_name = 'Тестер';
        $this->load_model('list_model');
        $this->load_model('trunk_model');
        $this->load_model('numberpool_model');
        $config = array(
            'log_file' => "/var/log/httpd/webaction.log",
            'log_write' => "file",

        );
        $this->log->setConfig($config);
    }

    public function index() {
        $this->listtable();

    }
    public function listtable() {
        $userTests = $this->db->select("st.name, st.status,st.md5hash, SUM(IF(`pr`.`checkstart`=1, 1, 0)) as `start`, 
            SUM(IF(`pr`.`checkstart`=0, 1, 0)) as `stop`, COUNT(*) as total 
            FROM `test_status` as st, `processing` as pr 
            WHERE st.md5hash=pr.md5hash AND st.userid='".$_SESSION['id']."'
            GROUP BY st.name DESC ");

        $view = array(
            'view' => 'tester/listtable',
            'module' => 'User tests list',
            'var' => array(
                'userTests'=>$userTests,
            )
        );
        $this->view($view);

    }
    public function create() {
        $functionName=$this->filename."function activate ";
        if(isset($_POST['create'])) {
            $md5hash = md5($_POST['name'].$_POST['poolgroup'].$_SESSION['id']);
            $createNewTestQuery = "INSERT INTO `test_status` (`md5hash`, `status`, `userid`, `name`) ".
                                "VALUES ( '".$md5hash."', 'stop', ".$_SESSION['id'].",'".$_POST['name']."' )";
            $this->log->debug($functionName." createNewTestQuery ".$createNewTestQuery);
            $this->db->query($createNewTestQuery);

            $poolname=$this->db->select("`name` from `dm_poolgroup` where `id`='".$_POST['poolgroup']."'; ",0);
            $q="INSERT INTO `processing` (`routename`,`anumber`, `prefix`, `number`, `numberpoolname`, `md5hash`) ".
                "SELECT '".$_POST['name']."', '".$_POST['anumber']."', '".$_POST['prefix']."', CONCAT('".$_POST['prefix']."',`number`) as numberWithPrefix,'".$poolname."' , '".$md5hash."' FROM `dm_numberpool` ".
                "where `poolgroup`='".$_POST['poolgroup']."';";
            $this->db->query($q);
            $this->log->debug($functionName." q ".$q);
            header('Location: '.baseurl('tester/listtable'));
            die;
        }
        $pgl=$this->numberpool_model->GetListGroup();
        $poolgrouplist=$this->list_model->GetList($pgl,"poolgroup",0);
        $getAnumber = "`anumber` FROM `anumber` WHERE `userid`='".$_SESSION['id']."'";
        $this->log->debug($functionName.$getAnumber);
        $anumber = $this->db->select($getAnumber, 0 );
        $this->log->debug($functionName."A number is :".$anumber);


        $this->view(
            array(
                'view' => 'tester/create',
                'var' => array(
                    'poolgroup'=>$poolgrouplist,
                    'anumber' => $anumber
                )
            )
        );
    }
    /**
     * @param $id
     */
    public function activate($md5hash) {
        global $_config;
        $functionName=$this->filename."function activate ";
        $md5hash=urldecode( $md5hash);
        $checkTestStatus = $this->db->select("`status` from `test_status` where `md5hash`='".$md5hash."'", false);
        $this->log->debug($functionName.$this->db->query->last);
        if($checkTestStatus == "stop") {
            $this->db->update("test_status",'status,in progress',"`md5hash`='".$md5hash."'");
            $this->log->debug($functionName.$this->db->query->last);
            $this->log->info($functionName."Start checker script for ".$md5hash." test");
            $command = $_config['php'] . " -f " . $_config['checker_all'] . " " . $md5hash . " >> " . $_config['checker_all_log'] . " & 2>" . $_config['checker_all_err_log'];
            $this->log->debug($functionName." checker script system comman ".$command);
            $run = system($command);
            $this->log->info($functionName." checker script system comman output :'".$run."'");
        }
        header('Location: '.baseurl('tester/listtable'));
        die;
    }
    /**
     * @param $id
     */
    public function deactivate($md5hash) {
        $functionName=$this->filename."function deactivate ";
        $md5hash=urldecode( $md5hash);
        $checkTestStatus = $this->db->select("`status` from `test_status` where `md5hash`='".$md5hash."'", false);
        $this->log->debug($functionName.$this->db->query->last);
        $this->log->info($functionName." The test ".$md5hash." in the ".$checkTestStatus." status");
        if($checkTestStatus != "stop") {
            $this->db->update("test_status",'status,stop',"`md5hash`='".$md5hash."'");
            $this->log->debug($functionName.$this->db->query->last);
            $this->db->update( "processing",'checkstart,0',"`md5hash`='".$md5hash."' and `status`=0");
            $this->log->debug($functionName.$this->db->query->last);
        }

        header('Location: '.baseurl('tester/listtable'));
        die;
    }
    public function reset($md5hash) {
        $functionName=$this->filename."function reset ";
        $md5hash=urldecode( $md5hash);
        $query="`recordfile`,`recordfile2` from `callwaytest`.`processing`
        where `processing`.`md5hash` ='".$md5hash."'";
        $files=$this->db->select($query);
        $this->log->debug($functionName.$this->db->query->last);
        $this->log->debug($files);
        foreach($files as $file) {
            if(trim($file['recordfile']) != "") {
                $fileDeleteCommand = "rm -f " . $file['recordfile'];
                $this->log->debug($functionName . $fileDeleteCommand);
                exec($fileDeleteCommand);
            }
            if(trim($file['recordfile2']) != "") {
                $fileDeleteCommand = "rm -f " . $file['recordfile2'];
                $this->log->debug($functionName . $fileDeleteCommand);
                exec($fileDeleteCommand);
            }
        }

        $query="
         UPDATE `callwaytest`.`processing` SET
`timestart` = NULL ,
`timering` = NULL ,
`progress` = NULL ,
`timeringing` = NULL ,
`timeup` = NULL ,
`timehangup` = NULL ,
`callstatus` = '',
`checkstart` = '0',
`status` = '0',
`recordfile` = NULL ,
`channel` = NULL ,
`recordfile2` = NULL,
 `logdata` = NULL ,
 `actionid` = NULL WHERE `processing`.`md5hash` ='".$md5hash."'";
        $this->log->debug($functionName.$query);
        $this->db->query( $query);

        header('Location: '.baseurl('tester/listtable'));
        die;
    }


    /**
     * @param $id
     */
    public function delete($md5hash) {
        $functionName=$this->filename."function delete ";
        $md5hash=urldecode( $md5hash);
        $query="DELETE FROM `processing` WHERE `md5hash`='".$md5hash."'";
        $this->log->debug($functionName.$query);
        $this->db->query($query);

        $query="DELETE FROM `test_status` WHERE `md5hash`='".$md5hash."'";
        $this->log->debug($functionName.$query);
        $this->db->query($query);

        header('Location: '.baseurl('tester/listtable'));
        die;
    }
    public function report($md5hash){
        /*
         *  `id` ,
`routename` ,
`number` ,
`timestart` ,
`timering` ,
`timeringing` ,
`timeup` ,
`timehangup` ,
`callstatus` ,
`checkstart` ,
`status` ,
`recordfile`
         */
        $md5hash=urldecode( $md5hash);
        $functionName=$this->filename."function report ";
        $query="* from `processing` where `md5hash`='".$md5hash."' and `number`<>''";
        $this->db->query($functionName.$query);
        $data=$this->db->select($query);

        foreach($data as $key=>$value){
            //print_r($value);
            $report[$key]['number']=$value['number'];
            if($value['timestart']==0){
                $value['timestart']= $value['timering']-0.1;
            }
            $report[$key]['timestart']=date('Y-m-d H:i:s',$value['timestart']);


            $report[$key]['timering']=round($value['timering']-$value['timestart'],2);
            if(is_null($value['timering'])){
                $report[$key]['PDD']= 0;
            }else
            {
                $report[$key]['PDD']= abs( round($value['timering'] - $value['timestart'], 2));
                //$report[$key]['PDD']=0;
               //$value['timeringing']=$value['timestart'];

            }
            if(is_null($value['progress'])) {


                if (is_null($value['timeringing'])) {
                    $report[$key]['PDD'] = 0;
                    if (is_null($value['timeup'])) {
                        $report[$key]['PDD'] = 0;
                        if (is_null($value['timehangup'])) {
                            $report[$key]['PDD'] = 0;
                        } else {
                            $report[$key]['PDD'] = round($value['timehangup'] - $value['timestart'], 2);
                            //$report[$key]['PDD']=0;
                            //$value['timeringing']=$value['timestart'];

                        }
                    } else {
                        $report[$key]['PDD'] = round($value['timeup'] - $value['timestart'], 2);
                        //$report[$key]['PDD']=0;
                        //$value['timeringing']=$value['timestart'];

                    }
                } else {
                    //f($value['timeringing']<)
                    $report[$key]['PDD'] = round($value['timeringing'] - $value['timestart'], 2);
                    //$report[$key]['PDD']=0;
                    //$value['timeringing']=$value['timestart'];

                }
            }else{
                if (is_null($value['timeringing'])) {
                        $report[$key]['PDD'] = round($value['progress'] - $value['timestart'], 2);
                }
                elseif($value['timeringing']<$value['progress']){
                    $report[$key]['PDD'] = round($value['timeringing'] - $value['timestart'], 2);
                }else {
                    $report[$key]['PDD'] = round($value['progress'] - $value['timestart'], 2);
                }
            }
            $report[$key]['PDD']=abs($report[$key]['PDD']);


            if(is_null($value['timeup'])){
                if(is_null($value['timeringing'])) {
                    if(is_null($value['progress'])) {
                        $report[$key]['RBT'] = 0;//round($value['timeup'] - $value['timestart'], 2);
                    }else{
                        $report[$key]['RBT'] = round($value['timehangup'] - $value['progress'], 2);
                    }

                }else{
                    $report[$key]['RBT'] =round($value['timehangup'] - $value['timeringing'], 2);
                }
            }else{

                if(is_null($value['timeringing'])) {
                    if(is_null($value['progress'])) {
                        $report[$key]['RBT'] = 0;//round($value['timeup'] - $value['timestart'], 2);
                    }else{
                        $report[$key]['RBT'] = round($value['timeup'] - $value['progress'], 2);
                    }
                }else{
                    $report[$key]['RBT'] = round($value['timeup'] - $value['timeringing'], 2);
                }

            }
            $report[$key]['DIALOG']=0;
            if(isset($value['timeup'])){
                $report[$key]['DIALOG']=round($value['timehangup'] - $value['timeup'], 2);

            }
          /*  if(!is_null($value['timeup'])){
                $report[$key]['DUR']= round($value['timehangup'] - $value['timeup'], 2);
            }else{
                $report[$key]['DUR']= round($value['timehangup'] - $value['timestart'], 2);
            }*/
            $report[$key]['DUR']= round($value['timehangup'] - $value['timestart'], 2);





            if(!is_null($value['timeringing'])) {
                $report[$key]['timeringing'] = round($value['timeringing'] - $value['timering'], 2);
            }else{
                $report[$key]['timeringing'] =0;
                $value['timeringing']=$value['timering'];
            }

            if(!is_null($value['timeup'])) {
                $report[$key]['timeup'] = round($value['timeup'] - $value['timeringing'], 2);
            }else{
                $report[$key]['timeup'] =0;

                $value['timeup']=$value['timeringing'];
            }
            if($value['callstatus']!="20") {

                $report[$key]['timehangup'] = round($value['timehangup'] - $value['timeup'], 2);
            }else{
                $report[$key]['timehangup']= 0;
            }
            $report[$key]['callstatus']=$value['callstatus'];
            $report[$key]['recordfile']=$value['recordfile'];
            $report[$key]['id'] = $value['id'];
            if($report[$key]['RBT']<0) {
                //$report[$key]['RBT']='NOV';
                $report[$key]['RBT']='0';
            }
            if($report[$key]['DUR']<0) {
                //$report[$key]['DUR']='NOV';
                $report[$key]['DUR']='0';
            }
            if($report[$key]['ANS']<0) {
                //$report[$key]['ANS']='NOV';
                $report[$key]['ANS']='0';
            }
            if(is_null($value['timeringing']) && is_null($value['progress']) && ! is_null( $value['timehangup'] )) {
                $report[$key]['PDD'] = round($value['timehangup'] - $value['timestart'], 2);
            }





        }
//die;
        $query="* from `test_status` where `md5hash`='".$md5hash."'";
        $this->db->query($functionName.$query);
        $testStatus=$this->db->select($query);
        $view = array(
            'var' => array('reports'=>$report,
                'routename'=>$testStatus['name'],
                'numberpoolname'=>$data[0]['numberpoolname']),
            'view' => 'tester/report',
            'css' => array(baseurl('pub/css/jquery.dataTables.min.css')),
            'js' => array(baseurl('pub/js/jquery.dataTables.min.js'),baseurl('pub/js/page.report.showreport.js'))
        );
        $this->view($view);
    }

    public function logdata($data){
        $data= $this->db->select("`logdata` from `processing` where `id`=".$data,0);
        $darr= explode("\n",$data);
        $search=array("answered","progress","Dial","End");
        $txt="";
        foreach($darr as $key=>$value){
            $f=0;
            foreach($search as $s){
                if(strstr($value,$s)){
                    $txt.="<font color=\"\#CC0000\">".$value."</font><br>";
                    $f=1;
                    break;
                }else {

                }
            }
            if($f==1){

            }else{
                $txt .= $value."<br>";
            }

        }
        //$data=str_replace("\n","<br>",$txt);
        echo $txt;


    }
    public function getaudio($data){
        $functionName=$this->filename."function getaudio ";
        $query = "`recordfile` from `processing` where `id`=".$data;
        $this->log->debug($functionName.$query);
        $file = $this->db->select($query,0);
        $this->log->debug($functionName."file name :".$file);
        if(trim($file) !="" && file_exists($file)){
            $fp=fopen($file, "r");
            header('Content-Type: audio/wav');
            $recordFileName = pathinfo($file, PATHINFO_FILENAME);
            $this->log->debug($functionName."Record file name :".$recordFileName);
            header('Content-disposition: attachment; filename="'.$recordFileName.'"');
            header("Content-transfer-encoding: binary");
            fpassthru($fp);
            fclose($fp);
        }else{
            $this->log->error($functionName."File name is empty ".$data);
        }

    }
    public function getaudio2($data){
        $functionName=$this->filename."function getaudio2 ";
        $query = "`recordfile2` from `processing` where `id`=".$data;
        $this->log->debug($functionName.$query);
        $file = $this->db->select($query,0);
        $this->log->debug($functionName."file name :".$file);
        if(trim($file) !="" && file_exists($file)) {
            $fp = fopen($file, "r");
            header('Content-Type: audio/wav');
            $recordFileName = pathinfo($file, PATHINFO_FILENAME);
            header('Content-disposition: attachment; filename="' .$recordFileName . '"');
            header("Content-transfer-encoding: binary");
            fpassthru($fp);
            fclose($fp);
        }else{
            $this->log->error($functionName."File name is empty ".$data);
        }

    }
    public function logout() {
        $this->user_model->logout();
        header('Location: '.baseurl());
    }
}