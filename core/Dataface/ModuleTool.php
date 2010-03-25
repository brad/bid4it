<?php
/**
 * A tool to help manage and work with modules.  Use this class to load and install
 * modules, and perform maintenance on them.
 */
class Dataface_ModuleTool {

	var $_modules;
	
	
	function &getInstance(){
		static $instance = 0;
		
		if ( !is_object($instance) ){
			$instance =& new Dataface_ModuleTool();
			
		}
		return $instance;
	
	}
	
	/**
	 * Displays a block as defined in all of the registered modules.
	 * @param string $blockName The name of the block.
	 * @param array $params Parameters that are passed to the block.
	 * @returns boolean True if at least one module defines this block.
	 *					False otherwise.
	 * @since 0.6.14
	 * @author Steve Hannah <shannah@sfu.ca>
	 * @created Feb. 27, 2007
	 */
	function displayBlock($blockName, $params=array()){
		//echo "here";
		$app =& Dataface_Application::getInstance();
		if ( !isset($app->_conf['_modules']) or count($app->_conf['_modules']) == 0 ){
			return false;
		}
		$out = false;
		foreach ($app->_conf['_modules'] as $name=>$path){
			//echo "Checking $name : $path";
			$mod =& $this->loadModule($name);
			if ( method_exists($mod,'block__'.$blockName) ){
				//echo "Method exists";
				$res = call_user_func(array(&$mod, 'block__'.$blockName), $params);
				if ( !$res !== false ){
					$out = true;
				}
				
			}
		}
		return $out;
	}
	
	/**
	 * Loads a module and returns a reference to it.
	 * @param string $name The name of the module's class.
	 *
	 */
	function loadModule($name){
		$app =& Dataface_Application::getInstance();
		
		if ( isset($this->_modules[$name]) ) return $this->_modules[$name];
		if ( class_exists($name) ){
			$this->_modules[$name] =& new $name;
			return $this->_modules[$name];
		}
		
		if ( !@$app->_conf['_modules'] or !is_array($app->_conf['_modules']) or !isset($app->_conf['_modules'][$name]) ){
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.ModuleTool.loadModule.ERROR_MODULE_DOES_NOT_EXIST',
					"The module '$name' does not exist.",
					array('name'=>$name)
					)
				);
		}
		import($app->_conf['_modules'][$name]);
		if ( !class_exists($name) ){
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.ModuleTool.loadModule.ERROR_CLASS_DOES_NOT_EXIST',
					"Attempted to load the module '$name' from path '{$app->_conf['_modules'][$name]}' but after loading - no such class was found.  Please check to make sure that the class is defined.  Or you can disable this module by commenting out the line that says '{$name}={$app->_conf['_modules'][$name]}' in the conf.ini file.",
					array('name'=>$name,'path'=>$app->_conf['_modules'][$name])
					)
				);
		}
		$this->_modules[$name] =& new $name;
		return $this->_modules[$name];
	}
	
	/**
	 * Load modules.
	 */
	function loadModules(){
		$app =& Dataface_Application::getInstance();
		if ( @$app->_conf['_modules'] and is_array($app->_conf['_modules']) ){
			foreach ( array_keys($app->_conf['_modules']) as $module){
				$this->loadModule($module);
			}
		}
	}
	
	
	/**
	 * Returns an array of modules that require migrations.  [Module Name] -> [Description of migration]
	 * performed.
	 */
	function getMigrations(){
		$this->loadModules();
		$out = array();
		foreach ($this->_modules as $name=>$mod ){
			if ( method_exists($mod, 'requiresMigration') and ( $req = $mod->requiresMigration()) ){
				$out[$name] = $req;
			}
		}
		
		return $out;
		
	}
	
	
	
	
	/**
	 * Performs migrations on the specified modules.
	 * @param $modules The names of the modules to migrate. If omitted, all modules will be migrated.
	 * @returns an associative array of log entries for each migration.
	 */
	function migrate($modules=array()){
		$log = array();
		$this->loadModules();
		$migrations = $this->getMigrations();
		foreach ($modules as $mod){
			$mod_obj = $this->loadModule($mod);
			//print_r($mod_obj);exit;
			if ( isset($migrations[$mod]) and method_exists( $mod_obj, 'migrate' ) ){
				$log[$mod] = $mod_obj->migrate();
			}
			unset($mod_obj);
		}
		
		return $log;
	}
	
	/**
	 * Installs the specified modules.
	 * @param array $modules Array of module names to install.
	 * @returns Associative array of status messages: [Module Name]-> [Install status]
	 */
	function install($modules=array()){
		$log = array();
		$this->loadModules();
		$migrations = $this->getMigrations();
		foreach ($modules as $mod){
			$mod_obj = $this->loadModule($mod);
			//print_r($mod_obj);exit;
			
			if ( !$this->isInstalled($mod) and method_exists($mod_obj,'install') ){
				$log[$mod] = $mod_obj->install();
			}
			
			unset($mod_obj);
		}
		
		return $log;
	}
	
	/**
	 * Indicates whether a given module is currently installed.
	 * @returns boolean True if it is installed.
	function isInstalled($moduleName){
		$mod_obj = $this->loadModule($mod);
		if ( PEAR::isError($mod_obj) ) return false;
		if ( method_exists($mod_obj,'isInstalled')) return $mod_obj->isInstalled();
		return false;
	}
	
	/**
	 * Returns a list of names of modules that are currently installed.
	 */
	function getInstalledModules(){
		$out = array();
		$this->loadModules();
		foreach ($this->_modules as $name=>$mod){
			if ( $this->isInstalled($name) ) $out[] = $name;
		}
		return $out;
	}
	
	/**
	 * Returns an array of names of modules that have not been installed, but
	 * can be installed.
	 */
	function getUninstalledModules(){
		$out = array();
		$this->loadModules();
		foreach ($this->_modules as $name=>$mod){
			if ( !$this->isInstalled($name) ) $out[] = $name;
		}
		return $out;
	}
}
