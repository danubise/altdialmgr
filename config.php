<?php


///////////////////////////////////////////////////////////////
/**
 * SYSTEM CONFIG START
 */
///////////////////////////////////////////////////////////////
include ('internal_config.php');
$DEBUG = FALSE; //Выключаем режим дебага

$system_path = 'system';

$application_folder = 'application';

$pub_folder = 'pub';

$core_dir = 'eventmonitor';

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
define('COREPATH', __DIR__.'/');
define('BASEPATH', COREPATH.$system_path.'/');
define('APPPATH', COREPATH.$application_folder.'/');
define('EXT', '.php');
define('libs', APPPATH.'libs/');
define('models', APPPATH.'models/');
define('config', APPPATH.'data/');
define('layout', APPPATH.'views/layout/');
define('cron', APPPATH.'cron/');
define('SITE_TITLE', '');

/**
 * Mysql db config
 */
define('db_lib', libs.'mysql.php');

///////////////////////////////////////////////////////////////
/**
 *
 *  SYSTEM CONFIG END
 *
 */
///////////////////////////////////////////////////////////////
