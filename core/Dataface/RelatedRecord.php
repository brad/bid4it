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
/**
 * Description:
 * Represents a record that is part of a relationship.
 */

class Dataface_RelatedRecord {

	/**
	 * @var boolean Flag to indicate whether display methods like display()
	 * 	and htmlValue should be constrained by permissions.  Default is
	 *  true.
	 */
	var $secureDisplay = true;

	var $vetoSecurity;
	/**
	 * The base record of the relationship.
	 * @type Dataface_Record
	 */
	var $_record;
	
	/**
	 * The name of the relationship.
	 * @type string
	 */
	var $_relationshipName;
	
	/**
	 * Reference to the relationship.
	 * @type Dataface_Relationship
	 */
	var $_relationship;
	
	/**
	 * Array of values for this related record.
	 */
	var $_values;
	
	/**
	 * Array of meta data values for this related record.
	 * A Metadata value is a value that describes a data in a field of the 
	 * related record.  Currently there is only one meta value: __Tablename_Fieldname_length.
	 */
	var $_metaDataValues;
	
	/**
	 * Maps field names to the absolute column name for that field.
	 * For example if the field profileid is located in the Profiles table, then 
	 * $this->_absoluteColumnNames['profileid'] == 'Profiles.profileid'.
	 * @type array([Field name] -> [Absolute column name])
	 */
	var $_absoluteColumnNames;
	
	var $_lockedFields=array();
	/**
	 * ???
	 */
	var $_records;
	
	var $_dirtyFlags=array();
	
	var $cache=array();
	
	
	/**
	 * Constructor
	 * @param $record Reference to Dataface_Record object to which this record is related.
	 * @type Dataface_Record
	 *
	 * @param $relationshipName The name of the relationship of which this related record is a member.
	 * @type string
	 *
	 * @param $values Associative array of values for this related record.
	 * @type array([Field name] -> [Field value])
	 */
	function Dataface_RelatedRecord(&$record, $relationshipName, $values=null){
		
		if ( !is_a($record, 'Dataface_Record') ){
			trigger_error("Error in Dataface_RelatedRecord constructor.  Expected first argument to be of type 'Dataface_Record' but received '".get_class($record)."'.".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$this->_record =& $record;
		$this->_relationshipName = $relationshipName;
		$this->_relationship =& $record->_table->getRelationship($relationshipName);
		if ( is_array($values) ){
			$this->setValues($values);
			$this->clearFlags();
		}
	}
	
	function clearCache(){
		$this->cache = array();
	}
	
	/**
	 * Initializes the values array for this related record.
	 */
	function _initValues(){
		if ( !isset( $this->_values ) ){
			$fkeys = $this->_relationship->getForeignKeyValues();
			$this->_values = array();
			$this->_absoluteColumnNames = array();
			//$cols = $this->_relationship->_schema['columns'];
			$cols = $this->_relationship->fields(true); // we will get all fields - even grafted ones.
			foreach ($cols as $col){
				list($table, $field) = explode('.', $col);
				$this->_values[$field] = null;
				$this->_absoluteColumnNames[$field] = $col;
				
				/*
				 * We want to check for locked fields. Locked fields are fields that *must* have a particular
				 * value for the relationship to remain valid.
				 */
				if ( isset( $fkeys[$table][$field]) and strpos($fkeys[$table][$field],'$') ===0 ){
					$this->_lockedFields[$field] = $fkeys[$table][$field];
					$this->_values[$field] = $this->_record->parseString($fkeys[$table][$field]);
				}
			}
		}
	}
	
	function clearFlags(){
		$this->_dirtyFlags = array();
	}
	
	function setFlag($fieldname){
		$this->_dirtyFlags[$fieldname] = true;
	}
	
	function clearFlag($fieldname){
		unset($this->_dirtyFlags[$fieldname]);
	}
	
	function isDirty($fieldname){
		return ( isset($this->_dirtyFlags[$fieldname]) );
	}
	
	/**
	 * Sets a meta data value for a field.
	 */
	function setMetaDataValue($key, $value){
		if ( !isset( $this->_metaDataValues ) ) $this->_metaDataValues = array();
		$this->_metaDataValues[$key] = $value;
		
	}
	
	function getLength($fieldname){
		if ( strpos($fieldname, '.') !== false ){
			list($tablename, $fieldname) = explode('.', $fieldname);
			return $this->getLength($fieldname);
		}
		$key = '__'.$fieldname.'_length';
		if ( isset( $this->_metaDataValues[$key] ) ){
			return $this->_metaDataValues[$key];
		} else {
			return strlen($this->getValueAsString($fieldname));
		}	
	}
	
	/**
	 * Sets the value for a field of this related record.
	 *
	 * @param $fieldname The name of the field to set.  This may be a relative name or an absolute column name.
	 * @param $value The value to set this field to.
	 */
	function setValue($fieldname, $value){
		$this->_initValues();
		
		
		
		if ( strpos($fieldname,'.') === false ){
			if ( strpos($fieldname, '__') === 0 ){
				return $this->setMetaDataValue($fieldname, $value);
			}
		
			//if ( !array_key_exists( $fieldname, $this->_values ) ){
			//	trigger_error("Attempt to set value for fieldname '$fieldname' that does not exist in related record of relationship '".$this->_relationshipName."'.  Acceptable values include {".implode(', ', array_keys($this->_values))."}.\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
			//}
			if ( isset( $this->_lockedFields[$fieldname]) ) return;
			$val = $this->_record->_table->parse($this->_relationshipName.".".$fieldname, $value);
			if ( $val != $this->_values[$fieldname] ){
				$this->_values[$fieldname] = $val;
				$this->clearCache();
				$this->setFlag($fieldname);
			}
		
		} else {
			
			list ( $table, $field )  = explode('.', $fieldname);
			return $this->setValue($field, $value);
		}
			
	}
	
	/**
	 * Sets multiple values at once.
	 *
	 * @param $values Associative array of values to set.
	 * @type array([Field name] -> [Field value])
	 */
	function setValues($values){
		if ( !is_array($values) ){
			trigger_error( "setValues() expects 1st parameter to be an array but received a '".get_class($values)."' ".Dataface_Error::printStackTrace(),E_USER_WARNING);
		}
		foreach ( $values as $key=>$value){
			
			$this->setValue($key, $value);
		}
	
	}
	
	/**
	 * Gets reference to the parent record (base record).
	 */
	function &getParent(){
		return $this->_record;
	}
	
		
		
	/**
	 * Gets the value for a field.
	 *
	 * @param $fieldname The name of the field whose value we are retrieving.  This may be either a relative
	 *					 fieldname or an absolute column name.
	 * @type string
	 */
	function getValue($fieldname){
		$this->_initValues();
		if ( strpos($fieldname,'.') === false ){
			
			if ( !array_key_exists( $fieldname,  $this->_values ) ){
				// The key does not exist as a normal field -- so check if it is a calculated field.
				$tables =& $this->_relationship->getDestinationTables();
				foreach ( array_keys($tables) as $tkey){
					if ( $tables[$tkey]->hasField($fieldname) ){
						$tempRecord =& new Dataface_Record($tables[$tkey]->tablename,$this->getValues());
						return $tempRecord->getValue($fieldname);
					}
				}
				trigger_error("Attempt to get value for fieldname '$fieldname' that does not exist in related record of relationship '".$this->_relationshipName."'.  Acceptable values include {".implode(', ', array_keys($this->_values))."}.\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
			}

			return $this->_values[$fieldname];
			
		} else {
			list ( $table, $field )  = explode('.', $fieldname);
			return $this->getValue($field);
		}
	}
	
	/**
	 * Gets the string value of a given field.
	 */
	function getValueAsString($fieldname){
		$value = $this->getValue($fieldname);

		$parent =& $this->getParent();
		$table =& $parent->_table->getTableTableForField($this->_relationshipName.'.'.$fieldname);
		if ( PEAR::isError($table) ){
			trigger_error($table->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$delegate =& $table->getDelegate();
		$rel_fieldname = $fieldname; //$table->relativeFieldName($fieldname);
		if ( $delegate !== null and method_exists( $delegate, $rel_fieldname.'__toString') ){
			$value = call_user_func( array(&$delegate, $rel_fieldname.'__toString'), $value);
		} else 
		
		
		if ( is_array($value) ){
			if ( method_exists( $table, $table->getType($fieldname)."_to_string") ){
				$value = call_user_func( array( &$table, $table->getType($fieldname)."_to_string"), $value );
			} else {
				$value = implode(', ', $value);
			}
		}
		
		return $value;
	
	}
	
	function htmlValue($fieldname){
		$value = $this->getValue($fieldname);
		$parent =& $this->getParent();
		$table =& $parent->_table->getTableTableForField($this->_relationshipName.'.'.$fieldname);
		if ( PEAR::isError($table) ){
			trigger_error($table->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$record =& $this->toRecord($table->tablename);
		$htmlval = $record->htmlValue($fieldname);
		return $htmlval;
	}
	
	/**
	 * <p>Gets the values stored in this table as an associative array.  The values
	 * are all returned as strings.</p>
	 * @param fields An optional array of field names to retrieve.
	 */
	function getValuesAsStrings($fields=''){
		$keys = is_array($fields) ? $fields : array_keys($this->_values);
		$values = array();
		foreach ($keys as $key){
			$values[$key] = $this->getValueAsString($key);
		}
		return $values;
	}
	
	
	
	function strvals($fields=''){
		return $this->getValuesAsStrings($fields);
	}
	
	
	/**
	 * @alias for getValueAsString()
	 */
	function strval($fieldname, $index=0){
		return $this->getValueAsString($fieldname, $index);
	}
	
	
	/**
	* @alias for getValueAsString()
	*/
	function stringValue($fieldname){
		return $this->getValueAsString($fieldname);
	}
	
	/**
	 * Gets the values of this related record.
	 * 
	 * @param $columns An optional array of columns to get.
	 * @type array(string)
	 *
	 * @param $excludeNulls If this is true, then columns with 'null' values will not be included.  Defaults to 'false'
	 * @type boolean
	 */
	function &getValues($columns=null, $excludeNulls=false){
		$this->_initValues();
		return $this->_values;
	}

	/**
	 * @alias for getValues()
	 */
	function &values($fields = null){
		return $this->getValues($fields);
	}
	
	/**
	 * @alias for getValues()
	 */
	function &vals($fields = null){
		return $this->getValues($fields);
	}
	
	
	/**
	 * Alias of getValue()
	 */
	function val($fieldname){
		return $this->getValue($fieldname);
	}
	
	
	
	/**
	 * Gets the values of this related record except that the keys of the returned associative array
	 * are absolute column names rather than relative names as are returned in getValues().
	 *
	 * @param $excludeNulls If true then 'null' values are not included in returned associative array.
	 * @type boolean
	 */
	function getAbsoluteValues($excludeNulls=false, $includeAll=false){
		$absVals = array();
		foreach ( $this->getValues() as $key=>$value){
			$absVals[ $this->_absoluteColumnNames[$key] ] = $value;
		}
		return $absVals;
		/*
		$table =& $this->_record->_table;
		$relationship =& $this->_relationship;
		$absVals = array();
		$values =& $this->getValues();
		foreach ( $values as $key=>$value){
			if ($value === null and $excludeNulls ) continue;
			$fullPath = Dataface_Table::absoluteFieldName($key, $relationship->_schema['selected_tables']);
			$absVals[$fullPath] = $value;
		}
		
		return $absVals;
		*/
	
	}
	
	/**
	 * Returns 2-Dimensional associative array of the values in this related record and in any join table.
	 * The output of this method is used to add and remove related records.
	 * @see Dataface_Relationship.getForeignKeyValues()
	 * @see Dataface_QueryBuilder.addRelatedRecord()
	 * @see Dataface_QueryBuilder.addExistingRelatedRecord()
	 */
	function getForeignKeyValues($sql = null){
		if ( !isset($sql) ) $sql_index = 0;
		else $sql_index = $sql;
		if ( isset($this->cache[__FUNCTION__][$sql_index]) ){
			return $this->cache[__FUNCTION__][$sql_index];
		}
		$fkeys = $this->_relationship->getForeignKeyValues();
		$absVals = $this->getAbsoluteValues(true);
	
		$out = $this->_relationship->getForeignKeyValues($absVals, $sql, $this->getParent());
		$this->cache[__FUNCTION__][$sql_index] = $out;
		return $out;
		
	}
	
	
	
	
	/**
	 * <p>Returns a the value of a field in a meaningful state so that it can be displayed.
	 * This method is similar to getValueAsString() except that this goes a step further and resolves
	 * references. For example, some fields may store an integer that represents the id for a related 
	 * record in another table.  If a vocabulary is assigned to that field that defines the meanings for 
	 * the integers, then this method will return the resolved vocabulary rather than the integer itself.</p>
	 * <p>Eg:</p>
	 * <code>
	 * <pre>
	 * // Column definitions:
	 * // Table Unit_plans (id INT(11), name VARCHR(255) )
	 * // Table Lessons ( unit_id INT(11) )
	 * // Lessons.unit_id.vocabulary = "select id,name from Unit_plans"
	 * $record =& new Dataface_Record('Lessons', array('unit_id'=>3));
	 * $record->getValueAsString('unit_id'); // returns 3
	 * $record->display('unit_id'); // returns "Good Unit Plan"
	 */
	 
	function display($fieldname){
		if ( isset($this->cache[__FUNCTION__][$fieldname]) ){
			return $this->cache[__FUNCTION__][$fieldname];
		}
		$parent =& $this->getParent();
		$table =& $parent->_table->getTableTableForField($this->_relationshipName.'.'.$fieldname);
		if (PEAR::isError($table) ){
			echo "Error loading table while displaying $fieldname";
			echo Dataface_Error::printStackTrace();
			echo $table->getMessage();
		}
		
		if ( !$table->isBlob($fieldname) ){
			$record =& $this->toRecord($table->tablename);
			$out = $record->display($fieldname);
			$this->cache[__FUNCTION__][$fieldname] = $out;
			return $out;
			
		} else {
			
			$keys = array_keys($table->keys());
			$qstr = '';
			foreach ($keys as $key){
				$qstr .= "&$key"."=".$this->strval($key);
			}
			$out = DATAFACE_SITE_HREF."?-action=getBlob&-table=".$table->tablename."&-field=$fieldname$qstr";
			$this->cache[__FUNCTION__][$fieldname] = $out;
			return $out;
		}
				
				
	}
	
	/**
	 * Shows a short preview of field contents.  Useful for text fields when we just want to 
	 * see the first bit of the field.  This will also strip all html tags out of the content.
	 */
	function preview($fieldname, $index=0, $maxlength=255){
		if ( isset($this->cache[__FUNCTION__][$fieldname][$index][$maxlength]) ){
			return $this->cache[__FUNCTION__][$fieldname][$index][$maxlength];
		}
		$strval = strip_tags($this->display($fieldname,$index));
		$out = substr($strval, 0, $maxlength);
		if ( strlen($strval)>$maxlength) {
			$out .= '...';
		}
		$this->cache[__FUNCTION__][$fieldname][$index][$maxlength] = $out;
		return $out;
	}
	
	/**
	 * Alias of display()
	 */
	function printValue($fieldname){
		return $this->display($fieldname);
	}
	
	/**
	 * Alias of display()
	 */
	function printval($fieldname){
		return $this->display($fieldname);
	}
	
	/**
	 * Alias of display()
	 */
	function q($fieldname){
		return $this->display($fieldname);
	}
	
	/**
	 * Displays field contents and converts html special characters to entities.
	 *
	 * @param $fieldname The name of the field to display.
	 */
	function qq($fieldname){
		$parent =& $this->getParent();
		$table =& $parent->_table->getTableTableForField($this->_relationshipName.'.'.$fieldname);
		if ( PEAR::isError($table) ){
			trigger_error($table->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		if ( !$table->isBlob($fieldname) ){
			return htmlspecialchars($this->q($fieldname, $index));
		} else {
			return $this->display($fieldname, $index);
		}
	}
	
	
	/**
	 * Validates a value against a field name.  Returns true if the value is a valid 
	 * value to be stored in the field.
	 *
	 * This method will always return true.  The Delegate class can be used to override
	 * this method.  Use <fieldname>__validate(&$record, $value, &$message) to override
	 * this functionality.
	 *
	 * @param fieldname The name of the field that we are validating for.
	 * @param value The value that we are checking.
	 * @param 3rd param : An out parameter to store the validation message.
	 *
	 */
	function validate( $fieldname, $value, &$params){
		if ( strpos($fieldname, '.') !== false ){
			list($relname, $fieldname) = explode('.', $fieldname);
			return $this->validate($fieldname, $value, $params);
		}
		
		if ( !is_array($params) ){
			$params = array('message'=> &$params);
		}
		$table =& $this->_relationship->getTable($fieldname);
		if (PEAR::isError($table) ){
			trigger_error($table->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
			exit;
		} else if (!$table ){
			trigger_error("Could not load table for field $fieldname .".Dataface_Error::printStackTrace(), E_USER_ERROR);
			exit;
		}
		$field =& $table->getField($fieldname);
		
		if ( $field['widget']['type'] == 'file' and @$field['validators']['required'] and is_array($value) and $this->getLength($fieldname) == 0 and !is_uploaded_file(@$value['tmp_name'])){
			// This bit of validation operates on the upload values assuming the file was just uploaded as a form.  It assumes
			// that $value is of the form
			//// eg: array('tmp_name'=>'/path/to/uploaded/file', 'name'=>'filename.txt', 'type'=>'image/gif').
			$params['message'] = "$fieldname is a required field.";
			return false;
		}
	
		$res = $table->validate($fieldname, $value, $params);
		if ( $res ){
			$delegate =& $table->getDelegate();
			if ( $delegate !== null and method_exists($delegate, $fieldname."__validate") ){
				/*
				 *
				 * The delegate defines a custom validation method for this field.  Use it.
				 *
				 */
				$methodname = $fieldname."__validate";
				$res = $delegate->$methodname($this,$value,$params);
				//$res = call_user_func(array(&$delegate, $fieldname."__validate"), $this, $value, $params);
			}
		}
		return $res;
		
	}
	
	
	/**
	 * Produces a Dataface_Record object representing the portion of this related record that is stored in a 
	 * particular table.
	 * @param string $tablename The name of the table for which we wich to have a Dataface_Record object returned.
	 * @since 0.6
	 */
	function &toRecord($tablename=null){
		if ( isset($this->cache[__FUNCTION__][$tablename]) ){
			return $this->cache[__FUNCTION__][$tablename];
		}
		if ( !isset($tablename) ){
			$tablename =  $this->_relationship->getDomainTable();
			
			
		} 
		
		$table =& Dataface_Table::loadTable($tablename);
		
		
		$values = array();
		
		
		$absVals =& $this->getAbsoluteValues();
		$fieldnames = $this->_relationship->fields(true);
		
		
		
		//foreach ( array_keys($absVals) as $key ){
		foreach ( $fieldnames as $key ){
			list($currTablename, $columnName) = explode('.', $key);
			if ( $currTablename == $tablename or $table->hasField($columnName)){
				
				$values[$columnName] = $absVals[$key];
				
			} else if ( isset($this->_relationship->_schema['aliases'][$columnName]) /*and 
				/*$table->hasField($this->_relationship->_schema['aliases'][$columnName])*/ ){
				$values[$this->_relationship->_schema['aliases'][$columnName]] = $absVals[$key];
			}
		}

		$record =& new Dataface_Record($tablename, $values);
		$record->secureDisplay = $this->secureDisplay;
		foreach (array_keys($values) as $key){

			if ( $this->isDirty($key) ) $record->setFlag($key);

		}
		$this->cache[__FUNCTION__][$tablename] =& $record;

		return $record;
	}
	
	/**
	 * Returns the Id of this related record object.  The id is a string in a 
	 * url format to uniquely identify this related record.  The format is:
	 * tablename/relationshipname?parentkey1=val1&parentkey2=val2&relationshipname::key1=val2&relationshipname::key2=val3
	 */
	function getId(){
		if ( isset($this->cache[__FUNCTION__]) ){
			return $this->cache[__FUNCTION__];
		}
		$parentid = $this->_record->getId();
		list($tablename, $querystr) = explode('?',$parentid);
		$id = $tablename.'/'.$this->_relationshipName.'?'.$querystr;
		$keys = array_keys($this->_relationship->keys());
		$params = array();
		foreach ($keys as $key){
			$params[] = urlencode($this->_relationshipName.'::'.$key).'='.urlencode($this->strval($key));
		}
		$out = $id.'&'.implode('&',$params);
		$this->cache[__FUNCTION__] = $out;
		return $out;
	}
	
	function getTitle(){
		$record =& $this->toRecord();
		return $record->getTitle();
	}
	
	/**
	 * Returns an array of Dataface_Record objects that represent collectively this
	 * related record.
	 */
	function toRecords(){
		$tables =& $this->_relationship->getDestinationTables();
		$out = array();
		foreach ( array_keys($tables) as $index ){
			$out[] =& $this->toRecord($tables[$index]->tablename);
		}
		return $out;
	}
	
	function save($lang=null, $secure=false){
		$recs =& $this->toRecords();
		foreach (array_keys($recs) as $i){

			$recs[$i]->save($lang, $secure);
		}
	}
	
	function checkPermission($perm, $params=array()){
		if ( isset($params['field']) ){
			if ( strpos($params['field'],'.') !== false ){
				list($junk,$fieldname) = explode('.', $params['field']);
			} else {
				$fieldname = $params['field'];
			}
			$t =& $this->_relationship->getTable($fieldname);
			
			$rec = $this->toRecord($t->tablename);
			return $rec->checkPermission($perm, $params);
		} else {
			foreach ( $this->toRecords() as $record){
				if ( !$record->checkPermission($perm, $params) ){
					return false;
				}
			}
			return true;
			
		}
	}
	
	
	
	/**
	 * Takes a boolean expression resembling an SQL where clause and evaluates it
	 * based on the values in this record.
	 */
	function testCondition($condition){
		extract($this->strvals());
		return eval('return ('.$condition.');');
	}
	
	
	/**
	 * Returns actions associated with this record.
	 * @param $params An associative array of parameters to filter the actions.
	 * 			Possible keys include:
	 *				category => the name of a category of actions to return.
	 */
	function getActions($params=array()){
		$params['record'] =& $this;
		return $this->_record->_table->tablename->getActions($params);
	}
	
	
	
	



}
