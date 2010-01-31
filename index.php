<?php

ini_set('display_errors','on');
error_reporting(E_ALL);

$install = false;
if ( !file_exists('conf.ini') ){
	$install = true;

} else {

	$conf = parse_ini_file('conf.ini', true);
	if ( $conf['_database']['user'] == 'Your Username Here' ){
		$install = true;
	}
}

if ( $install ){
	header("Location: install.php");
	exit;
}
require_once 'include/functions.inc.php';
require_once 'config.inc.php';
require_once DATAFACE_INSTALLATION_PATH.'/dataface-public-api.php';
df_init(__FILE__, DATAFACE_INSTALLATION_URL);
$app =& Dataface_Application::getInstance();
$app->display();
?>
