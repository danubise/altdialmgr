<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 20.09.18
 * Time: 15:06
 */

class Usersettings extends Core_controller
{
    private $filename="usersettings.php ";

    public function __construct() {
        parent::__construct();
        $this->module_name = 'User settings';
        $config = array(
            'log_file' => "/var/log/httpd/webaction.log",
            'log_write' => "file",

        );
        $this->log->setConfig($config);

    }
    public function index(){
        $functionName=$this->filename."function index ";
        if(isset($_POST['save'])){
            $deleteSQL="DELETE FROM  `networksettings` WHERE `userid`='".$_SESSION['id']."'";
            $this->log->debug($functionName.$deleteSQL);
            $this->db->query($deleteSQL);
            $networkSettings = array(
                'ipaddress'=> $_POST['ipaddress'],
                'port'=>$_POST['port'],
                'userid'=>$_SESSION['id']
            );
            $this->log->debug($networkSettings);
            $this->db->insert ("networksettings",$networkSettings);
            $this->log->debug($this->db->query->last);
        }

        $getNetworkSettingsSQL = "* FROM `networksettings` WHERE `userid`='".$_SESSION['id']."'";
        $this->log->debug($functionName.$getNetworkSettingsSQL);
        $networkSettings = $this->db->select($getNetworkSettingsSQL, 0 );
        $this->log->debug($networkSettings);

        $getAnumber = "`anumber` FROM `anumber` WHERE `userid`='".$_SESSION['id']."'";
        $this->log->debug($functionName.$getAnumber);
        $anumber = $this->db->select($getAnumber, 0 );
        $this->log->debug($functionName."A number is :".$anumber);

        $getPrefix = "`prefix` FROM `prefix` WHERE `userid`='".$_SESSION['id']."'";
        $this->log->debug($functionName.$getPrefix);
        $prefix = $this->db->select($getPrefix, 0 );
        $this->log->debug($functionName."Prefix is :".$prefix);

        $view = array(
            'view' => 'usersettings/index',
            'module' => 'Отчеты',
            'var' => array(
                'networkSettings'=>$networkSettings,
                'anumber'=>$anumber,
                'prefix'=>$prefix
            )
        );
        $this->view($view);
    }
}