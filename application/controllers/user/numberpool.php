<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 26.03.15
 * Time: 12:08
 */

class Numberpool extends Core_controller {
    public $dbasterisk='';
    private $filename="numberpool.php ";
    public function __construct() {
        parent::__construct();
        $this->module_name = 'Группы номеров';
        //$this->load_model('campany_model');
        //$this->load_model('trunk_model');
        $this->load_model('list_model');
        $this->load_model('numberpool_model');
        $config = array(
            'log_file' => "/var/log/httpd/webaction.log",
            'log_write' => "file",

        );
        $this->log->setConfig($config);
    }

    public function savenewname(){
        printarray($_POST);
        $idpool=0;
        foreach($_POST['poolname'] as $id=>$newPoolName){
            $newPoolName = str_replace(" ", "_", trim($newPoolName));

            $this->db->update('dm_poolgroup', "name,".$newPoolName ,"id=".$id);
            echo $this->db->query->last."<br>";
            echo $newPoolName;
            $idpool = $id;
        }
        header("location: ".baseurl("numberpool/index/edit/".$idpool));
    }

    public function index($page="",$idpool=0) {
        $page=urldecode( $page);
        $idpool=urldecode($idpool);
        switch($page){
            case "syncronyze":
                //синхронизация поулов с рабочей таблицей
                break;
            case "save":
                //printarray($_FILES);
                //die;
                //$text=file_get_contents($_FILES['file']['tmp_name']);
                // if need CSV $text = explode("\n",file_get_contents($_FILES['file']['tmp_name']));
                $text = explode("\n", $_POST['numbers']);
                $num_array = array();
                foreach($text as $t) {
                $t = trim($t);
                if(is_numeric($t)) $num_array[] = $t;
		}
                $tdata=array(
                    "name" => $_POST['name'],
                    "numberlist" => $num_array
                );
                $this->numberpool_model->Create($tdata);
                //printarray($tdata);
                header("location: ".baseurl("numberpool/index/grouplist"));
                die;
                break;
            case "addnumbertopool":
                //$text=file_get_contents($_FILES['file']['tmp_name']);
                //if need CSV $text = explode("\n",$text);
                $text = explode("\n", $_POST['numbers']);
                $num_array = array();
                foreach($text as $value1) {
                    $values=$this->cp1251_to_utf8($value1);
                        $prefix = "";
                        //echo $values."\n";
                    $num_array[] = trim($values);
                        if($values[0]=="+") {
                            $prefix="+";
                            $values = str_replace("+","",$values);
                        }
                        if(intval($values) and (strlen($values)>5 and (strlen(intval($values))==strlen($values)))) {
                            $values = $prefix.intval($values);
                            $num_array[] = $values;
                        }
                }
                $this->numberpool_model->AddNumberToPool($idpool,$num_array);
                //printarray($num_array);
                header("location: ".baseurl("numberpool/index/edit/".$idpool));
                die;
                break;
            case "add":

                $view = array(
                    'view' => 'numberpool/newgroup',
                    'module' => 'Новая группа'
                );
                break;
            case "additem":
                $poolname=$this->numberpool_model->Get($idpool);
                //printarray($poolname);
                $view = array(
                    'view' => 'numberpool/addnumbertogroup',
                    'module' => 'Добавление группы',
                    'var' => array(
                        'idpool'=>$poolname['id'],
                        'poolname'=>$poolname['name']
                    )
                );
                break;
            case "edit":
                /**
                 *  <td><a href="<?= baseurl('numberpool/index/edititem/' . $value['id']) ?>">Изменить</a>/
                <a href="<?= baseurl('numberpool/index/deleteitem/' . $value['id']) ?>">Удалить</a></td>
                 */
                $data=$this->numberpool_model->GetNumbers($idpool);
                $pool=$this->numberpool_model->Get($idpool);
                $view = array(
                    'view' => 'numberpool/edittable',
                    'module' => 'Изменить группу',
                    'var' => array(
                        'listnumber' => $data,
                        'idpool' => $idpool,
                        'pool' => $pool
                        )
                );
                break;
            case "deleteitem":
                if(is_array($_POST['select'])) {
                    //foreach($_POST['select'] as $key=>$value){ 
                        $this->numberpool_model->DeleteNumber($idpool,$_POST['select']);
                    //}
                }
                header("location: ".baseurl("numberpool/index/edit/".$idpool));
                break;
            case "delete":
                $this->numberpool_model->Delete($idpool);

            default :
                $allpool=$this->numberpool_model->GetAllPools();
                $view = array(
                    'view' => 'numberpool/grouplist',
                    'module' => 'Таблица групп номеров',
                    'var' => array(
                        'timestart'=>date('Y-m-d H:i:s'),
                        'pools'=>$allpool
                    )
                );
        }


        $this->view($view);
    }

    public function logout() {
        $this->user_model->logout();
        header('Location: '.baseurl());
    }
    function cp1251_to_utf8($s)
    {
        if ((mb_detect_encoding($s,'UTF-8,CP1251')) == "WINDOWS-1251")
        {
            $c209 = chr(209); $c208 = chr(208); $c129 = chr(129);
            $t="";
            for($i=0; $i<strlen($s); $i++)
            {
                $c=ord($s[$i]);
                if ($c>=192 and $c<=239) $t.=$c208.chr($c-48);
                elseif ($c>239) $t.=$c209.chr($c-112);
                elseif ($c==184) $t.=$c209.$c209;
                elseif ($c==168)    $t.=$c208.$c129;
                else $t.=$s[$i];
            }
            return $t;
        }
        else
        {
            return $s;
        }
    }
}