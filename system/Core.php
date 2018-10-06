<?php

    define('CORE_VERSION', 3);

    $Core = new Core();

    class Core {

        private static $instance;

        public function __construct() {
            self::$instance =& $this;
            $this->db = connect_mysql();

            $this->set_config('system');
            $this->set_config('route');
            $this->load_model('user_model');
            //$this->load_model('trunk_model');
            $this->pwdUserStructure($this->user_model->group);
        }

        public function __destruct() {
           // $this->db->close();
        }

        public static function &get_instance() {
            return self::$instance;
        }

        private function pwdUserStructure($group) {
            define('controllers', APPPATH.'controllers/'.$group.'/');
            define('views', APPPATH.'views/'.$group.'/');
        }

        private function load_model($model) {
            if(connect_file(models.$model.EXT)) $this->{$model} = new $model();
        }

        public function set_config($config) {
            if(empty($this->config)) $this->config = new stdClass();
            $file = config.'config_'.$config.EXT;
            if(file_exists($file)) {
                require_once $file;
                $class = 'Config_'.$config;
                $this->config->$config = new $class();
            }
        }

    }

    require_once BASEPATH.'Route.php';



