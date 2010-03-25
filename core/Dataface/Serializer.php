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
import( 'Dataface/Table.php');
import( 'Dataface/Error.php');
class Dataface_Serializer {


	var $_table;
	
	function Dataface_Serializer($tablename){
		$this->_table =& Dataface_Table::loadTable($tablename);
	}
	
	
	/**
	 * Serializes a value that comes from the field '$fieldname'.  The output from this is meant to be inserted 
	 * into a database.  Note that this output is not escaped.  You will still have to do that.
	 *
	 * @param $fieldname The name of the field from which this value supposedly comes.
	 * @param $value The value to be serialized.
	 * @param handleRepeat If true (default) this will recursively serialize the individual fields of a repeat field.
	 */
	function serialize($fieldname, $value, $handleRepeat=true){
		
		// check to see if the input value is a placeholder.  If it is, we should pass it 
		// through untouched.
		if ( is_string($value) and preg_match('/^__(.*)__$/', $value)){
			// This fixes an issue with addRelatedRecord();
			return $value;
		}
		
		if ( $value === null ){
			return null;
		}
		
		if ( strpos($fieldname, '.') !== false ){
			// This is a related field.
			$table =& $this->_table->getTableTableForField($fieldname);
			list( $relname, $fieldname) = explode('.', $fieldname);
			$serializer =& new Dataface_Serializer($table->tablename);
			$out = $serializer->serialize($fieldname, $value, $handleRepeat);
			
			return $out;
			
		}
		
		$table =& $this->_table;
		$field =& $table->getField($fieldname);
		
		
		$delegate =& $table->getDelegate();
		if ( $delegate !== null and method_exists($delegate, $fieldname."__serialize") ){
			$val = call_user_func(array(&$delegate, $fieldname."__serialize"), $value);
			
			return $val;
		}
		$widget = $field['widget'];
		$type = $widget['type'];
		
		
		if ( $handleRepeat and $field['repeat'] and is_array($value) ){
			foreach ($value as $key=>$val){
				$value[$key] = $this->serialize($fieldname, $val, false);
			}
			
			$value = implode($field['separator'], $value);
			
		}
		
		if ($table->isDate( $fieldname ) ){
			if ( !isset($value) || !$value ) return null;
			$params = $value; //$field['value'];
			if ( is_string($params)  and strtotime($params) ){
				$timestamp = strtotime($params);
				switch ($table->getType($fieldname)){
					case 'date':
						return date('Y-m-d', $timestamp);
					case 'datetime':
					case 'timestamp':
						return date('Y-m-d h:i:s', $timestamp);
					case 'time':
						return date('h:i:s', $timestamp);
					case 'year':
						return date('Y', $timestamp);
				}
					
			}
			if ( !is_array($params) ) return null;
			
			
			$datestr = str_pad($params['year'],4,"0",STR_PAD_LEFT).'-'.str_pad($params['month'],2,"0",STR_PAD_LEFT).'-'.str_pad($params['day'],2,"0",STR_PAD_LEFT);
			$timestr = str_pad($params['hours'],2,"0",STR_PAD_LEFT).':'.str_pad($params['minutes'],2,"0",STR_PAD_LEFT).':'.str_pad($params['seconds'], 2,"0",STR_PAD_LEFT);
			
			switch ( $table->getType($fieldname) ){	
				case 'date':
					return $datestr;
					//return "FROM_UNIXTIME('$datestr')";
				case 'datetime':
					return $datestr.' '.$timestr;
					//return "FROM_UNIXTIME('$datestr $timestr')";
				case 'timestamp':
					return str_pad($params['year'],4,"0",STR_PAD_LEFT).str_pad($params['month'],2,"0",STR_PAD_LEFT).str_pad($params['day'],2,"0",STR_PAD_LEFT).str_pad($params['hours'],2,"0",STR_PAD_LEFT).str_pad($params['minutes'],2,"0",STR_PAD_LEFT).str_pad($params['seconds'],2,"0",STR_PAD_LEFT);
				case 'time':
					return $timestr;
				case 'year':
					return str_pad($params['year'],4,"0",STR_PAD_LEFT);
			}

		}
		
		if ( $table->isInt( $fieldname ) ){
			return intval($value);	
		}
		
		if ( $table->isFloat( $fieldname) ){
			return doubleval($value);
		}
		
		
		
		
		if ( is_array( $value ) ){
			if ( $widget['type'] == 'table' or $widget['type'] == 'group'){
				import( 'XML/Serializer.php');
				$serializer =& new XML_Serializer(array('typeHints'=>true));
				$ser_res =& $serializer->serialize($value);
				if (!PEAR::isError($ser_res) ){
					return $serializer->getSerializedData();
				}
			}

			trigger_error("Trying to serialize value for field '$fieldname' that we don't know what to do with.  The value is an array and we don't know how to parse it.\n<br>". Dataface_Error::printStackTrace(), E_USER_ERROR);
			
		} else {
			
			
			return $value;
		}
	
	
	
	}
	

	
	/**
	 * This functions is not implemented yet, but its functionality is implicit any record's setValue() method.
	 * The delegate's *__parse() methods are supposed to handle deserialization.
	 */
	function unserialize($fieldname, $value){
		trigger_error("Not implemented yet.\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
	
	}
	
	
	/**
	 * Wraps the value inside a mysql function to encrypt the input (if the 'crypt')
	 * attribute is selected.
	 */
	function encrypt($fieldname, $value=null){
		if ( !isset($value) ) echo Dataface_Error::printStackTrace();
		if ( strpos($fieldname, '.') !== false ){
			// This is a related field.
			$table =& $this->_table->getTableTableForField($fieldname);
			list( $relname, $fieldname) = explode('.', $fieldname);
			$serializer =& new Dataface_Serializer($table->tablename);
			$out = $serializer->encrypt($fieldname, $value);
			
			return $out;
			
		}
		$field = $this->_table->getField($fieldname);
		if ( PEAR::isError($field) ){
			echo $field->getMessage();
			echo Dataface_Error::printStackTrace();
			exit;
		}
		if ( isset($field['encryption']) ){
			switch(strtolower($field['encryption'])){
				case 'md5':
					return 'MD5('.$value.')';
				case 'password':
					return 'PASSWORD('.$value.')';
				case 'sha1':
					return 'SHA1('.$value.')';
				case 'encrypt':
					return 'ENCRYPT('.$value.')';
			}
		}
		return $value;
	}
	
	
	
	



}
