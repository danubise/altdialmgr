<?php

class Home extends Core_controller {
    private $filename = "home.php ";
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $this->view(
            array(
                'view' => 'layout/main'
            )
        );
    }

    public function login() {
        if($this->user_model->auth($_POST['login'],$_POST['pass'])) {
            
        }
        header("Location: ".baseurl(''));
    }
}