<?php

class Clients extends Core_controller {
    public function __construct() {
            parent::__construct();
            $this->module_name = 'Группы номеров';
            $this->load_model('list_model');
            $this->load_model('numberpool_model');
        }

    public function index() {
        $users = $this->db->select("* from `users` where `login` != 'admin'");
        $view = array(
                    'view' => 'clients/clientslist',
                    'module' => 'List of the clients',
                    'var' => array(
                        'users'=>$users
                    )
                );
        $this->view($view);
    }

    public function add($userdata) {
        $view = array(
                    'view' => 'clients/add',
                    'module' => 'Add new client',
                    'var' => array(
                        'userdata'=>$userdata
                    )
                );
        $this->view($view);
    }

    public function adduser() {
        $userdata=$_POST;
        if($this->user_model->clientadd($userdata)){
            $this->index();
            return;
        }else{
            $userdata['failed'] = true;
            $this->add($userdata);
        }
    }

    public function userdelete($username) {
        $this->user_model->userdelete($username);
        $this->index();
    }
}
?>