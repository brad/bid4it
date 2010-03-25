<?php

class Dataface_Cache {
	/**
	 * The string prefix that should be prepended to all keys for using APC cache.
	 */
	var $prefix;
	
	/**
	 * The cache directory that is used (if APC is not used)
	 */
	var $cachedir;
	
	/**
	 * Array of references to variables that should be stored when updateCache()
	 * is called.
	 */
	var $monitored = array();
	
	function Dataface_Cache($cachedir=null, $prefix=null){
		if ( $cachedir === null ) $cachedir = '/tmp';
		$this->cachedir = $cachedir;
		if ( $prefix === null ) $prefix = DATAFACE_SITE_PATH;
		$this->prefix = $prefix;
		
	
	}
	
	function apc_get($key){
		return apc_fetch($this->prefix.$key);
	}
	
	function apc_set($key, &$value){
		return apc_store($this->prefix.$key, $value);
	}
	
	function get($key){
		return $this->apc_get($key);
	}
	
	function set($key, &$value){
		return $this->apc_set($key, $value);
	}
	
	function &getInstance(){
		static $instance = null;
		if ( $instance === null ){
			$instance = new Dataface_Cache();
		}
		return $instance;
	}

}

