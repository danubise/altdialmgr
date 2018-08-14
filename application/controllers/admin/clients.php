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

    public function add() {
        $view = array(
                    'view' => 'clients/add',
                    'module' => 'Add new client',
                    'var' => array(
                    )
                );
        $this->view($view);
    }

    public function adduser() {
        printarray($_POST);
        die;
        $this->index();
    }
}
?>