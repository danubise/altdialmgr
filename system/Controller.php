<?php

include_once ('log.php');
class Core_controller {

    public $module_name;
    public $access_metod;
    public $log;

    public function __construct() {
        $this->access_metod = array('load_model','load_lib','view','set_config');
        $this->log = new Log();
    }

    function __get($key) {
        $Core =& get_instance();
        return $Core->$key;
    }

    public function load_model($model) {
        if(connect_file(models.$model.EXT)) $this->{$model} = new $model();
    }

    public function load_lib($lib, $conf = null) {
        if(connect_file(libs.$lib.EXT))
        $this->{$lib} = new $lib();
    }

    public function view($param) {

        if(isset($param['var'])) {
            foreach($param['var'] as $key=>$var) {
                if(!isset($$key)) $$key = $var;
            }
        }

        if(!empty($param['page'])) {
            $CONTENTPAGE = layout . $param['page'] . EXT;
            echo $CONTENTPAGE;
            if (file_exists($CONTENTPAGE)) {
                include($CONTENTPAGE);
                return true;
            }
        }
        if(!empty($param['view']) ){
            $CONTENT = views . $param['view'] . EXT;
            if( $param['view'] == "share/report" && file_exists($CONTENT)) {

                include($CONTENT);
                return true;
            }
        }

        include(views.'layout/main'.EXT);

    }

    public function set_config($config) {
        global $Core;
        $Core->set_config($config);
    }
}