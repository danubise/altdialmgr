<?php
class Core_model {
    public $log;
    public function __construct() {
        session_start();
        $this->log = new Log();
    }

    function __get($key) {
        $Core =& get_instance();
        return $Core->$key;
    }

}