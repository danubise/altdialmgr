<?php
/**
 * Created by Unix develop team.
 * User: vlad
 * Date: 28.02.15
 * Time: 22:39
 */
class Home extends Core_controller {
    public function __construct() {
        parent::__construct();
        $this->module_name = 'Главная страница';
    }

    public function index() {
       $this->view(
	array(
	    'view' => 'test'
	)
       );
    }

    public function logout() {
        $this->user_model->logout();
        header('Location: '.baseurl());
    }
}