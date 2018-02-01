<?php
/**
 * Created by PhpStorm.
 * User: slava
 * Date: 01.12.15
 * Time: 17:49
 */

include('Parcer.php');
include('log.php');

$data="[Dec  1 17:46:08] VERBOSE[31477][C-004813b7] app_dial.c:     -- SIP/orphy-008630b1 is making progress passing it to Local/8801827753603@checker-00000c87;2";
$p = new Parcer();
$d=$p->setlogline($data);
echo "*****";
print_r($d);