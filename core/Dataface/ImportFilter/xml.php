<?php

/**
 * Import filter that converts xml to Record objects.  The XML
 * must be of the form:
 *
 * <dataface>
 *	<Profile>
 *		<FirstName>John</FirstName>
 *		<LastName>Smith</LastName>
 *		<Phone>01234-567890</Phone>
 *	</Profile>
 *	<Profile>
 *		<FirstName>Jane</FirstName>
 *		<LastName>Doe</LastName>
 *		<Phone>01234-567891</Phone>
 *	</Profile>
 * </dataface>
 *
 * The above example xml would be converted into an array of 2 Dataface_Record objects
 * for the "Profile" table.
 */

require_once 'Dataface/ImportFilter.php';
require_once 'xml2array.php';

class Dataface_ImportFilter_xml extends Dataface_ImportFilter {

	function Dataface_ImportFilter_xml(){
		$this->name = 'xml';
		$this->label = 'XML';
	}
	
	
	function import(&$data){
		$arr = GetXMLTree ($data);
		$arr = $arr['dataface'];
		
		$records = array();
		foreach (array_keys($arr) as $tablename){
			foreach ( array_keys($arr[$tablename]) as $index){
				$records[] =& new Dataface_Record($tablename, $arr[$tablename][$index]);
			}
		}
		
		return $records;
	}

}
