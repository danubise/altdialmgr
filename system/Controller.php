<?php
/**
 * Created by Unix develop team.
 * User: vlad
 * Date: 26.02.15
 * Time: 22:56
 */
class Core_controller {

    public $module_name;
    public $access_metod;

    public function __construct() {
        $this->access_metod = array('load_model','load_lib','view','set_config');
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

        if(!empty($param['page']) && file_exists(layout.$param['page'].EXT)) {
            include (layout.$param['page'].EXT);
            return true;
        }

        $CONTENT = views.$param['view'].EXT;
        include(views.'layout/main'.EXT);

    }

    public function set_config($config) {
        global $Core;
        $Core->set_config($config);
    }
}