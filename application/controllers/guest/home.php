<?php
/**
 * Created by Unix develop team.
 * User: vlad
 * Date: 27.02.15
 * Time: 13:00
 */
class Home extends Core_controller {
    private $filename = "home.php ";
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->view(
            array(
                'module' => 'Авторизация'
            )
        );
    }

    public function login() {
        if($this->user_model->auth($_POST['login'],$_POST['pass'])) {
            
        }
        header("Location: ".baseurl(''));
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
            'view' => 'report',
            'css' => array(baseurl('pub/css/jquery.dataTables.min.css')),
            'js' => array(baseurl('pub/js/jquery.dataTables.min.js'),baseurl('pub/js/page.report.showreport.js'))
        );
        $this->view($view);

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
}