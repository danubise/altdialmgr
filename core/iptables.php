#!/bin/php
<?php
include ('/var/www/html/dialmanager/internal_config.php');
ini_set('default_charset', 'utf-8');
date_default_timezone_set("Europe/Samara");
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
$config = array(
    'log_file' => $_config['iptables_log'],
    'log_write' => "file",
    'iptables' => "/sbin/iptables",
    'chain' => "testersip",
    'localip' => "95.141.193.73"
);

include('mysqli.php');
include('log.php');
$log = new Log($config);
$db = new db($_config['mysql']);
$checkForUpdate = "`propertyvalue` FROM `commonsetting` WHERE `propertyname` ='iptables_update'";
$log->debug($checkForUpdate);
$needToUpdate = $db->select($checkForUpdate, false);
if($needToUpdate == 1){
    $disableUpdateKey = array("propertyvalue"=>0);
    $db->update("commonsetting", $disableUpdateKey, "`propertyname` ='iptables_update'");
    $log->info("Find key to update iptables rules");
    $log->debug("Flush sip chain ");
    $cmd = $config['iptables']." -F ".$config['chain'];
    $log->debug($cmd);
    executeIptablesRule($cmd);
    $getAllIp = "`ipaddress` FROM `networksettings`";
    $ipAddress = $db->select($getAllIp);
    $log->debug("Found ".sizeof($getAllIp)." ip address");

    if(is_array($ipAddress)) {
        $log->debug("Adding ip address to chain");
        foreach ($ipAddress as $key => $ip) {
            $cmd = $config['iptables']." -A ".$config['chain']." -s ".$ip." -d ".$config['localip']." -j ACCEPT";
            $log->debug($cmd);
            executeIptablesRule($cmd);
        }
    }

    $log->info("Stop iptables update");
}else{
    $log->info("The update key is ".$needToUpdate);
}

function executeIptablesRule($cmd){
    system($cmd." &>>".$GLOBALS['config']['log_file']);
}