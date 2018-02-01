<?php
/**
 * Created by Unix develop team.
 * User: vlad
 * Date: 27.02.15
 * Time: 13:00
 */
class Home extends Core_controller {
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
}