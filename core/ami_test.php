<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 26.11.15
 * Time: 12:31
 */
include_once('ami.php');
$ami=new Ami();

$cannel="Local/784523125485@checker-000001ff;2";
$r1 = $ami->getchanneldetail($cannel);
print_r($r1);