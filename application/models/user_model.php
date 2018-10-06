<?php
class User_model extends Core_model {

    public $id = 0;
    public $group = 'guest';
    public $owner = '';
    public $status = false;

    public function __construct() {
        parent::__construct();
        $this->login();
        //print_r($this->config->system->database);
    }

    /**
     * Работа в системе с логином
     * @return bool
     */
    private function login() {
        if(!empty($_SESSION['id']) and !empty($_SESSION['hash'])) {

            $check = $this->db->select("COUNT(*) from `users` where `id`=".intval($_SESSION['id'])." and `hash`='".$_SESSION['hash']."'",0);
            if($check) {
                $this->id = intval($_SESSION['id']);
                $getOwner = $this->db->select("* from `users` where `id`=".$this->id, 0);
                $this->owner = new stdClass();
                foreach($getOwner as $key=>$value) {
                    $this->owner->{$key} = $value;
                }
                $this->group = $this->getGroup($this->owner->group);
                $this->status = true;
                return true;
            }
        }
        return false;
    }

    /**
     * Авторизация
     * @param $login
     * @param $psw
     * @return bool
     */
    public function auth($login,$psw) {
        if($this->login()===false) {
            $psw = sha1($psw);
            $id = $this->db->select("`id` from `users` where `login`='".$login."' and `password`='".$psw."' order by `id` asc limit 1",0);
            $result = $this->db->select("`password`,`hash` from `users` where `id`=".intval($id),0);
            if($psw===$result['password']) {
                $_SESSION['id'] = $id;
                $_SESSION['hash'] = $result['hash'];
                return true;
            }
            return false;
        }
    }
    public function userdelete($userid){
        $result=$this->db->query("DELETE FROM `users` WHERE `id`='$userid'");

    }
    public function useradd($login,$password){
        $exist=$this->db->select("`id` from `users` where `login`=$login",0);
        if($exist==""){
            $data=array(
                "login"=>$login,
                "password"=>sha1($password),
                "group"=>"1",
                "hash"=>sha1($password.$login)
            );
            $this->db->insert("users",$data);
            return true;
        }else{
            return false;
        }
    }
    public function clientadd($userdata){
        $exist=$this->db->select("`id` from `users` where `login`='".$userdata['login']."'",0);
        if( $exist == ""){
            $userdata['password'] = sha1($userdata['password']);
            $userdata['hash'] = sha1($userdata['password'].$userdata['login']);
            $userdata['group'] = 0;

            $this->db->insert("users",$userdata);
            return true;
        }else{
            return false;
        }
    }
    public function usersave($data){
        $id=$_POST['id'];
        $login=$_POST['login'];
        $password=$_POST['password[0]'];
        $data=array(
          "login"=>$_POST['login'],
            "password"=>sha1($password),
            "group"=>"1",
            "hash"=>sha1($password.$login)
        );
        $this->db->update("`users`",$data,"`id`='$id'");
        return true;
    }
    public function clientsave($data){
        $this->db->update("users",$data,"`id`='".$data['id']."'");
        return true;
    }
    public function users(){
        $users=$this->db->select("* from `users`");
        return $users;
    }
    public function userget($iduser){
        $user=$this->db->select("* from `users` where `id`='$iduser'",0);
        return $user;
    }

    /**
     * Выход из системы
     */
    public function logout() {
        unset($_SESSION['id'],$_SESSION['hash']);
    }


    /**
     * Получение названии системной группы по ID
     * @param $id
     * @return string
     */
    public function getGroup($id) {
        switch(intval($id)) {
            case 0:
                return "user";
            case 1:
                return "admin";
            case 2:
                return "block";
            case 3:
                return "manager";
            default: return 'guest';
        }
    }

}