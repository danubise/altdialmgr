<?php
/**
 * Created by Unix develop team.
 * User: slava
 * Date: 26.05.15
 * Time: 22:39
 */
class Tester extends Core_controller {
    private $log="";
    public function __construct() {
        parent::__construct();
        $this->module_name = 'Тестер';
        $this->load_model('list_model');
        $this->load_model('trunk_model');
        $this->load_model('numberpool_model');
        $config = array(
            'log_file' => "/var/www/html/dialmanager/core/checker.log",
            'log_write' => "file",

        );
        $this->log= new Log($config);


    }

    public function index() {
        if(isset($_POST['add'])) {

        }

        $this->listtable();

    }
    public function listtable() {
        $listtest=$this->db->select("DISTINCT `routename` from `processing` ORDER BY `processing`.`id` DESC ");
        $status=array();
        if(is_array($listtest)) {
            foreach ($listtest as $key => $value) {

                $status[$value]['complite'] = $this->db->select(" count(*) FROM `processing` WHERE `routename`='$value' and `checkstart`=1 and `number`<>''", 0);
                $status[$value]['finish'] = $this->db->select(" count(*) FROM `processing` WHERE `routename`='$value' and `checkstart`=0 and `number`<>''", 0);
            }
        }

        $view = array(
            'view' => 'tester/listtable',
            'module' => 'Создание нового теста',
            'var' => array(
                'route'=>$listtest,
                'status'=>$status

            )
        );
        $this->view($view);

    }
    public function create() {
        if(isset($_POST['add'])) {
           // print_r($_POST);
            $poolname=$this->db->select("`name` from   `dm_poolgroup` where `id`='".$_POST['poolgroup']."'; ",0);
            $q="INSERT INTO `processing` (`routename`, `number`, `numberpoolname`) SELECT '".$_POST['name']."', `number`,'".$poolname."' FROM `dm_numberpool` where `poolgroup`='".$_POST['poolgroup']."'; ";
            $this->db->query($q);
            //echo $q;
            //die;
            header('Location: '.baseurl('tester/listtable'));
            die;
        }
        $pgl=$this->numberpool_model->GetListGroup();
        $poolgrouplist=$this->list_model->GetList($pgl,"poolgroup",0);
        $campany=$this->db->select("`id`,`name`,`status` from `Company` where `status`<'2'");
        $this->view(
            array(
                'view' => 'tester/create',
                'var' => array( 'poolgroup'=>$poolgrouplist)
            )
        );
    }
    /**
     * @param $id
     */
    public function activate($id) {
        $id=urldecode( $id);
        $commnad="/usr/bin/php -f /var/www/html/dialmanager/core/checker.php ".$id." >> /var/log/checker.log & 2>/dev/null";
        //echo "cmd ".$commnad."\n";
        //die;
       // $run = system("/usr/bin/php -f /var/www/html/dialmanager/core/sdp_check.php ".$id." >> /var/log/checker.log & 2>/dev/null");
        //echo "run1 ".$run."\n";
        $run = system("/usr/bin/php -f /var/www/html/dialmanager/core/checker_all.php ".$id." >> /var/log/checker.log & 2>/dev/null");
        //echo "run2 ".$run."\n";
        //die;
        header('Location: '.baseurl('tester/listtable'));
    }
    /**
     * @param $id
     */
    public function deactivate($id) {
        $id=urldecode( $id);
        $this->db->update( "processing",'checkstart,0',"`routename`='".$id."' and `status`=0");
        $this->log->debug($this->db->query->last);

        header('Location: '.baseurl('tester/listtable'));
    }
    public function reset($id) {
        $id=urldecode( $id);
        $query="`recordfile`,`recordfile2` from `callwaytest`.`processing`
        where `processing`.`routename` ='".$id."'";
        $files=$this->db->select($query);
        foreach($files as $file) {
            $c="rm -f ".$file['recordfile'];
            exec($c);
            exec("rm -f ".$file['recordfile2']);
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
 `actionid` = NULL WHERE `processing`.`routename` ='".$id."'";


        //echo $query."\n";
        $this->db->query( $query);
        //echo $this->db->query->last;
        //die;
        header('Location: '.baseurl('tester/listtable'));
    }


    /**
     * @param $id
     */
    public function delete($id) {
        $id=urldecode( $id);
        $query="DELETE FROM `processing` WHERE `routename`='".$id."'";
        $this->db->query($query);
        header('Location: '.baseurl('tester/listtable'));
    }
    public function report($id){
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
        $id=urldecode( $id);
        $query="* from `processing` where `routename`='".$id."' and `number`<>''";
        //echo urldecode( $query);
        //die;
        $data=$this->db->select($query);
        //print_r($data);
        //die;
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
        $view = array(
            'var' => array('reports'=>$report,
                'routename'=>$id,
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
        $file = $this->db->select("`recordfile` from `processing` where `id`=".$data,0);
        $fp=fopen($file, "r");
        header('Content-Type: audio/wav');
        header('Content-disposition: attachment; filename="'.end(explode("/",str_replace(":", "-", $file))).'"');
        header("Content-transfer-encoding: binary");
        fpassthru($fp);
        fclose($fp);

    }
    public function getaudio2($data){
        $file = $this->db->select("`recordfile2` from `processing` where `id`=".$data,0);
        $fp=fopen($file, "r");
        header('Content-Type: audio/wav');
        header('Content-disposition: attachment; filename="'.end(explode("/",str_replace(":", "-", $file))).'"');
        header("Content-transfer-encoding: binary");
        fpassthru($fp);
        fclose($fp);

    }
    public function logout() {
        $this->user_model->logout();
        header('Location: '.baseurl());
    }
}

    /**
     * Created by PhpStorm.
     * User: slava
     * Date: 09.10.15
     * Time: 13:56
     * Phone: 89878130785
     * Email: danubise@gmail.com
     * Skype: danubise
     */
    /*
     * config
     *  log_write   file/null (file) запись в файл или выводи на монитор
     *  log_file    имя файла в который будет вестись запись данных прим.test.log
     *  log_differentfiles true/false (false) возможность записывать в разный файлы, в зависимости от Level прим. info.test.log
     *  log_timetype unix/normal (normal) формат времени в лог файле
     *  log_active  true/false (true) включить\выключить логирование
     *  log_level   all,info,warning,error,debug перечисленные уровни логирования
     *  log_color   true/false (false) вывод логив в цветах
     *
     * пример использования
     * 1)   $log->info("information") //простой вывод информации
     * результат
     *      2015-10-09 15:18:16 info information
     * 2)   $log->info("text","123"); //запись с информации с определенным ключем для фильтрации в лог файле
     *      2015-10-09 15:31:13 info[123] txt
     */
    class Log {

        private $LogMessage= "";
        private $FileName="file.log";
        private $Write="file";
        private $TimeType="normal";
        private $Active=true;
        private $Level="all";
        private $DifferentFile=false;
        private $Color=false;
        private $GlobalIndex=""; // указывает что лог одного процесса
        //private $LevelTypes = array("info","warning","error","debug");

        public function __construct($config=""){
            if(is_array($config)){
                if(isset($config['log_file'])){
                    if(trim($config['log_file']) !="") {
                        $this->FileName = $config['log_file'];
                    }
                }
                if(isset($config['log_write'])){
                    $this->Write=$config['log_write'];
                }
                if(isset($config['log_differentfiles'])){
                    $this->DifferentFile=$config['log_differentfiles'];
                }
                if(isset($config['log_timetype'])){
                    $this->TimeType=$config['log_timetype'];
                }
                if(isset($config['log_active'])){
                    $this->Active=$config['log_active'];
                }
                if(isset($config['log_level'])){
                    $this->Level=$config['log_level'];
                }
                if(isset($config['log_color'])){
                    $this->Color=$config['log_color'];
                }
            }
            $this->LogMessage= new LogMessage();
        }
        public function SetGlobalIndex($txt){
            if(isset($txt)){
                $txt=trim($txt);
                if($txt!=""){
                    $this->GlobalIndex=$txt." ";
                }
            }
        }

        public function info($txt,$key=""){
            if($this->Level=="all" || strstr($this->Level,"info")) {
                if($this->Color) {
                    $this->LogMessage->type = "\e[32minfo\e[0m";
                }else{
                    $this->LogMessage->type = "info";
                }
                $this->Format($txt, $key);
            }
        }
        public function warning($txt,$key=""){
            if($this->Level=="all" || strstr($this->Level,"warning")) {
                if($this->Color) {
                    $this->LogMessage->type = "\e[33mwarning\e[0m";
                }else{
                    $this->LogMessage->type = "warning";
                }
                $this->Format($txt, $key);
            }
        }
        public function error($txt,$key=""){
            if($this->Level=="all" || strstr($this->Level,"error")) {
                if($this->Color) {
                    $this->LogMessage->type = "\e[31merror\e[0m";
                }else{
                    $this->LogMessage->type = "error";
                }
                $this->Format($txt, $key);
            }
        }
        public function debug($txt,$key=""){
            if($this->Level=="all" || strstr($this->Level,"debug")) {
                if($this->Color) {
                    $this->LogMessage->type = "\e[4mdebug\e[24m";
                }else{
                    $this->LogMessage->type = "debug";
                }
                $this->Format($txt, $key);
            }
        }
        private function Format($txt,$key){
            if(!$this->Active){
                return;
            }
            $this->LogMessage->txt=$this->CheckObject($txt);
            $this->LogMessage->key=$key;
            $this->Write();
        }
        private function CheckObject($txt){
            if(!isset($txt)){
                return "Null";
            }
            if(is_object($txt) || is_array($txt)){
                return print_r($txt, true);
            }
            return trim($txt);
        }
        private function LogShow(){
            if($this->TimeType=="unix"){
                $timetype= microtime(true);
            }else {
                $timetype = date("Y-m-d H:i:s");
            }
            $txtarr=explode("\n",$this->LogMessage->txt);
            $log="";
            $key="";
            if($this->LogMessage->key!=""){
                $key="[".$this->LogMessage->key."]";
            }
            foreach($txtarr as $line){
                $log.=$timetype." ".$this->GlobalIndex.$this->LogMessage->type.$key." ".$line."\n";
            }
            return $log;
        }
        private function Write(){
            $FileName=$this->FileName;
            if($this->DifferentFile){
                $FileName=$this->LogMessage->type."_".$FileName;
            }
            if ($this->Write == "file") {
                file_put_contents($FileName, $this->LogShow(), FILE_APPEND);
            } else {
                echo $this->LogShow();
            }
        }
    }
    class LogMessage{
        public $type;
        public $txt;
        public $key;
        public $line;
        public function __construct($type="Info",$txt="",$key=""){
            $this->type=$type;
            $this->txt=$txt;
            $this->key=$key;
        }
    }