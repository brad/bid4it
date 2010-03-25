<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
class Dataface_ValuelistTool {

	
	var $_valuelists = array();
	
	function Dataface_ValuelistTool(){
	
		$this->_loadValuelistsIniFile();
			
	
	}
	
	function _valuelistsIniFilePath(){
		return DATAFACE_SITE_PATH.'/valuelists.ini';
	}
	
	function _hasValuelistsIniFile(){
		return file_exists($this->_valuelistsIniFilePath());
	}
	
	function _loadValuelistsIniFile(){
		if ( !isset( $this->_valuelists ) ){
			$this->_valuelists = array();
		}
		$valuelists =& $this->_valuelists;
		
		if ( $this->_hasValuelistsIniFile() ){
			
			
			$conf = parse_ini_file( $this->_valuelistsIniFilePath(), true);
			
			foreach ( $conf as $vlname=>$vllist ){
				$valuelists[$vlname] = array();
				if ( is_array( $vllist ) ){
					foreach ( $vllist as $key=>$value ){
						
						if ( $key == '__sql__' ) {
							// we perform the sql query specified to produce our valuelist.
							// the sql query should return two columns only.  If more are 
							// returned, only the first two will be used.   If one is returned
							// it will be used as both the key and value.
							$res = df_query($value);
							if ( $res ){
								while ($row = mysql_fetch_row($res) ){
									$valuekey = $row[0];
									$valuevalue = count($row)>1 ? $row[1] : $row[0];
									$valuelists[$vlname][$valuekey] = $valuevalue;
									
									if ( count($row)>2 ){
										$valuelists[$vlname.'__meta'][$valuekey] = $row[2];
									}
								}
							} else {
								trigger_error('Valuelist sql query failed: '.$value.': '.mysql_error().Dataface_Error::printStackTrace(), E_USER_NOTICE);
							}
						
						} else {
							$valuelists[$vlname][$key] = $value;
						}
					}
				}
				
				
			}
			
		} 
	}
	
	
	function &getInstance(){
		static $instance = 0;
		if ( $instance === 0 ){
			$instance = new Dataface_ValuelistTool();
		}
		return $instance;
	}
	
	function &getValuelist($name){
		if ( !is_a($this, 'Dataface_ValuelistTool') ){
			$vlt =& Dataface_ValuelistTool::getInstance();
		} else {
			$vlt =& $this;
		}
		
		if ( isset($vlt->_valuelists[$name] ) ){
			return $vlt->_valuelists[$name];
		}
		
		trigger_error("Request for valuelist '$name' that does not exist in Dataface_ValuelistTool::getValuelist().\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
	}
	
	
	function hasValuelist($name){
		if ( !is_a($this, 'Dataface_ValuelistTool') ){
			$vlt =& Dataface_ValuelistTool::getInstance();
		} else {
			$vlt =& $this;
		}
		return isset( $vlt->_valuelists[$name]);
	}
	
	/**
	 * Obtains reference to valuelists associative array.
	 */
	function &valuelists(){
		if ( !is_a($this, 'Dataface_ValuelistTool') ){
			$vlt =& Dataface_ValuelistTool::getInstance();
		} else {
			$vlt =& $this;
		}
		
		return $vlt->_valuelists;
	}
	
	/**
	 * Adds a value to a valuelist.  This only works for valuelists
	 * that are pulled from the database.
	 * @param Dataface_Table The table to add the valuelist to.
	 * @param string $valuelistName The name of the valuelist.
	 * @param string $value The value to add.
	 * @param string $key The key to add.
	 * @param boolean $checkPerms If true, this will first check permissions
	 *		  before adding the value.
	 * @returns mixed May return a permission denied error if there is insufficient
	 *			permissions.
	 */
	function addValueToValuelist(&$table, $valuelistName,  $value, $key=null, $checkPerms=false){

		import( 'Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$conf = $configTool->loadConfig('valuelists', $table->tablename);
		
		$relname = $valuelistName.'__valuelist';
		//$conf = array($relname=>$conf);
		$table->addRelationship( $relname, $conf[$valuelistName]);
		$rel =& $table->getRelationship($relname);
		$fields =& $rel->fields();
		if ( count($fields) > 1 ) {
			$valfield = $fields[1];
			$keyfield = $fields[0];
		}
		else {
			$valfield = $fields[0];
			$keyfield = $fields[0];
		}
		
		$record = new Dataface_Record($table->tablename);
		$rrecord = new Dataface_RelatedRecord($record, $relname);
		if ( $checkPerms and !$rrecord->checkPermission('edit', array('field'=>$valfield)) ){
			return Dataface_Error::permissionDenied();
		}
		$rrecord->setValue($valfield, $value);
		if (isset($key) and isset($keyfield) ){
			if ( $checkPerms and !$rrecord->checkPermission('edit', array('field'=>$keyfield)) ){
				return Dataface_Error::permissionDenied();
			}
			$rrecord->setValue($keyfield, $key);
		}
		import('Dataface/IO.php');
		$io = new Dataface_IO($table->tablename);
		$res = $io->addRelatedRecord($rrecord);
		if ( PEAR::isError($res) ) return $res;
		return array('key'=>$rrecord->val($keyfield), 'value'=>$rrecord->val($valfield));
	
	}
	
	
	

}
