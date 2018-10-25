<?php
class Service extends Core_controller {
    public function __construct() {
        parent::__construct();
        $this->module_name = 'Service';
    }

    public function index() {
        $queueList = $this->db->select("`extension` FROM `queues_config` ORDER BY `extension` ASC");
        $queueListActive =$this->db->select("`extension` FROM `eventm_activeq` ORDER BY `extension` ASC");
        $queueListCurrent =$this->db->select("`extension` FROM `eventm_current` ORDER BY `extension` ASC");
        $restartStatus = $this->db->select("`propertyvalue` FROM `eventm_settings` WHERE `propertyname` = 'restart'",false);
        $activatelog = $this->db->select("`propertyvalue` FROM `eventm_settings` WHERE `propertyname` = 'activatelog'",false);
        if(is_array($queueListActive)) {
            foreach ($queueList as $key => $queue) {
                foreach ($queueListActive as $key1 => $queueActive) {
                    if ($queue == $queueActive) {
                        unset($queueList[$key]);
                    }
                }
            }
        }
        $this->view(
            array(
                'view' => 'service/index',
                'var' => array(
                    'queueList' => $queueList,
                    'queueListActive' => $queueListActive,
                    'queueListCurrent' => $queueListCurrent,
                    'restartStatus' => $restartStatus,
                    'activatelog' => $activatelog
                )
            )
        );
    }

    public function logout() {
        $this->user_model->logout();
        header('Location: '.baseurl());
    }
    public function setparam(){

        $action = $_POST['action'];
        switch ($action){
            case "add":
                $queuesAvailable = $_POST['queuesAvailable'];
                if(isset($queuesAvailable) && is_array($queuesAvailable)) {
                    foreach ($queuesAvailable as $key => $queue) {
                        $this->db->insert("eventm_activeq", array("extension" => $queue));
                        //echo $this->db->query->last;
                    }
                }
                break;
            case "del":
                //echo "11111";
                $queuesActive = $_POST['queuesActive'];
                //printarray($_POST);
                if(isset($queuesActive) &&is_array($queuesActive)) {
                    foreach ($queuesActive as $key => $queue) {
                        $this->db->query ("DELETE FROM `eventm_activeq` WHERE `extension` =  '".$queue."'");
                        //echo $this->db->query->last;
                    }
                }
                break;
            case "submit":
                $this->logActivate();
                $this->submit();
                die;
                break;
        }
        $this->index();
    }
    public function logActivate(){
        if(isset($_POST['activatelog'])){
            $this->db->update("eventm_settings", array("propertyvalue" => "1"), "`propertyname`='activatelog'");
        }else{
            $this->db->update("eventm_settings", array("propertyvalue" => "0"), "`propertyname`='activatelog'");
        }
    }
    public function submit(){
//        printarray($_POST);
//        die;
        $queueListActive =$this->db->select("`extension` FROM `eventm_activeq` ORDER BY `extension` ASC");
        if(is_array($queueListActive)) {
            $this->db->query ("DELETE FROM `eventm_current` WHERE 1");
            foreach ($queueListActive as $key=>$queue){
                $this->db->insert("eventm_current", array("extension" => $queue));
            }
        }
        $this->index();
    }
    public function activate(){
        $this->db->update("eventm_settings", array("propertyvalue" => "1"), "`propertyname`='restart'");
        //echo $this->db->query->last;
        $this->index();
    }
}