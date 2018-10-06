<?php

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