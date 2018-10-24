<?php

class Home extends Core_controller {
    public function __construct() {
        parent::__construct();
        $this->module_name = 'Main page';
    }

    public function index() {
        header('Location: '.baseurl("service"));
    }

    public function logout() {
        $this->user_model->logout();
        header('Location: '.baseurl());
    }
}