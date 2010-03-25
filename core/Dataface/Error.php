<?php

require_once 'PEAR.php';

define('DATAFACE_ERROR_PERMISSION_DENIED', 10101010);
define('DATAFACE_ERROR_NO_IMPORT_FILTERS_FOUND', 10101011);
define('DATAFACE_ERROR_DUPLICATE_ENTRY', 10101100);

define('DATAFACE_E_NOTICE', 200);
define('DATAFACE_E_PERMMISSIONS', 210);
define('DATAFACE_E_PERMISSION_DENIED', 211);
define('DATAFACE_E_LOGIN_FAILURE', 212);
define('DATAFACE_E_NO_RESULTS', 250);


define('DATAFACE_E_WARNING', 100);
define('DATAFACE_E_NO_IMPORT_FILTERS_FOUND', 111);
define('DATAFACE_E_DUPLICATE_ENTRY', 112);

define('DATAFACE_E_ERROR', 300);
define('DATAFACE_E_IO_ERROR', 320);
define('DATAFACE_E_DELETE_FAILED', 321);
define('DATAFACE_E_WRITE_FAILED', 322);
define('DATAFACE_E_READ_FAILED', 323);
define('DATAFACE_E_NO_TABLE_SPECIFIED', 350);
define('DATAFACE_E_MISSING_KEY', 351);
define('DATAFACE_E_REQUEST_NOT_HANDLED', 201);

class Dataface_Error extends PEAR_Error {

	
	function stringRepresentation($arg){
		if ( is_object($arg) ) return get_class($arg).' Object';
		if ( is_array($arg) ) return 'array('.implode(',', array_map(array('Dataface_Error','stringRepresentation'), $arg)).')';
		return strval($arg);
	}
	
	function printStackTrace(){
		$debug = debug_backtrace();
		$out = "";
		foreach ($debug as $line){
			$args = '';
			if ( isset($line['args']) ){
				foreach ($line['args'] as $arg){
					$args .= substr(Dataface_Error::stringRepresentation($arg), 0, 100).',';
				}
			}
			$args = substr($args,0,strlen($args)-1);
			$out .= "On line ".@$line['line']." of file ".@$line['file']." in function ".@$line['function']."($args)\n<br>";
		}
		return $out;
	}
	
	function permissionDenied($msg="Permission Denied", $userInfo=''){
		if ( !$userInfo ) $userInfo = Dataface_Error::printStackTrace();
		$err = PEAR::raiseError($msg, DATAFACE_E_PERMISSION_DENIED, E_USER_WARNING, null, $userInfo);
		return $err;
	}
	
	function isPermissionDenied($obj){
		if ( PEAR::isError($obj) and $obj->getCode() == DATAFACE_E_PERMISSION_DENIED ) return true;
		return false;
	}
	
	function noImportFiltersFound($msg="No Import filters found", $userInfo=''){
		if ( !$userInfo ) $userInfo = Dataface_Error::printStackTrace();
		$err = PEAR::raiseError($msg, DATAFACE_E_NO_IMPORT_FILTERS_FOUND, E_USER_WARNING, null, $userInfo);
		return $err;
	
	}
	
	function isNoImportFiltersFound($obj){
		if ( PEAR::isError($obj) and $obj->getCode() == DATAFACE_E_NO_IMPORT_FILTERS_FOUND ) return true;
		return false;
	
	}
	
	function duplicateEntry($msg="This record already exists", $userInfo=''){
		if ( !$userInfo ) $userInfo = Dataface_Error::printStackTrace();
		$err = PEAR::raiseError($msg, DATAFACE_E_DUPLICATE_ENTRY, E_USER_WARNING, null, $userInfo);
		return $err;
	}
	
	function isDuplicateEntry($obj){
		if ( PEAR::isError($obj) and $obj->getCode() == DATAFACE_E_DUPLICATE_ENTRY ) return true;
		return false;
	}
	
	function isError($obj){
		if ( !PEAR::isError($obj) ) return false;
		return ($obj->getCode() >= DATAFACE_E_ERROR);
	}
	
	function isWarning($obj){
		if ( !PEAR::isError($obj) ) return false;
		return ( $obj->getCode() >= DATAFACE_E_WARNING && $obj->getCode() < DATAFACE_E_NOTICE);
	}
	
	function isNotice(&$obj){
		if ( !PEAR::isError($obj) ) return false;
		return ( $obj->getCode() >= DATAFACE_E_NOTICE and $obj->getCode() < DATAFACE_E_ERROR);
		
	}
}
