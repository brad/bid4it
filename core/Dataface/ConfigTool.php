<?php
 
/**
 * A tool to manage application configuration. This can read and write configuration
 * files and transfer configuration information from database format to ini files and
 * vice versa.
 *
 */
 
require_once 'I18Nv2/I18Nv2.php';
 
class Dataface_ConfigTool {
	
	var $configTypes = array('actions','fields','relationships','valuelists','tables','lang','metadata');
	var $rawConfig = array();
	var $config = array();
	var $configLoaded = false;
	var $iniLoaded = array();
	var $configTableName = 'dataface__config';

	function Dataface_ConfigTool(){
		$this->apc_load();
		register_shutdown_function(array(&$this, 'apc_save'));
	}
	
	/**
	 * Array to lookup the name of an entity based on its ID.
	 */
	var $nameLookup = array('actions'=>array(), 'fields'=>array(), 'table'=>array(), 'relationships'=>array(), 'valuelists'=>array(),'lang'=>array());
	
	function &getInstance(){
		static $instance = 0;
		if (!$instance ){
			$instance = new Dataface_ConfigTool();
		}
		return $instance;
	}
	
	/**
	 * Loads configuration information of particular type for a particular table.
	 * @param $type The type of config information to load. (e.g., 'actions', 'fields', 'table', 'relationships')
	 * @param $table The name of the table for which to load configuration info.
	 * @return A 2-dimensional associative array modelling the configuration information that has been returned.
	 */
	function &loadConfig($type=null, $table=null){
		$out =& $this->loadConfigFromINI($type, $table);
		return $out;
	}
	
	
	/**
	 * Loads configuration information from an INI file.
	 * @param $type The type of configuration information to load: e.g., actions, relationships, valuelists, fields, etc..
	 * @param $tablename The name of the table for which to load the configuration information.
	 * @return Associative array of configuration options in the same form as they would be returned by parse_ini_file().
	 */
	 
	function &loadConfigFromINI($type=null, $tablename=null){
		$app =& Dataface_Application::getInstance();
		if ( $type == 'lang' ){
			if ( isset($this->config[$type][$app->_conf['lang']][$tablename]) ){
				return $this->config[$type][$app->_conf['lang']][$tablename];
			}
		} else {
			if ( isset( $this->config[$type][$tablename] ) ){
				return $this->config[$type][$tablename];
			}
		} 
		$app =& Dataface_Application::getInstance();
		$paths = array();
		$lpaths = array();
		if ( $type === 'lang' ){
			
			if ( $tablename !== null ){
				$lpaths[] = DATAFACE_SITE_PATH.'/tables/'.$tablename.'/lang/'.$app->_conf['lang'].'.ini';
				
			} else {
				$paths[] = DATAFACE_PATH.'/lang/'.$app->_conf['lang'].'.ini';
				$lpaths[] = DATAFACE_SITE_PATH.'/lang/'.$app->_conf['lang'].'.ini';
			}
		
		} else if ( $tablename !== null ){
			//$paths = array(DATAFACE_SITE_PATH.'/tables/'.$tablename.'/'.$type.'.ini');
			$paths[] = DATAFACE_PATH.'/'.$type.'.ini';
			$lpaths[] = DATAFACE_SITE_PATH.'/'.$type.'.ini';
			$lpaths[] = DATAFACE_SITE_PATH.'/tables/'.$tablename.'/'.$type.'.ini';
			
		} else {
			
			$paths[] = DATAFACE_PATH.'/'.$type.'.ini';
			$lpaths[] = DATAFACE_SITE_PATH.'/'.$type.'.ini';
		}
		
		// Add the ability to override settings in a module.
		// Added Feb. 28, 2007 by Steve Hannah for version 0.6.14
		if ( isset($app->_conf['_modules']) and count($app->_conf['_modules']) > 0 ){
			foreach ( $app->_conf['_modules'] as $classname=>$path ){
				$modpath = explode('_',$classname);
				$modname = $modpath[count($modpath)-1];
				if ( $type == 'lang' ){
					$paths[] = DATAFACE_PATH.'/modules/'.$modname.'/lang/'.$app->_conf['lang'].'.ini';
				} else {
					$paths[] = DATAFACE_PATH.'/modules/'.$modname.'/'.$type.'.ini';
				}
			}
		}
		
		// Add the ability to override settings in the database.
		// Added Feb. 27, 2007 by Steve Hannah for version 0.6.14
		if ( @$app->_conf['enable_db_config']  and $type != 'permissions'){
			if ( $type == 'lang' ){
				if ( isset($tablename) ){
					$lpaths[] = 'db:tables/'.$tablename.'/lang/'.$app->_conf['lang'];
				} else {
					$paths[] = 'db:lang/'.$app->_conf['lang'].'.ini';
				}
			} else {
				if ( isset($tablename) ){
					$paths[] = 'db:'.$type.'.ini';
					$lpaths[] = 'db:tables/'.$tablename.'/'.$type.'.ini';
				} else {
					$paths[] = 'db:'.$type.'.ini';
				}
			}
		}
		
		if ( !$tablename ){
			$tablename = '__global__';
			
		}

		$paths = array_merge($paths, $lpaths);
		//print_r($paths);
		//print_r($lpaths);
		if ( !isset( $this->config[$type][$tablename] ) ) $this->config[$type][$tablename] = array();
		//import('Config.php');

		foreach ( $paths as $path ){
			if ( !isset( $this->iniLoaded[$path] ) ){
				$this->iniLoaded[$path] = true;
				
				if ( is_readable($path) || strstr($path,'db:') == $path ){
					
					
					$config = $this->parse_ini_file($path, true);
				
					if ( isset( $config['charset'] ) and function_exists('iconv') ){
						I18Nv2::recursiveIconv($config, $config['charset'], 'UTF-8');
					}
					
					
					if ( isset($config['__extends__']) ){
						$config = array_merge_recursive_unique($this->loadConfigFromINI($type, $config['__extends__']), $config);
					}
					
					$this->rawConfig[$path] =& $config;
					
				} else {
					$config = array();
					$this->rawConfig[$path] =& $config;
				}
			} else {
				//echo "getting $path from raw config.";
				//echo "$path already loaded:".implode(',', array_keys($this->iniLoaded));
				$config =& $this->rawConfig[$path];
			}
					
					
			//echo "Conf for x".$path."x: ";
			if ( !$config ) $config = array();
			foreach ( array_keys($config) as $entry ){
				if ( $type == 'lang'){
					$this->config[$type][$app->_conf['lang']][$tablename][$entry] =& $config[$entry];
				} else {
					if ( strpos($entry, '>') !== false ){
						list($newentry,$entryParent) = explode('>', $entry);
						$this->config[$type][$tablename][trim($newentry)] = array_merge($this->config[$type][$tablename][trim($entryParent)],$config[$entry]);
					} else {
						$this->config[$type][$tablename][$entry] =& $config[$entry];
					}
					
				}
			}
			
			unset($config);
		}
		if ( $type == 'lang' ){
			return $this->config[$type][$app->_conf['lang']][$tablename];
		} else {
			return $this->config[$type][$tablename];
		}
		
	}
	
	function apc_save(){
		if ( function_exists('apc_store') and defined('DATAFACE_USE_CACHE') and DATAFACE_USE_CACHE ){
			$res = apc_store($this->apc_hash().'$config', $this->config);
			$res2 = apc_store($this->apc_hash().'$iniLoaded', $this->iniLoaded);
			
		}
	}
	
	function apc_load(){
		if ( function_exists('apc_fetch') and defined('DATAFACE_USE_CACHE') and DATAFACE_USE_CACHE ){
			$this->config = apc_fetch($this->apc_hash().'$config');
			$this->iniLoaded = apc_fetch($this->apc_hash().'$iniLoaded');
		}
	}
	
	function apc_hash(){
		$appname = basename(DATAFACE_SITE_PATH);
		return __FILE__.'-'.$appname;
	}
	
	
	
	/**
	 * Scours the tables directory to load all configuration information from the ini files.
	 */
	function loadAllConfigFromINI(){
		$tables_path = DATAFACE_SITE_PATH.'/tables';
		$dir = dir($tables_path);
		while ( false !== ( $entry = $dir->read() ) ){
			if ( $entry === '.' || $entry === '..' ) continue;
			$full_path = $tables_path.'/'.$entry;
			if ( is_dir($full_path) ){
				foreach ( $this->configTypes as $type ){
					$this->loadConfigFromINI($type, $entry);
				}
			}
		}
		foreach ($this->configTypes as $type){
			// load global properties.
			$this->loadConfigFromINI($type, null);
		}
		
	}
	
	function loadAllConfig(){
		$app =& Dataface_Application::getInstance();
		switch( strtolower($app->_conf['config_storage']) ){
			case 'db':
			case 'sql':
			case 'database':
				$this->loadConfigFromDB();
				break;
			case 'ini':
				$this->loadAllConfigFromINI();
				break;
				
		}
	
	}
	
	
	
	function parse_ini_file($path, $sections=false){
		static $config = 0;
		if ( !is_array($config) ){
			$config = array();
		}
		
		

		$app =& Dataface_Application::getInstance();
		//echo "Checking for $path";
		if ( strstr($path, 'db:') == $path ){
			$path = substr($path, 3);
			if ( !is_array($config) ){
				$config = array();
				if ( class_exists('Dataface_AuthenticationTool') ){
					$auth =& Dataface_AuthenticationTool::getInstance();
					$username = $auth->getLoggedInUsername();
				} else {
					$username = null;
				}
				
				
				$sql = $this->buildConfigQuery($path, $username, $app->_conf['lang']);
				$res = @mysql_query($sql, $app->db());
				if (!$res ){
					$this->createConfigTable();
					$res = mysql_query($sql, $app->db());
				}
				if ( !$res ){
					return $config;
				}
				while ( $row = mysql_fetch_assoc($res) ){
					if ( !$row['section'] ){
						$config[$row['file']][$row['key']] = $row['value'];
					} else {
						$config[$row['file']][$row['section']][$row['key']] = $row['value'];
					}
				}
				@mysql_free_result($res);
				
			
			}

			if ( !@$config[$path] ){

				return array();
			}
			
			return $config[$path];
			
		} else {
			if ( !(DATAFACE_EXTENSION_LOADED_APC && (filemtime($path) < apc_fetch($this->apc_hash().$path.'__mtime')) && ( $config[$path]=apc_fetch($this->apc_hash().$path) ) ) ){
				
				$config[$path] =  parse_ini_file($path, $sections);
				if ( DATAFACE_EXTENSION_LOADED_APC ){
					apc_store($this->apc_hash().$path, $config[$path]);
					apc_store($this->apc_hash().$path.'__mtime', time());
				}
			} else {
				//
			}
			
			
			return $config[$path];
			
		}
			
	}
	
	function buildConfigQuery($path, $username, $lang, $where=null){
		$sql = "select * from `".$this->configTableName."` where (`lang` IS NULL OR `lang` = '".$lang."') and ( `username` IS NULL";
		if ( isset($username) ){
			$sql .= " OR `username`	= '".addslashes($username)."')";
		} else {
			$sql .= ')';
		}
		if ( isset($where) ) $sql .= ' and ('.$where.')';
				
				
		$sql .= ' ORDER BY `priority`';
		return $sql;
	}
	
	
	function createConfigTable(){
		import('Dataface/ConfigTool/createConfigTable.function.php');
		return Dataface_ConfigTool_createConfigTable();
	}
	
	function setConfigParam($file, $section, $key, $value, $username=null, $lang=null, $priority=5){
		import('Dataface/ConfigTool/setConfigParam.function.php');
		return Dataface_ConfigTool_setConfigParam($file, $section, $key, $value, $username, $lang, $priority);
	}
	
	function clearConfigParam($file, $section, $key, $value, $username=null, $lang=null){
		import('Dataface/ConfigTool/clearConfigParam.function.php');
		return Dataface_ConfigTool_setConfigParam($file, $section, $key, $value, $username, $lang);
	}

}

