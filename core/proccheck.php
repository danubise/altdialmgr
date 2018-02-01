<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 24.09.15
 * Time: 19:17
 */
    $routename=$argv[1];
    $data = exec ("ps -ef | grep php",$mas);
    $d= explode("\n",$data);
    //echo "find - ".$routename."\n";
    $count=0;
    $finddata=array();
    foreach($mas as $key=>$value){
        $v=strripos($value,$routename);
        //echo " v= ".$v."\n";
        if($v!==false){
            $finddata[]=$value;

            $count++;
        }
    }
    if($count<=2){
        die;
    }
    //echo "count = $count\n";
    //print_r($finddata);
    //echo "{".$data."}\n";