<?php

// Action to install and update applications.
// Created by Xataface.
 
class dataface_actions_install {

	function handle(&$params){
	
		$app =& Dataface_Application::getInstance();
		
		if ( df_get_database_version() == df_get_file_system_version() ){
			header('Location: '.DATAFACE_SITE_HREF.'?--msg='.urlencode('The application database is up to date at version '.df_get_database_version()));
			exit;
		}
		
		if ( df_get_database_version() > df_get_file_system_version() ){
			header('Location: '.DATAFACE_SITE_HREF.'?--msg='.urlencode('The database version is greater than the file system version.  Please upgrade your application to match the version in the database (version '.df_get_database_version()));
			exit;
		}
		
		if ( file_exists('conf/Installer.php') ){
			import('conf/Installer.php');
			$installer = new conf_Installer;
			
			$methods = get_class_methods('conf_Installer');
			$methods = preg_grep('/^update_([0-9]+)$/', $methods);
			
			$updates = array();
			
			foreach ($methods as $method){
				preg_match('/^update_([0-9]+)$/', $method, $matches);
				$version = intval($matches[1]);
				if ( $version > df_get_database_version() and $version <= df_get_file_system_version() ){
					$updates[] = $version;
				}
			}
			
			sort($updates);
			
			foreach ($updates as $update ){
				$method = 'update_'.$update;
				$res = $installer->$method();
				if ( PEAR::isError($res) ) return $res;
				$res = mysql_query("update dataface__version set `version`='".addslashes($update)."'", df_db());
				if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);	
			}
			
		}
		
		$res = mysql_query("update dataface__version set `version`='".addslashes(df_get_file_system_version())."'", df_db());
		if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
		
		header('Location: '.DATAFACE_SITE_HREF.'?--msg='.urlencode('The database has been successfully updated to version '.df_get_file_system_version()));
		exit;
		
	}
}
