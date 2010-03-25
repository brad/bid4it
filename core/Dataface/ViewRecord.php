<?php
import('Dataface/Record.php');
class Dataface_ViewRecord extends Dataface_Record {

	var $records=array();
	var $tableRefs=array();
	var $view=null;
	var $readOnlyValues=array();
	
	function Dataface_ViewRecord($viewname, $values=null){
		if ( is_a($viewname, 'Dataface_View') ) $this->view =& $viewname;
		else $this->view =& Dataface_View::loadView($viewname);
		
		if ( isset($values) ) $this->setValues($values);
		
	}
	
	function setValues($values, $init=false){
		foreach ( array_keys($values) as $key ){
			$this->setValue($key, $values[$key]);
		}
		return true;
	}
	
	function setValue($key, $value, $init=false){
		$tablename = $this->view->getTableName($key);
		$table =& $this->view->getTableOrView($tablename);
		if ( !isset($this->records[$tablename])){
			// The record for this value has not been created yet.
			$this->records[$tablename] = $table->newRecord();
		} 
		$realKey = $this->view->getRealFieldName($key);
		if ( !isset($realKey) ){
			// if the column name is 'null' then it is the result of a function
			// or an expression - I.E. the column is read-only.
			$this->readOnlyValues[$key] = $value;
		} else {
			$res = $this->records[$tablename]->setValue($realKey, $value);
			if ( PEAR::isError($res) ) return $res;
		}
		return true;
		
	}
	
	function getValue($key){
		$tablename = $this->view->getTableName($key);
		$realKey = $this->view->getRealFieldName($key);
		$tablename = $this->view->getTableName($key);
		if ( !isset($this->records[$tablename]) ) return null;
		$record =& $this->records[$tablename];
		return $record->getValue($realKey);
	}

	function getValues(){
		$out = array();
		foreach ( array_keys($this->view->fields()) as $fieldname) {
			$tablename = $this->view->getTableName($fieldname);
			if ( isset($tablename) ){
				$realKey = $this->view->getRealFieldName($fieldname);
				$out[$fieldname] = $this->records[$tablename]->getValue($realKey);
			} else if ( isset($this->readOnlyValues[$fieldname]) ){
				$out[$fieldname] = $this->readOnlyValues[$fieldname];
			}
		}
		
		return $out;
	}
	
	
	
	function save($keys=null){
		foreach ( array_keys($this->records) as $key ){
			$res = $this->records[$key]->save($this->view->mapValuesToTable($keys));
			if ( PEAR::isError($res) ){
				return $res;
			} else if ( !$res ){
				return PEAR::raiseError('Error saving view record.'. Dataface_Error::printStackTrace(), E_USER_ERROR);
			}
		}
		return true;
		
		
	}
	
	

}
