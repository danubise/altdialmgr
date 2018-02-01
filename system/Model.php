<?php
/**
 * Created by Unix develop team.
 * User: vlad
 * Date: 26.02.15
 * Time: 22:22
 */

class Core_model {
    public function __construct() {
        session_start();
    }

    function __get($key) {
        $Core =& get_instance();
        return $Core->$key;
    }

}