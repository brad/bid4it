<?php
 
function init($site_path, $dataface_url){
	if (defined('DATAFACE_SITE_PATH')){
		trigger_error("Error in ".__FILE__."
			DATAFACE_SITE_PATH previously defined when trying to initialise the site."/*.Dataface_Error::printStackTrace()*/, E_USER_ERROR);
	}
	
	if (defined('DATAFACE_URL')){
		trigger_error("Error in ".__FILE__."
			DATAFACE_URL previously defined when trying to initialise the site."/*.Dataface_Error::printStackTrace()*/, E_USER_ERROR);
	}
	define('DATAFACE_SITE_PATH', dirname($site_path));
	$temp_site_url = dirname($_SERVER['PHP_SELF']);
	if ( $temp_site_url{strlen($temp_site_url)-1} == '/'){
		$temp_site_url = substr($temp_site_url,0, strlen($temp_site_url)-1);
	}
	define('DATAFACE_SITE_URL', $temp_site_url);
	define('DATAFACE_SITE_HREF', (DATAFACE_SITE_URL != '/' ? DATAFACE_SITE_URL.'/':'/').basename($_SERVER['PHP_SELF']) );
	define('DATAFACE_URL', $dataface_url);
	
	require_once(dirname(__FILE__).'/config.inc.php');
}

