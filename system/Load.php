<?php
/**
 * Created by Unix develop team.
 * User: vlad
 * Date: 26.02.15
 * Time: 22:24
 */

connect_file(db_lib);
connect_file(BASEPATH.'Functions.php');
connect_file(BASEPATH.'Controller.php');
connect_file(BASEPATH.'Model.php');


function connect_file($file) {
    if(file_exists($file)) {
        //echo '<b>Connect file:</b> '.$file.'<br>';
        require_once $file;
        return true;
    }
    return false;
}