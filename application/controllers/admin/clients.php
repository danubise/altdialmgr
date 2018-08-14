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
        echo $this->db->query->last;
        $view = array(
                    'view' => 'clients/clientslist',
                    'module' => 'List of the clients',
                    'var' => array(
                        'users'=>$users
                    )
                );
        $this->view($view);
    }
}
?>