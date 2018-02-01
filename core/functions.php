<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 22.05.15
 * Time: 17:11
 */
function logger1($data,$id="",$view=false){
    $file="/var/log/asterisk/agi.log";
    $td=date('Y-m-d H:i:s');
    $scriptname="checker.php";

    $head="$td $scriptname $id ";
    $data=$head.$data;
    $data=str_replace("\n","\n".$head,$data);
    $data=trim($data)."\n";
    if($data==""){
        $data="'' - empty";
    }

        if ($view) {
            echo $data;
        } else {
            file_put_contents($file, $data, FILE_APPEND);
        }
}
function logger($data,$id="",$view=false){
    if(is_array($data)){
        foreach($data as $key=>$value){
            logger1($key."=>",$id,$view);
            if(is_array($value)){
                logger1("array",$id,$view);
                logger($value,$id,$view);
            }
            else{
                logger1($value,$id,$view);
            }
        }
    }else {
        logger1($data,$id,$view);
    }
}