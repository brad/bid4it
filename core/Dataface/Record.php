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
import( 'Dataface/RelatedRecord.php');
import('Dataface/LinkTool.php');


/**
 * Set the number of related records that are to be loaded in each block.
 * Related Records are loaded in blocks so that records that have large amounts
 * of related records don't clog the system.
 */
define('DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE', 30);

/**
 * Represents a single record from a table.
 */
class Dataface_Record {

	/**
	 * @var boolean Whether calls to display and htmlValue should be subject
	 *				to permissions.  By default this is true.
	 */
	var $secureDisplay = true;


	// A unique ID for this record (for making object comparisons in PHP 4)
	// sinc the comparison operators don't work properly until PHP 5.
	var $_id;
	
	function _nextId(){
		static $id = 0;
		return $id++;
	}

	/**
	 * This will hold a reference to the parent record if this table is 
	 * an extension of another table.  A table can be "extended" or "inherit"
	 * from another table by defining the __isa__ parameter to the fields.ini
	 * file. 
	 * @see getParentRecord()
	 */
	var $_parentRecord;
	
	var $propertyChangeListeners=array();
	
	/**
	 * Associative array of values of this record.  [Column Names] -> [Column Values]
	 */
	var $_values;
	
	
	/**
	 * Reference to the Dataface_Table object that owns this Record.
	 */
	var $_table;
	
	
	/**
	 * The name of the table that owns this record.
	 */
	var $_tablename;
	
	
	/**
	 * Associative array of values of related records to this record.
	 *			[Relationship name] ->[
	 *				[0 .. num records] -> [
	 *					[Column Names] -> [Column Values]
	 *				]
	 *			]
	 */
	var $_relatedValues = array();
	
	/**
	 * A boolean array indicating whether or not a block of related records is loaded.
	 * The block size is defined in the DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE constant.
	 */
	var $_relatedValuesLoaded = array();
	
	var $_numRelatedRecords = array();
	var $_lastRelatedRecordStart = 0;
	var $_lastRelatedRecordLimit = 30;
	
	var $_relationshipRanges;
	
	
	// The title of the record
	var $_title;
	
	
	/**
	 * Associative array of snapshot values.  It is possible to take a snapshot of a record
	 * so that it can be compared when updating (to only update those fields that have been
	 * changed.
	 */
	var $_oldValues;
	
	var $_transientValues=array();
	
	
	/**
	 * Flag indicating if we are using meta data fields.  Meta data fields (if this flag is set)
	 * are signalled by '__' preceeding the field name.
	 */
	var $useMetaData = true;
	
	
	/**
	 * Flags to indicate if a field has been changed.
	 * Associative array. [Field name] -> [boolean]
	 */
	var $_dirtyFlags = array();
	
	/**
	 * Flags to indicate if values of a related field have been changed.
	 * Associative array. [Relationship name] -> [  [Field name] -> [Field value]  ]
	 */
	var $_relatedDirtyFlags = array();
	
	
	var $_isLoaded = array();
	
	var $_metaDataValues=array();
	
	
	/**
	 * Indicator to say whether blob columns should be loaded.  This is useful for the blob
	 * columns of related records.
	 */
	var $loadBlobs = false;
	
	/**
	 * Stores metadata for related records. Index keys are identical to relatedValues array.
	 */
	var $_relatedMetaValues=array();
	
	var $_delegate = null;
	
	var $cache=array();
	
	/**
	 * This flag is used to veto security settings of changes.
	 * If this flag is set when setValue() is called, then it records this 
	 * as a veto change, which means that the normal security checks won't
	 * be in effect.  All changes made to a record inside the beforeSave()
	 * beforeInsert() and beforeUpdate() triggers are performed in 
	 * veto mode so that the changes will be exempt from security checks.
	 */
	var $vetoSecurity = false;
	
	/**
	 * This array tracks any fields that should be exempt from security
	 * checks.
	 */
	var $vetoFields = array();
	
	
	/**
	 * This is a multi-purpose pouch that allows triggers to attach data to a record
	 * and retrieve it later.
	 */
	var $pouch = array();
	
	
	/**
	 * @param $tablename The name of the table that owns this record.
	 * @param $values An associative array of values to populate the record.
	 */
	function Dataface_Record($tablename, $values=null){
		$this->_id = Dataface_Record::_nextId();
		$this->_tablename = $tablename;
		$this->_table =& Dataface_Table::loadTable($tablename);
		$this->_delegate =& $this->_table->getDelegate();
		
		if ( $values !== null ){
			$this->setValues($values);
			$this->setSnapshot();
		}
	}
	
	/**
	 * This was necessary to fix a memory leak with records that have a parent record.
	 *  Thanks to http://bugs.php.net/bug.php?id=33595 for the details of this
	 * workaround.
	 *
	 * When looping through and discarding records, it is a good idea to 
	 * explicitly call __destruct.
	 *
	 */
	function __destruct(){
		unset($this->propertyChangeListeners);
		unset($this->cache);
		unset($this->pouch);
		unset($this->vetoFields);
		unset($this->_delegate);
		unset($this->_metaDataValues);
		unset($this->_transientValues);
		unset($this->_oldValues);
	
		if ( isset($this->_parentRecord) ){
			$this->_parentRecord->__destruct();
			unset($this->_parentRecord);
		}
		
		
	}
	
	function &table(){
		return $this->_table;
	}
	
	function clearCache(){
		/*if ( defined('ABOUT_TO_CRASH') ){
			//foreach (array_keys($this->cache) as $key ){
			//	unset($this->cache[$key]);
			//}
			unset($this->cache);
			sleep(2);
			var_export($this->cache);
			//unset($this->cache['getValue']);
			print_r($this->cache);
			echo "Here";exit;
		}*/
		unset($this->cache);
		
		$this->cache=array();
		
		$this->_relatedValuesLoaded = array();
		
		$this->_relatedValues = array();
		$this->_relatedMetaValues = array();
		
		
	}
	
	/**
	 * Can't remember exactly what this does.  I think it's used to set the default 
	 * range of records that should be returned by a call to getRelatedRecords().
	 */
	function getRelationshipRange($relationshipName){
		if ( isset( $this->_relationshipRanges[$relationshipName] ) ){
			return $this->_relationshipRanges[$relationshipName];
		} else {
			return $this->_table->getRelationshipRange($relationshipName);
		}
	}
	
	function setRelationshipRange($relationshipName, $lower, $upper){
		if ( !isset( $this->_relationshipRanges ) ) $this->_relationshipRanges = array();
		$this->_relationshipRanges[$relationshipName] = array($lower, $upper);
		
	}
	
	/**
	 * <p>Parses a string, resolving any variables to the values in this record.  A variable is denoted by
	 * a dollar sign preceeding the name of a field in the table.  This method replaces the variable
	 * with its corresponding value from this record.</p>
	 *
	 * <p>Examples of variables include:
	 *		<ul>
	 *			<li>$id : This would be replaced by the value in the 'id' field of the record.</li>
	 *			<li>$address.city : This would be replaced by the value in the 'city' field in the 'address' relationship.</li>
	 *		</ul>
	 *	</p>
	 * <p>Related records can be parsed, but currently indexes are not supported.  For example, if there are records in a relationship
	 *	there is no way to specify the third record in a variable.  Variables refering to related fields are automatically replaced
	 *	with the value found in the first related record.</p>
	 *
	 * @param $str The string to be parsed.
	 */
	function parseString( $str){
		if ( !is_string($str) ) return $str;
		$matches = array();
		$blackString = $str;
		while ( preg_match( '/(?<!\\\)\$([0-9a-zA-Z\._\-]+)/', $blackString, $matches ) ){
			if ( $this->_table->hasField($matches[1]) ){
				$replacement = $this->strval($matches[1]);
				
				
			} else {
				$replacement = $matches[1];
			}
			$str = preg_replace( '/(?<!\\\)\$'.$matches[1].'/', $replacement, $str);
			$blackString = preg_replace( '/(?<!\\\)\$'.$matches[1].'/', "", $blackString);
			
		}
		return $str;
	}
	
	
	/**
	 * Indicates whether a paricular related record has been loaded yet.
	 * @param $relname The relationship name.
	 * @param $index The integer index of the record that we are checking to see if it is loaded.
	 */
	function _relatedRecordLoaded($relname, $index, $where=0, $sort=0){
	
		$blockNumber = floor($index / DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE);
		return ( isset( $this->_relatedValuesLoaded[$relname][$where][$sort][$blockNumber] ) and $this->_relatedValuesLoaded[$relname][$where][$sort][$blockNumber] );
	}
	
	
	/**
	 * Converts an index range to a block range.  Related records are loaded in blocks where 
	 * a single block contains a number of records (as defined in the DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE
	 * constant.
	 *
	 * @param $lower The lower index range.
	 * @type integer
	 *
	 * @param $upper The upper index range
	 * @type integer
	 *
	 * @returns array(lower,upper)
	 */
	function _translateRangeToBlocks($lower, $upper){
	
		$lowerBlock = floor($lower / DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE);
		$upperBlock = floor($upper / DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE);
		return array(intval($lowerBlock), intval($upperBlock));
	}
	
	/**
	 * Boolean indicator to see if a block has already been loaded.
	 *
	 * @param $relname The name of the relationship whose record blocks we are inquiring about.
	 * @type string
	 *
	 * @param $block The block number to check.
	 * @type integer
	 *
	 * @returns boolean
	 */
	function _relatedRecordBlockLoaded($relname, $block, $where=0, $sort=0){
		return ( isset( $this->_relatedValuesLoaded[$relname][$where][$sort][$block] ) and $this->_relatedValuesLoaded[$relname][$where][$sort][$block] );
	}
	
	/**
	 * Loads a block of related records into memory.  Records are loaded in as blocks
	 * so that we don't load too much more than neccessary (imaging a relationship with
	 * a million related records.  We couldn't possibly want to load more than a few 
	 * hundred at a time.
	 *
	 * @param $relname The name of the relationship from which to return records.
	 * @type string
	 *
	 * @param $block The block number to load.  (From 0 to ??)
	 * @type integer
	 *
	 * @returns boolean value indicating whether the loading worked.
	 */
	function _loadRelatedRecordBlock($relname, $block, $where=0, $sort=0){
		if ( $this->_relatedRecordBlockLoaded($relname, $block, $where, $sort) ) return true;

		$relationship =& $this->_table->getRelationship($relname);
		if ( !is_object($relationship) ){
			trigger_error(
				df_translate(
					'scripts.Dataface.Record._loadRelatedRecordBlock.ERROR_GETTING_RELATIONSHIP',
					"Error getting relationship '$relname'.  The value returned by getRelationship() was '$relationship'.",
					array('relationship'=>$relname, 'retval'=>$relationship)
					)
				.Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		$start = $block * DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE;
		$limit = DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE;
		
		if ( $start >= $this->numRelatedRecords($relname, $where) ){


			return false;
		}
		
		//$sql = $this->parseString($relationship->_schema['sql']);
		$sql = $this->parseString($relationship->getSQL($this->loadBlobs, $where, $sort));

		// TODO We need to change this so that it is compatible with relationships that already specify a limit.
		$sql .= " LIMIT ".addslashes($start).",".addslashes($limit);
		

		//$res = mysql_query($sql, $this->_table->db);
		$db =& Dataface_DB::getInstance();
		$res = $db->query($sql, $this->_table->db);
		if ( !$res ){
			
		
			trigger_error( mysql_error($this->_table->db).
				df_translate(
					'scripts.Dataface.Record._loadRelatedRecordBlock.ERROR_LOADING_RELATED_RECORDS',
					"Error loading related records for relationship '$relname' in table '".$this->_table->tablename."'.  There was a problem performing the sql query '$sql'. The Mysql error returned was '".mysql_error($this->_table->db)."'\n<br>".Dataface_Error::printStackTrace(),
					array('relationship'=>$relname,'table'=>$this->_table->tablename, 'mysql_error'=>mysql_error($this->_table->db), 'sql'=>$sql)
					)
				,E_USER_ERROR);
		}
		$index = $start;
		while ( $row = mysql_fetch_assoc($res) ){
			$record_row = array();
			$meta_row = array();
			foreach ($row as $key=>$value){
				
				if (  strpos($key, '__') === 0  ){
					$meta_row[$key] = $value;
				} else {
					$record_row[$key] = $this->_table->parse($relname.'.'.$key, $value);
				}
				unset($value);
			}
			$this->_relatedValues[$relname][$where][$sort][$index++] =& $record_row;
			$this->_relatedMetaValues[$relname][$where][$sort][$index-1] =& $meta_row;
			unset($record_row);
			unset($meta_row);
			
		}

		$this->_relatedValuesLoaded[$relname][$where][$sort][$block] = true;
		
		return true;
	}
	
	
	/**
	 * Returns the total number of related records for a given relationship.
	 * @param $relname The relationship name.
	 * @return Integer number of records in this relationship.
	 */
	function numRelatedRecords($relname, $where=0){
	
		if ( !isset( $this->_numRelatedRecords[$relname][$where]) ){
			$relationship =& $this->_table->getRelationship($relname);
			
			//if ( $where !== 0 ){
				$sql = $this->parseString($relationship->getSQL($this->loadBlobs, $where));
			//} else {
			//	$sql = $this->parseString($relationship->_schema['sql']);
			//}
			$sql = stristr($sql, ' FROM ');
			$sql = "SELECT COUNT(*)".$sql;
			
			$res = mysql_query($sql, $this->_table->db);
			if ( !$res ){
				trigger_error(
					df_translate(
						'scripts.Dataface.Record.numRelatedRecords.ERROR_CALCULATING_NUM_RELATED_RECORDS',
						"Error calculating the number of related records there are for the relationship '$relname' in the table '".$this->_table->tablename."'.  There was a problem performing the sql query '$sql'.  The MYSQL error returned was '".mysql_error($this->_table->db)."'.\n<br>",
						array('relationship'=>$relname,'table'=>$this->_table->tablename,'mysql_error'=>mysql_error($this->_table->db),'sql'=>$sql)
						)
					.Dataface_Error::printStackTrace(), E_USER_ERROR);
			}
			list( $this->_numRelatedRecords[$relname][$where]) = mysql_fetch_row($res);
		}
		return $this->_numRelatedRecords[$relname][$where];
		
	}
	
	/**
	 * Returns an array of all of the records returned by a specified relation.
	 * Each record is an associative array where the values are in raw format as returned by the database.
	 *
	 * @param string $relname The name of the relationship whose records we are retrieveing.
	 * @param boolean $multipleRows Defaults to true.  If true, this will return an array of records.  If it is false it only returns the first record.
	 * @param integer $start The start position from this relationshi to return records from.
	 * @param integer $limit The number of records to return
	 * @param string $where A short where clause to filter the results.
	 * @param string $sort A comma-delimited list of fields to sort on. e.g. 'Name asc, Weight desc'.
	 * @return array A 2-dimensional array of records.  Each record is represented by an associative array.
	 *
	 * @example ../docs/examples/getRelatedRecords.example.php Getting a list of courses that a student record is enrolled in.
	 * @example ../docs/examples/getRelatedRecords.example2.php A more complex example using sorting and filtering of results.
	 *
	 * @see Dataface_Record::getRelatedRecordObjects()
	 * @see Dataface_Record::getRelationshipIterator()
	 * @see Dataface_Record::numRelatedRecords()
	 * @see Dataface_Record::sortRelationship()
	 * @see Dataface_Record::moveUp()
	 * @see Dataface_Record::moveDown()
	 */
	function &getRelatedRecords( $relname, $multipleRows=true , $start = null, $limit=null, $where=0, $sort=0){
		if ( !is_bool($multipleRows) and intval($multipleRows) > 0  ){
			/*
			 * Give caller the option of omitting the "MultipleRows" option.
			 */
			$sort = $where;
			$where = $limit;
			$limit = $start;
			$start = $multipleRows;
			$multipleRows = true;
		} else if ( $multipleRows === 'all'){
			// the second argument is the 'all' specifier - meaning that all records should be returned.
			$where = ($start === null ? 0:$start);
			$sort = $limit;
			$start = 0;
			$limit = $this->numRelatedRecords($relname, $where) + 1;
			$multipleRows = true;

		
		
		} else if ( is_string($multipleRows) and intval($multipleRows) === 0 and $multipleRows !== "0"){

			if ( is_string($start) and intval($start) === 0 and $start !== "0" ){
				// $start actually contains the sort parameter
				$sort = $start;
				$start = $limit;
				$limit = $where;
			} else {
				$sort = $where;
			
			}
			$where = $multipleRows;
			$multipleRows = 'all';
			return $this->getRelatedRecords($relname, $multipleRows, $where, $sort);
		}
			
		
		if ( $where === null ) $where = 0;
		if ( $sort === null ) $sort = 0;
		list($defaultStart, $defaultEnd) = $this->getRelationshipRange($relname);
		if ( $start === null){
			//$start = $this->_lastRelatedRecordStart;
			$start = $defaultStart;
		} else {
			$this->_lastRelatedRecordStart = $start;
		}
		
		if ( $limit === null ){
			//$limit = $this->_lastRelatedRecordLimit;
			$limit = $defaultEnd-$defaultStart;
		} else {
			$this->_lastRelatedRecordLimit = $limit;
		}
		
		
		$range = $this->_translateRangeToBlocks($start,$start+$limit-1);
		if ( $where === null ) $where = 0;
		if ( $sort === null ) $sort = 0;
		if ( !$sort ){
			$relationship =& $this->_table->getRelationship($relname);
			$order_column = $relationship->getOrderColumn();
			if ( !PEAR::isError($order_column) and $order_column){
				$sort = $order_column;
			}
		}
		// [0]->startblock as int , [1]->endblock as int
		for ( $i=$range[0]; $i<=$range[1]; $i++){
			$res = $this->_loadRelatedRecordBlock($relname, $i, $where, $sort);
			
			// If the above returned false, that means that we have reached the end of the result set.
			if (!$res ) break;
		}
		
		
		if ( $multipleRows === true ){
		
		
			$out = array();
			for ( $i=$start; $i<$start+$limit; $i++){
				if ( !isset( $this->_relatedValues[$relname][$where][$sort][$i] ) ) continue;
				$out[$i] =& $this->_relatedValues[$relname][$where][$sort][$i];
			}
			//return $this->_relatedValues[$relname][$where][$sort];
			return $out;
		} else if (is_array($multipleRows) ){
			trigger_error("Unsupported feature: using array query for multiple rows in getRelatedRecords".Dataface_Error::printStackTrace(), E_USER_ERROR);
			// we are searching using a query
			foreach ( array_keys($this->_relatedValues[$relname][$where][$sort]) as $rowIndex ){
				$row =& $this->_relatedValues[$relname][$where][$sort][$rowIndex];
				$match = true;
				foreach ( $multipleRows as $key=>$value ){
					if ( strpos($key,'.')!== false ){
						// if the query specifies an absolute path, just parse it
						list($dummy, $key) = explode('.', $key);
						if ( trim($dummy) != trim($relname) ){
							// make sure that this query is for this relationship
							continue;
						}
					}
					$fullpath = $relname.'.'.$key;
					$nvalue = $this->_table->normalize($fullpath, $value);
					if ( $nvalue != $this->_table->normalize($fullpath, $rowIndex) ){
						// see if this column matches
						$match = false;
					}
				}
				if ( $match ) return $row;
				unset($row);
			}
			
		
		} else {
			if (count($this->_relatedValues[$relname][$where][$sort])>0){
				if ( is_int( $start ) ){
					return $this->_relatedValues[$relname][$where][$sort][$start];
				} else {
					return reset($this->_relatedValues[$relname][$where][$sort]);
				}
				//$first =& array_shift($this->_relatedValues[$relname]);
				//array_unshift($this->_relatedValues[$relname], $first);
				//return $first;
			} else {
				return null;
			}
		}
				
	}
	
	/**
	 * Returns the "children" of this record.  
	 * <p>A record's children can be defined by two means:
	 * <ol><li>Adding &quot;meta:class = children&quot; to a relationship
	 * in the <em>relationships.ini</em> file to indicate the the records in 
	 * that relationship are considered &quot;child&quot; records of the parent
	 * record.</li>
	 * <li>Defining a method named <em>getChildren()</em> in the delegate class
	 * that returns an array of Dataface_Record objects (not Dataface_RelatedRecord
	 * objects) which are deemed to be the children of a particular record.</li>
	 * </ol></p>
	 *
	 * @return array Array of Dataface_Record objects that are the children of
	 * 		this record.
	 */
	function getChildren($start=null, $limit=null){
		$delegate =& $this->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getChildren')){
			$children =& $delegate->getChildren($this);
			return $children;
		} else if ( ( $rel =& $this->_table->getChildrenRelationship() ) !== null ){
			$it =& $this->getRelationshipIterator($rel->getName(), $start, $limit);
			$out = array();
			while ( $it->hasNext() ){
				$child =& $it->next();
				$out[] = $child->toRecord();
			}
			return $out;
		} else {
			
			return null;
		}
	}
	
	
	/**
	 * Gets a particular child at the specified index.  
	 * <p>If only one child is needed, then this method is preferred to 
	 * getChildren() because it avoids loading the unneeded records from the 
	 * database.</p>
	 *
	 * @param integer $index The zero-based index of the child to retrieve.
	 * @return Dataface_Record The child record at that index (or null if none exists).
	 *
	 * @see Dataface_Record::getChildren()
	 */
	function getChild($index){
		$children =& $this->getChildren($index,1);
		if ( !isset($children) || count($children) == 0 ) return null;
		return $children[0];
	}
	
	/**
	 * Returns the "parent" record of this record.
	 *
	 * !!!!IMPORTANT!!!! 
	 *		DO NOT CONFUSE THIS WITH getParentRecord().
	 *		getParentRecord() returns this record's parent in terms of the 
	 *		table heirarchy.
	 *
	 *		This method obtains the parent record in terms of the content 
	 *		heirarchy.
	 *
	 * <p>A record's parent can be defined in two ways:
	 * <ol>
	 * <li>Adding &quot;meta:class = parent&quot; to a relationship in the 
	 *     <em>relationships.ini</em> file to indicate that the first record
	 *     in the relationship is the &quot;parent&quot; record of the source record.</li>
	 * <li>Defining a method named <em>getParent()</em> in the delegate class that
	 *     returns a Dataface_Record object (not a Dataface_RelatedRecord object)
	 *     that is deemed to be the record's parent.</li>
	 * </ol>
	 * </p>
	 *
	 * @param Dataface_Record The parent record of this record.
	 */
	function &getParent(){
		$delegate =& $this->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getParent')){
			$parent =&  $delegate->getParent($this);
			return $parent;
		} else if ( ( $rel =& $this->_table->getParentRelationship() ) !== null ){
			$it =& $this->getRelationshipIterator($rel->getName());
			
			if ( $it->hasNext() ){
				$parent =& $it->next();
				$out =& $parent->toRecord();
				return $out;
			}
			return null;
		} else {
			return null;
		}
	}
	
	/**
	 * Obtains a reference to the Dataface_Record object that holds the parent 
	 * of this record (in terms of table heirarchy).  
	 *
	 * Tables can extend other tables using the __isa__ property of the fields.ini
	 * file.
	 *
	 * @see Dataface_Table::getParent();
	 * @returns Dataface_Record
	 */
	function &getParentRecord(){
		if ( !isset($this->_parentRecord) ){
			$parent =& $this->_table->getParent();
			if ( isset($parent) ){
				$this->_parentRecord =& new Dataface_Record($parent->tablename, array());
				foreach ( array_keys($parent->keys()) as $key ){
					$this->_parentRecord->addPropertyChangeListener( $key, $this);
				}
			}	
		}
		return $this->_parentRecord;
	
	}
	
	
	
	
	/**
	 * This returns a unique id to this record.  It is in a format similar to a url:
	 * table?key1=value1&key2=value2
	 * @return string
	 */
	function getId(){
		$keys = array_keys($this->_table->keys());
		$params=array();
		foreach ($keys as $key){
			$params[] = urlencode($key).'='.urlencode($this->strval($key));
		}
		return $this->_table->tablename.'?'.implode('&',$params);
	}
	
	
	/**
	 * Obtains an iterator to iterate through the related records for a specified
	 * relationship.
	 *
	 * @param string $relationshipName The name of the relationship.
	 * @param integer $start The start index (zero based). Use null for default.
	 * @param integer $limit The number of records to return. Use null for default.
	 * @param string $where A string where clause to limit the results.  e.g. 'Name="Fred" and Size="large"'.
	 *				Use 0 for default.
	 * @param string $sort A comma-delimited list of columns to sort on with optional 'asc' or 'desc'
	 *			indicators.  e.g. 'FirstName, SortID desc, LastName asc'.
	 * @return Dataface_RelationshipIterator
	 */
	function getRelationshipIterator($relationshipName, $start=null, $limit=null,$where=0, $sort=0){
		if ( !$sort ){
			$relationship =& $this->_table->getRelationship($relationshipName);
			$order_column = $relationship->getOrderColumn();
			if ( !PEAR::isError($order_column) and $order_column){
				$sort = $order_column;
			}
		}
		return new Dataface_RelationshipIterator($this, $relationshipName, $start, $limit, $where, $sort);
	}
	
	/**
	 * Gets an array of Dataface_RelatedRecords 
	 * @param string $relationshipName The name of the relationship.
	 * @param integer $start The start index (zero-based).  Use null for default.
	 * @param integer $end The limit parameter.  Use null for default.
	 * @param string $where A where clause to limit the results. e.g. 'Name="Fred" and Size='large'".
	 *			Use 0 for default.
	 * @param string $sort A comma delimited list of columns to sort on.  e.g. 'OrderField, LastName desc, FirstName asc'
	 * @return array Array of Dataface_RelatedRecord objects.
	 * 
	 * @see Dataface_Record::getRelatedRecords()
	 * @see Dataface_Record::getRelationshipIterator()
	 * @see Dataface_RelationshipIterator()
	 * @see Dataface_Record::getRelatedRecord()
	 */
	function &getRelatedRecordObjects($relationshipName, $start=null, $end=null,$where=0, $sort=0){
		$out = array();
			
		$it =& $this->getRelationshipIterator($relationshipName, $start, $end,$where,$sort);
		while ($it->hasNext() ){
			$out[] =& $it->next();
		}
		return $out;
	}
	
	
	/**
	 * Returns a single Dataface_RelatedRecord object from the relationship 
	 * specified by $relationshipName .
	 *
	 * @param string $relationshipName The name of the relationship.
	 * @param integer $index The position of the record in the relationship.
	 * @param string $where A where clause to filter the base result set.
	 *				Use 0 for default.
	 * @param string $sort A comma-delimited list of columns to sort on.
	 * @return Dataface_RelatedRecord
	 */
	function &getRelatedRecord($relationshipName, $index=0, $where=0, $sort=0){
		if ( isset($this->cache[__FUNCTION__][$relationshipName][$index][$where][$sort]) ){
			return $this->cache[__FUNCTION__][$relationshipName][$index][$where][$sort];
		}
		$it =& $this->getRelationshipIterator($relationshipName, $index, 1, $where, $sort);
		if ( $it->hasNext() ){
			$rec =& $it->next();
			$this->cache[__FUNCTION__][$relationshipName][$index][$where][$sort] =& $rec;
			return $rec;
		} else {
			$null = null;	// stupid hack because literal 'null' can't be returned by ref.
			return $null;
		}
	}
	
	
	
	/**
	 * <p>Sets the value of the field '$fieldname' to '$value'</p>.
	 * @param string $key The name of the field to set.  This can be a simple name (eg: 'id') or a related name (eg: 'addresses.city').
	 * @param string $value The value to set the field to.
	 * @param integer $index The index of the record to change (if this is a related record).
	 */
	function setValue($key, $value, $index=0){
		
		$oldValue = $this->getValue($key, $index);
		
		
		if ( strpos($key, '.')!== false ){
			trigger_error("Unsupported operation: setting value on related record.".Dataface_Error::printStackTrace(), E_USER_ERROR);
		
		}
			
		// This is a local field
		else {
			if ( strpos($key, "__") === 0 && $this->useMetaData ){
				/*
				 * This is a meta value..
				 */
				return $this->setMetaDataValue($key, $value);
			}
			
			$add=true;
			
			 
			if ( !array_key_exists($key, $this->_table->fields() ) ){
				
				if ( array_key_exists($key, $this->_table->transientFields()) ){
					
					$this->_transientValues[$key] = $value;
					$add=false;
					//return;
				}
			
				else if ( !array_key_exists($key, $this->_table->graftedFields(false)) ){
					$parent =& $this->getParentRecord();
					
					if ( isset($parent) and $parent->_table->hasField($key) ){
						
						$parent->setValue($key, $value, $index);
				
					}
					$add=false;
				} else {
					
					
					
					$add=true;
				}
				
			} 
			if ( $add ){
				$this->_values[$key] = $this->_table->parse($key, $value);
				
				$this->_isLoaded[$key] = true;
			}
		}
		
		
		// now set the flag to indicate that the value has been changed.
		
		$this->clearCache();
		
		if ($oldValue != $this->getValue($key, $index) ){
			
			$this->setFlag($key, $index);
			if ( $this->vetoSecurity ){
				$this->vetoFields[$key] = true;
			}
			$this->clearCache();
			
			$this->firePropertyChangeEvent($key, $oldValue, $this->getValue($key,$index));
			
			// Now we should notify the parent record if this was a key field
			if ( array_key_exists($key, $this->_table->keys() ) ){
				if ( !isset($parent) ) $parent =& $this->getParentRecord();
				if ( isset($parent) ){
					$keys = array_keys($this->_table->keys());
					$pkeys = array_keys($parent->_table->keys());
					$key_index = array_search($key, $keys);
					
					$parent->setValue($pkeys[$key_index], $this->getValue($key, $index));
				}
			}
		}
		
		
	}
	
	function addPropertyChangeListener($key, &$listener){
		$this->propertyChangeListeners[$key][] = &$listener;
	}
	
	function removePropertyChangeListener($key, &$listener){
		if ( !isset($key) ) $key = '*';
		if ( !isset($callback) ) unset($this->propertyChangeListeners[$key]);
		else if ( isset($this->propertyChangeListeners[$key]) ){
			if ( ($index = array_search($listener,$this->propertyChangeListeners[$key])) !== false){
				unset($this->propertyChangeListeners[$key][$index]);
			}
		}
	}
	
	function firePropertyChangeEvent($key, $oldValue, $newValue){
		$origKey = $key;
		$keys = array('*');
		if ( isset($key) ) $keys[] = $key;
		foreach ($keys as $key){
			if ( !isset($this->propertyChangeListeners[$key] ) ) continue;
			foreach ( array_keys($this->propertyChangeListeners[$key]) as $lkey){
				$this->propertyChangeListeners[$key][$lkey]->propertyChanged($this,$origKey, $oldValue, $newValue);
				
			}
		}
		
		
	}
	
	/**
	 * This method is to implement the PropertyChangeListener interface.  This method
	 * will be called whenever a change is made to the parent record's primary
	 * key, so that we can keep our keys in sync.
	 */
	function propertyChanged(&$source, $key, $oldValue, $newValue){
		$parentRecord =& $this->getParentRecord();
		if ( is_a($source, 'Dataface_Record') and is_a($parentRecord, 'Dataface_Record') and $source->_id === $parentRecord->_id ){
			$pkeys = $source->_table->keys();
			$pkey_names = array_keys($pkeys);
			$okeys = $this->_table->keys();
			$okey_names = array_keys($okeys);
			
			if ( !array_key_exists($key, $pkeys) ) return false;
				// The field that was changed was not a key so we don't care
				
			$key_index = array_search($key, $pkey_names);
			if ( $key_index === false ) trigger_error("An error occurred trying to find the index of the parent's key.  This is a code error that should be fixded by the developer.", E_USER_ERROR);
			
			
			if ( !isset($okey_names[$key_index]) )
				trigger_error("Attempt to keep the current record in sync with its parent but they seem to have a different number of primary keys.  To use Dataface inheritance, tables must have a corresponding primary key.", E_USER_ERROR);
			
			
			$this->setValue( $okey_names[$key_index], $newValue);
		}
	}
	
	/**
	 * Returns the full path to the  file contained in a container field.
	 */
	function getContainerSource($fieldname){
		$filename =& $this->strval($fieldname);
		if ( strlen($filename)===0 ){
			return null;
		}
		$field =& $this->_table->getField($fieldname);
		return $field['savepath'].'/'.$filename;
	
	}

	
	function setMetaDataValue($key, $value){
		if ( !isset( $this->_metaDataValues ) ) $this->_metaDataValues = array();
		$this->_metaDataValues[$key] = $value;
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->setMetaDataValue($key, $value);
		}
	}
	
	
	/**
	 * Returns a join record for the give table.  A join record is one that contains
	 * auxiliary data for the current record.  It is specified by the [__join__]
	 * section of the fields.ini file or the __join__() method of the delegate
	 * class.  It is much like a one-to-one relationship.  The key difference
	 * between a join record and a related record is that a join record 
	 * is assumed to be one-to-one, and an extra tab is added to the edit form 
	 * to edit a join record.
	 *
	 * @param string $tablename The name of the table from which the join record
	 * 				should be drawn.
	 *
	 * @returns Dataface_Record Join record from the specified join table or 
	 * 			a new record with the correct primary key values if none exists.
	 *
	 * @returns PEAR_Error If the specified table in incompatible.
	 *
	 */
	function getJoinRecord($tablename){
		$table =& Dataface_Table::loadTable($tablename);
		$query = $this->getJoinKeys($tablename);
		foreach ( $query as $key=>$val ){
			$query[$key] = '='.$val;
		}
		
		$record = df_get_record($tablename, $query);
		if ( !$record ){
			// No record was found, so we create a new one.
			$record = new Dataface_Record($tablename, array());
			foreach ( $query as $key=>$value){
				$record->setValue($key, substr($value,1));
			}
		}
		return $record;
		
	}
	
	
	/**
	 * Gets the keys that are necessary to exist in a join record for the given
	 * table.
	 *
	 * @param string $tablename The name of the join table.
	 *
	 * @returns array An associative array of key value pairs.  This is essentially
	 * 		the values for the primary key of the join record in question.
	 *
	 * @example
	 *<code>
	 *	// Table: Persons(PersonID, Name, SSN)  PKEY (PersonID)
	 *	// Table: Authors(AuthorID, AuthorCategory, Description) PKEY (AuthorID)
	 *  // Suppose AuthorID is a foreign key for PersonID (1-to-1).
	 *  // In the fields.ini file we have:
	 *  // [__join__]
	 *	// Authors=Author Details
	 *
	 *	$person = df_get_record('Persons', array('PersonID'=>10));
	 *  $authorKeys = $person->getJoinKeys('Authors');
	 *	print_r($authorKeys);
	 *		// array( 'AuthorID'=>10)
	 *</code>
	 */
	function getJoinKeys($tablename){
		$table =& Dataface_Table::loadTable($tablename);
		$query = array();
		
		$pkeys1 = array_keys($this->_table->keys());
		$pkeys2 = array_keys($table->keys());
		
		if ( count($pkeys1) != count($pkeys2) ){
			return PEAR::raiseError("Attempt to get join record [".$this->_table->tablename."] -> [".$table->tablename."] but they have a different number of columns as primary key.");
		}
		
		for ($i =0; $i<count($pkeys1); $i++ ){
			$query[$pkeys2[$i]] = $this->strval($pkeys1[$i]);
		}
		
		return $query;
	
	}
	
	function tabs(){
		return $this->_table->tabs($this);
	}
	
	/**
	 * Gets the length of the value in a particular field.  This can be especially
	 * useful for blob and longtext fields that aren't loaded into memory.  It allows
	 * you to see if there is indeed a value there.
	 *
	 * @param string $fieldname The name of the field to check.
	 * @param integer $index If this is a related field then this is the index offset.
	 * @param string $where If this is a related field this is a where clause to filter the results.
	 * @param string $sort If this is a related field then this is a sort clause to sort the results.
	 * @return integer The length of the specified field's value in bytes.
	 */
	function getLength($fieldname, $index=0, $where=0, $sort=0){
		if ( strpos($fieldname, '.') !== false ){
			
			list($relname, $localfieldname) = explode('.',$fieldname);
			$record =& $this->getRelatedRecords($relname, false, $index, null, $where, $sort);
			$relatedRecord =& new Dataface_RelatedRecord($this, $relname,$record);
			$relatedRecord->setValues($this->_relatedMetaValues[$relname][0][0][$index]);
			return $relatedRecord->getLength($localfieldname);
			//$key = '__'.$localfieldname.'_length';
			//if ( isset($record[$key]) ){
			//	return $record[$key];
			//} else {
			//	return null;
			//}
		} else {
			$key = '__'.$fieldname.'_length';
			if ( isset($this->_metaDataValues[$key] ) ){
				return $this->_metaDataValues[$key];
			} else {
				return strlen($this->getValueAsString($fieldname));
			}
		}
		
	}
	
	
	
	/**
	 * <p>Sets muliple values at once.</p>
	 * @param $values Associative array. [Field names] -> [Values]
	 */
	function setValues($values){
		$fields = $this->_table->fields(false, true);
		foreach ($values as $key=>$value){
			if ( isset( $fields[$key] ) ){
				$this->setValue($key, $value);
			} else if ( strpos($key,'__')===0){
				$this->setMetaDataValue($key,$value);
			}
		}
	}
	
	
	/**
	 * <p>Gets the value of a field in this record.</p>
	 * @param $fieldname The name of the field whose value we wish to obtain.   Could be simple name (eg: 'id') or related name (eg: 'addresses.city').
	 * @param $index The index of the value.  This is primarily used when retrieving the value of a related field that has more than one record.
	 */
	function &getValue($fieldname, $index=0, $where=0, $sort=0, $debug=false){
		static $callcount=0;
		$callcount++;
		if ( $debug ) echo "Num calls to getValue(): $callcount";
		if ( isset($this->cache[__FUNCTION__][$fieldname][$index][$where][$sort]) ){
			return $this->cache[__FUNCTION__][$fieldname][$index][$where][$sort];
		}
		
		
		
		if ( is_array($index) ){
			trigger_error(
				df_translate(
					'scripts.Dataface.Record.getValue.ERROR_PARAMETER_2',
					"In Dataface_Record.getValue() expected 2nd parameter to be integer but received array."
					)
				.Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		if ( is_array($where) ){
			trigger_error(
				df_translate(
					'scripts.Dataface.Record.getValue.ERROR_PARAMETER_3',
					"In Dataface_Record.getValue() expected 3rd parameter to be a string, but received array."
					)
				.Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		if ( is_array($sort) ){
			trigger_error(
				df_translate(
					'scripts.Dataface.Record.getValue.ERROR_PARAMETER_4',
					"In Dataface_Record.getValue() expected 4th parameter to be a string but received array."
					)
				.Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		$out = null;
		if ( strpos($fieldname,'.') === false ){
			$delegate =& $this->_delegate;
			
			if ( !isset( $this->_values[$fieldname] ) ) {
				// The field is not set... check if there is a calculated field we can use.
				if ( $delegate !== null and method_exists($delegate, "field__$fieldname")){
					$methodname = "field__$fieldname";
					$out =& $delegate->$methodname($this,$index);
					//$out =& call_user_func( array(&$delegate, "field__$fieldname"), $this, $index);
				//} else if ( array_key_exists($fieldname, $this->_transientValues) ){
				} else if ( array_key_exists($fieldname, $this->_table->transientFields()) ){
					$transientFields =& $this->_table->transientFields();
					if ( array_key_exists( $fieldname, $this->_transientValues) ){
						$out = $this->_transientValues[$fieldname];
					} else if ( isset($transientFields[$fieldname]['relationship']) ){
						$out= array();
						$rrecords =& $this->getRelatedRecordObjects($transientFields[$fieldname]['relationship'], 'all');
						$currRelationship =& $this->_table->getRelationship($transientFields[$fieldname]['relationship']);
						$relKeys =& $currRelationship->keys();
						//print_r(array_keys($currRelationship->keys()));
						foreach ($rrecords as $rrecord){
							$row = $rrecord->strvals();
							
							foreach ( array_keys($row) as $row_field ){
								$ptable =& $rrecord->_relationship->getTable($row_field);
								$precord =& $rrecord->toRecord($ptable->tablename);
								if ( !$precord or PEAR::isError($precord) ) continue;
								$row['__permissions__'][$row_field] = $precord->getPermissions(array('field'=>$row_field));
								if ( isset($relKeys[$row_field]) ) unset($row['__permissions__'][$row_field]['edit']);
								unset($precord);
								unset($ptable);
							}
							$row['__id__'] = $rrecord->getId();
							
							$out[] = $row;
							unset($rrecord);
							unset($row);
						}
						unset($relKeys);
						unset($currRelationship);
						unset($rrecords);
						$this->_transientValues[$fieldname] = $out;
						
					} else {
						$out = null;
					}
					
				} else if ( ( $parent =& $this->getParentRecord() ) and $parent->_table->hasField($fieldname) ){
				
					return $parent->getValue($fieldname,$index,$where,$sort,$debug);
				} else {
					$this->_values[$fieldname] = null;
					$out = null;
				}
			} else {
				$out = $this->_values[$fieldname];
			}
			if ( isset($out) ){
				// We only store non-null values in cache.  We were having problems 
				// with segfaulting in PHP5 when groups are used.
				// This seems to fix the issue, but let's revisit it later.
				$this->cache[strval(__FUNCTION__)][strval($fieldname)][$index][$where][$sort] = $out;
			}
			return $out;
		} else {
			list($relationship, $fieldname) = explode('.', $fieldname);
			
			$rec =& $this->getRelatedRecords($relationship, false, $index, 1, $where, $sort);
			$this->cache[__FUNCTION__][$relationship.'.'.$fieldname][$index][$where][$sort] =& $rec[$fieldname];
			return $rec[$fieldname];
			
			
		}
	}
	
	function _getSubfield(&$fieldval, $path){
		if ( !is_array($fieldval) ){
			return PEAR::raiseError("_getSubfield() expects its first parameter to be an array.");
		}
		$path = explode(":",$path);
		$temp1 =& $fieldval[array_shift($path)];
		$temp2 =& $temp1;
		while ( sizeof($path) > 0 ){
			unset($temp1);
			$temp1 =& $temp2[array_shift($path)];
			unset($temp2);
			$temp2 =& $temp1;
		}
		return $temp2;
	}
	function getAbsoluteValues(){
		$values = $this->getValues();
		$absValues = array();
		foreach ( $values as $key=>$value){
			$absValues[$this->_table->tablename.".".$key] = $value;
		}
		return $absValues;
	}
	
	
	/**
	 * @alias for getValue()
	 */
	function &value($fieldname, $index=0, $where=0, $sort=0){
		$val =& $this->getValue($fieldname, $index,$where, $sort);
		return $val;
	}
	
	/**
	 * @alias for getValue()
	 */
	function &val($fieldname, $index=0, $where=0, $sort=0){
		$val =& $this->getValue($fieldname, $index, $where, $sort);
		return $val;
	}
	
	/**
	 * Gets the values of this Record in an associative array. [Field names] -> [Field values].
	 * @param $fields Array of column names that we wish to retrieve.  If this parameter is omitted, all of the fields are returned.
	 */
	function &getValues($fields = null, $index=0, $where=0, $sort=0){
		if ( !isset( $this->_values ) ) $this->_values = array();
		$values = array();
		$fields = ( $fields === null ) ? array_keys($this->_table->fields(false,true)) : $fields;
		foreach ($fields as $field){
			$values[$field] =& $this->getValue($field, $index, $where, $sort);
		}
			
		return $values;
	}
	
	/**
	 * @alias for getValues()
	 */
	function &values($fields = null, $index=0, $where=0, $sort=0){
		$vals =& $this->getValues($fields, $index, $where, $sort);
		return $vals;
	}
	
	/**
	 * @alias for getValues()
	 */
	function &vals($fields = null, $index=0, $where=0, $sort=0){
		$vals =& $this->getValues($fields, $index, $where, $sort);
		return $vals;
	}
	
	/**
	 * <p>Gets the values of this record as strings.  Some records like dates and times, are stored as data structures.  getValue()
	 * returns these datastructures unchanged.  This method will perform the necessary conversions to return the values as strings.</p>
	 */
	function getValueAsString($fieldname, $index=0, $where=0, $sort=0){
		//return $this->_table->getValueAsString($fieldname, $this->getValue($fieldname), $index);
		
		$value = $this->getValue($fieldname, $index, $where, $sort);
		
		
		$table =& $this->_table->getTableTableForField($fieldname);
		$delegate =& $table->getDelegate();
		$rel_fieldname = $table->relativeFieldName($fieldname);
		if ( $delegate !== null and method_exists( $delegate, $rel_fieldname.'__toString') ){
			$methodname = $rel_fieldname.'__toString';
			$value = $delegate->$methodname($value); //call_user_func( array(&$delegate, $rel_fieldname.'__toString'), $value);
		} else 
		
		
		if ( is_array($value) ){
			$methodname = $this->_table->getType($fieldname)."_to_string";
			if ( method_exists( $this->_table, $methodname) ){
				
				$value = $this->_table->$methodname($value); //call_user_func( array( &$this->_table, $this->_table->getType($fieldname)."_to_string"), $value );
			} else {
				$value = $this->array2string($value);
				
			}
		}
		
		else if ( ( $parent =& $this->getParentRecord() ) and $parent->_table->hasField($fieldname) ){
			return $parent->getValueAsString($fieldname, $index, $where, $sort);
		}
		
		return $value;
	}
	
	function array2string($value){
		if ( is_string($value) ) return $value;
		if ( is_array($value) ){
			if ( count($value) > 0 and is_array($value[0]) ){
				$delim = "\n";
			} else {
				$delim = ', ';
			}
			return implode($delim, array_map(array(&$this,'array2string'), $value));
		}
		return '';
	}
	
	
	/**
	 * <p>Gets the values stored in this table as an associative array.  The values
	 * are all returned as strings.</p>
	 * @param fields An optional array of field names to retrieve.
	 */
	function getValuesAsStrings($fields='', $index=0, $where=0, $sort=0){
		$keys = is_array($fields) ? $fields : array_keys($this->_table->fields(false,true));
		$values = array();
		foreach ($keys as $key){
			$values[$key] = $this->getValueAsString($key, $index, $where, $sort);
		}
		return $values;
	}
	
	/**
	 * @alias getValuesAsStrings()
	 */
	function strvals($fields='', $index=0, $where=0, $sort=0){
		return $this->getValuesAsStrings($fields, $index, $where, $sort);
	}
	
	
	/**
	 * @alias for getValueAsString()
	 */
	function strval($fieldname, $index=0, $where=0, $sort=0){
		return $this->getValueAsString($fieldname, $index, $where, $sort);
	}
	
	
	/**
	* @alias for getValueAsString()
	*/
	function stringValue($fieldname, $index=0, $where=0, $sort=0){
		return $this->getValueAsString($fieldname, $index, $where, $sort);
	}
	
	
	/**
	 * Returns the value of a field except it is serialzed to be instered into a database.
	 */
	function getSerializedValue($fieldname, $index=0, $where=0, $sort=0){
		$s = $this->_table->getSerializer();
		return $s->serialize($fieldname, $this->getValue($fieldname, 0, $where, $sort));
	
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
	 
	function display($fieldname, $index=0, $where=0, $sort=0, $urlencode=false){
		if ( isset($this->cache[__FUNCTION__][$fieldname][$index][$where][$sort]) ){
			return $this->cache[__FUNCTION__][$fieldname][$index][$where][$sort];
		}
		if ( strpos($fieldname,'.') === false ){
			// this is not a related field.
			if ( $this->secureDisplay and !Dataface_PermissionsTool::view($this, array('field'=>$fieldname)) ) return 'NO ACCESS';
		} else {
			list($relationship,$fieldname) = explode('.',$fieldname);
			$rrecord =& $this->getRelatedRecord($relationship,$index,$where,$sort);
			$out = $rrecord->display($fieldname);
			$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
			return $out;
		}
		
		
	
		$table =&  $this->_table->getTableTableForField($fieldname);
		
		
		$delegate =& $this->_table->getDelegate();
		if ( $delegate !== null and method_exists( $delegate, $fieldname."__display") ){
			$methodname = $fieldname."__display";
			$out = $delegate->$methodname($this);
			//$out = call_user_func(array(&$delegate, $fieldname."__display"), $this);
			$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
			return $out;
			
		}
		
		if ( $this->_table->isBlob($fieldname) ){
			$field =& $this->_table->getField($fieldname);
			unset($table);
			$table =& Dataface_Table::loadTable($field['tablename']);
			$keys = array_keys($table->keys());
			$qstr = '';
			foreach ($keys as $key){
				$qstr .= "&$key"."=".$this->strval($key,$index,$where,$sort);
			}
			$out = DATAFACE_SITE_HREF."?-action=getBlob&-table=".$field['tablename']."&-field=$fieldname&-index=$index$qstr";
			$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
			return $out;
		}
		
		else if ( $this->_table->isContainer($fieldname) ){
			$field =& $this->_table->getField($fieldname);
			$strvl=$this->strval($fieldname,$index,$where,$sort);
			if ($urlencode)
			{
			    $strvl=rawurlencode($strvl);
			}
			$out = $field['url'].'/'.$strvl;
			if ( strlen($out) > 1 and $out{0} == '/' and $out{1} == '/' ){
				$out = substr($out,1);
			}
			$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
			return $out;
		}
		
		else { //if ( !$this->_table->isBlob($fieldname) ){
		
			$field =& $this->_table->getField($fieldname);
			
			$vocab = $field['vocabulary'];
			if ( $vocab ){
				$valuelist =& $table->getValuelist($vocab);
			}
			
			if ( PEAR::isError($field) ){
				$field->addUserInfo("Failed to get field '$fieldname' while trying to display its value in Record::display()");
				trigger_error($field->toString(), E_USER_ERROR);
				
			}
			$value = $this->getValue($fieldname, $index, $where, $sort);
			if ( PEAR::isError($value) ) return '';
			if ( isset($valuelist) && !PEAR::isError($valuelist) ){
				if ( $field['repeat'] and is_array($value) ){
					$out = "";
					foreach ($value as $value_item){
						if ( isset( $valuelist[$value_item] ) ){
							$out .= $valuelist[$value_item].', ';
						}
					}
					if ( strlen($out) > 0 ) $out = substr($out, 0, strlen($out)-2);
					$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
					return $out;
				}
				
				//else if ( isset( $valuelist[$value]) ){
				else {
					if ( is_array($value) ) $value = $this->strval($fieldname, $index, $where, $sort);
					if ( isset($valuelist[$value]) ){
						$out = $valuelist[$value];
						$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
						return $out;
					} else {
						return null;
					}
				} 
			} else {
				$parent =& $this->getParentRecord();
				
				
				
				if ( isset($parent) and $parent->_table->hasField($fieldname) ){
					
					return $parent->display($fieldname, $index, $where, $sort);
				}
				$out = $this->getValueAsString($fieldname, $index, $where, $sort);
				$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
				return $out;
			}
		
		
			//return $this->_table->display($fieldname, $this->getValue($fieldname, $index));
		} 
				
				
	}
	
	
	/**
	 * This method sits above "display" on the output stack for a field.
	 * I.e. it wraps "display()" and adds some extra filtering to make the
	 * output directly appropriate to be displayed as HTML.  In text fields
	 * this will convert newlines to breaks, and in blob fields, this will output
	 * either the full a-href tag or img tag depending on the type of content that
	 * is stored.
	 * 
	 * @param $fieldname The name of the field to output
	 * @param $params Associative array of html parameters that can optionally
	 * be supplied.
	 * Returns HTML string.
	 */
	function htmlValue($fieldname, $index=0, $where=0, $sort=0,$params=array()){
		$recid = $this->getId();
		$uri = $recid.'#'.$fieldname;
		$domid = $uri.'-'.rand();
		
		$delegate =& $this->_table->getDelegate();
		if ( isset($delegate) && method_exists($delegate, $fieldname.'__htmlValue') ){
			$methodname = $fieldname.'__htmlValue';
			$res = $delegate->$methodname($this);
			//$res = call_user_func(array(&$delegate, $fieldname.'__htmlValue'), $this);
			if ( is_string($res) and DATAFACE_USAGE_MODE == 'edit' and $this->checkPermission('edit', array('field'=>$fieldname)) and !$this->_table->isMetaField($fieldname) ){
				$res = '<span id="'.$domid.'" df:id="'.$uri.'" class="df__editable">'.$res.'</span>';
			}
			return $res;
		}
		$parent =& $this->getParentRecord();
		if ( isset($parent) and $parent->_table->hasField($fieldname) ){
			return $parent->htmlValue($fieldname, $index, $where, $sort, $params);
		}	
		$val = $this->display($fieldname, $index, $where, $sort);
		$field = $this->_table->getField($fieldname);
		//if ( $field['widget']['type'] != 'htmlarea' ) $val = htmlentities($val,ENT_COMPAT, 'UTF-8');
		if ( $this->_table->isText($fieldname) and $field['widget']['type'] != 'htmlarea' ) $val = nl2br($val);
		
		if ( $this->_table->isBlob($fieldname) or $this->_table->isContainer($fieldname) ){
			if ( $this->getLength($fieldname, $index,$where,$sort) > 0 ){
				if ( $this->isImage($fieldname, $index, $where, $sort) ){
					$val = '<img src="'.$val.'"';
					if ( !isset($params['width']) and isset($field['width']) ){
						$params['width'] = $field['width'];
					}
					foreach ($params as $pkey=>$pval){
						$val .= ' '.$pkey.'="'.$pval.'"';
					}
					$val .= '/>';
				} else {
					$file_icon = df_translate(
						$this->getMimetype($fieldname,$index,$where,$sort).' file icon',
						df_absolute_url(DATAFACE_URL).'/images/document_icon.gif'
						);
					$val = '<img src="'.$file_icon.'"/><a href="'.$val.'" target="_blank"';
					foreach ($params as $pkey=>$pval){
						$val .= ' '.$pkey.'="'.$pval.'"';
					}
					$val .= '>View Field Content In New Window ('.$this->getMimetype($fieldname, $index,$where,$sort).')</a>';
				}
			} else {
				$val = "(Empty)";
			}
		}
		if ( is_string($val) and DATAFACE_USAGE_MODE == 'edit' and $this->checkPermission('edit', array('field'=>$fieldname))  and !$this->_table->isMetaField($fieldname)){
			$val = '<span id="'.$domid.'" df:id="'.$uri.'" class="df__editable">'.$val.'</span>';
		}
		return $val;
		
	
	
	}
	
	
	/**
	 * Returns a preview of a field.  A preview is a shortened version of the text of a field
	 * with all html tags stripped out.
	 *
	 * @param $fieldname The name of the field for which the preview pertains.
	 * @param $index The index of the field (for related field only).
	 * @param $maxlength The number of characters for the preview.
	 * @return string
	 */
	function preview($fieldname, $index=0, $maxlength=255, $where=0, $sort=0){
	
		$strval = strip_tags($this->display($fieldname,$index, $where, $sort));
		$out = substr($strval, 0, $maxlength);
		if ( strlen($strval)>$maxlength) {
			$out .= '...';
		}	 
		return $out;
		
	}
	
	/**
	 * @alias display()
	 */
	function printValue($fieldname, $index=0, $where=0, $sort=0 ){
		return $this->display($fieldname, $index, $where, $sort);
	}
	
	/**
	 * @alias display()
	 */
	function printval($fieldname, $index=0, $where=0, $sort=0){
		return $this->display($fieldname, $index, $where, $sort);
	}
	
	/**
	 * @alias display()
	 */
	function q($fieldname, $index=0, $where=0, $sort=0){
		return $this->display($fieldname, $index, $where, $sort);
	}
	
	/**
	 * Gets the value of a field as a string and prepared for display on an html page.
	 * All html special characters are replaced with their associated HTML entities.
	 */
	function qq($fieldname, $index=0, $where=0, $sort=0){
		return $this->htmlValue($fieldname, $index, $where, $sort);
	}
	
	/**
	 * Indicates whether a field has a value.
	 */
	function hasValue($fieldname){
		return (isset( $this->_values) and array_key_exists($fieldname, $this->_values) );
	}
	
	/**
	 * Returns an array of the roles assigned to the current user with respect
	 * to this record.
	 * @param array $params Extra parameters.  See getPermissions() for possible
	 *		values.
	 * @returns array of strings
	 */
	function getRoles($params=array()){
		return $this->_table->getRoles($this, $params);
		
	}
	
	/**
	 * Returns permissions as specified by the current users' roles.  This differs
	 * from getPermissions() in that getPermissions() allows the possibility of
	 * defining custom permissions not associated with a user role.
	 * @param array $params See getPermissions() for possible values.
	 */
	function getRolePermissions($params=array()){
		return $this->_table->getRolePermissions($this, $params);
	}
	
	
	/**
	 * Gets the permissions associated witha  field.  Permissions are returned
	 * as an associative array whose keys are the permissions names, where a 
	 * permission is granted only if a key by its name exists and evaluates to
	 * true.
	 * @param array $params An associative array with parameters:
	 *				field :  The name of a field to obtain permissions on.
	 *				relationship: The name of a relationship to obtain permissions on.
	 * @param string $params[field] The name of a field to obtain permissions on.
	 * @param string $params[relationship] The name of a relationship to obtain permissions on.
	 * @param array $params[fieldmask] Permissions mask to apply to field permissions.
	 * @param array $params[recordmask] Permissions mask to apply to record permissions.
	 *
	 * @see Dataface_PermissionsTool
	 * @see Dataface_Table::getPermissions()
	 * @see Dataface_Table::getFieldPermissions()
	 * @see Dataface_Table::getRelationshipPermissions()
	 */
	function getPermissions($params=array()){
		$params['record'] =& $this;
		return $this->_table->getPermissions($params);
	}
	
	/**
	 * Checks to see is a particular permission is granted in this record.
	 *
	 * @param $perm The name of a permission.
	 * @param $params Associative array of parameters:
	 *  		field: The name of a field to check permissions on.
	 * 			relationship: The name of a relationship to check permissions on.
	 * @return boolean whether permission is granted on the current record.
	 */
	function checkPermission($perm, $params=array()){
		$perms = $this->getPermissions($params);
		return ( isset($perms[$perm]) and $perms[$perm] );
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
		//if ( func_num_args() > 2 ){
		//	$params =& func_get_arg(2);
		//}
		//else {
		//	$params = array();
		//}
		
		if ( !is_array($params) ){
			$params = array('message'=> &$params);
		}
		$res = $this->_table->validate($fieldname, $value, $params);
		
		$field =& $this->_table->getField($fieldname);
		
		if ( $field['widget']['type'] == 'file' and @$field['validators']['required'] and is_array($value) and $this->getLength($fieldname) == 0 and !is_uploaded_file(@$value['tmp_name'])){
				// This bit of validation operates on the upload values assuming the file was just uploaded as a form.  It assumes
				// that $value is of the form
				//// eg: array('tmp_name'=>'/path/to/uploaded/file', 'name'=>'filename.txt', 'type'=>'image/gif').
				$params['message'] = "$fieldname is a required field.";
				$params['message_i18n_id'] = "Field is a required field";
				$params['message_i18n_params'] = array('field'=>$fieldname);
				return false;
		}
		if ( $res ){
			$delegate =& $this->_table->getDelegate();
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
		
		if ($res){
			$parent =& $this->getParentRecord();
			if ( isset($parent) and $parent->_table->hasField($fieldname) ){
				$res = $parent->validate($fieldname, $value, $params);
			}
		}
		
		return $res;
		
		
	}
	

	
	
	
	
	
	/**
	 * Sometimes a link is specified to be associated with a field.  These links
	 * will be displayed on the forms next to the associated field.
	 * Links may be specified in the fields.ini file with a "link" attribute; or
	 * in the delegate with the $fieldname__link() method.
	 */

	
	/**
	 * Clears all fields in this record.
	 */
	function clearValues(){
		$this->_values = array();
		$this->_relatedValues = array();
		$this->_valCache = array();
		
		$this->clearFlags();
		
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->clearValues();
		}
	
	}
	
	/**
	 * Clears the value in a field.
	 */
	function clearValue($field){
		
		unset($this->_values[$field]);
		$this->clearFlag($field);
		
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->clearValue();
		}
	}
	
	
	
	/**
	 * Signifies that we are beginning a transaction.  So a snapshot of the values
	 * can be saved and possibly be later reverted.
	 */
	function setSnapshot(){
		 
		$this->clearFlags();
		if ( isset($this->_values) ){
			// If there are no values, then we don't need to set the snapshot
			$this->_oldValues = unserialize(serialize($this->getValues()));
		}
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->setSnapshot();
		}
		
		
	}
	
	/**
	 * Indicates whether a snapshot of values exists.
	 */
	function snapshotExists(){
		return (is_array($this->_oldValues) and count($this->_oldValues) > 0);
	}
	
	/**
	 * Clears a snapshot of values.  Note that for an update to take place 
	 * properly, a snapshot should be obtained before any changes are made to the 
	 * Table schema.
	 */
	function clearSnapshot(){
		$this->_oldValues = null;
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->clearSnapshot();
		}
	}
	
	/**
	 * Returns the snapshot values for this table.  These are copies of the values
	 * as they appeared the last time a snapshot was taken.
	 */
	function &getSnapshot($fields=''){
		if ( is_array($fields) ){
			$out = array();
			foreach ($fields as $field){
				if ( isset( $this->_oldValues[$field] ) ){
					$out[$field] = $this->_oldValues[$field];
				}
			}
			
			return $out;
		} else {
			return $this->_oldValues;
		}
	}
	
	
	function snapshotKeys(){
		return $this->getSnapshot(array_keys($this->_table->keys()));
	}
	
	/**
	 * Indicates whether a value in the record has been updated since the flags have been cleared.
	 * @param $fieldname The name of the field we are checking
	 * @param $index Either the integer index of the record we are checking or an array query to match a record.
	 */
	function valueChanged($fieldname, $index=0, $checkParent=false){
		if ( strpos($fieldname, '.') !== false ){
			// This is a related field, so we have to check the relationship for dirty flags
			$path = explode('.', $fieldname);
			
			if ( is_array($index) ){
				$index = $this->getRelatedIndex($index);
			}
			
			return (isset( $this->_relatedDirtyFlags[$path[0]]) and 
					isset( $this->_relatedDirtyFlags[$path[0]][$path[1]]) and 
					$this->_relatedDirtyFlags[$path[0]][$path[1]] === true );
		} else {
			// this is a local related field... just check the local dirty flags array.
			if ( $checkParent ){
				$parent =& $this->getParentRecord();
				if ( isset($parent) and $parent->_table->hasField($fieldname) ){
					return $parent->valueChanged($fieldname, $index);
				}
			}
			return (@$this->_dirtyFlags[$fieldname]);
		}
	}
	
	
	/**
	 * Boolean indicator to see whether the record has been changed since its flags were last cleared.
	 */
	function recordChanged($checkParent=false){
		if ($checkParent){
			$parent =& $this->getParentRecord();
			if ( isset($parent) ){
				$res = $parent->recordChanged();
				if ( $res ) return true;
			}
		}
		
		$fields =& $this->_table->fields();
		foreach ( array_keys( $fields) as $fieldname){
			if ( $this->valueChanged($fieldname) ) return true;
		}
		return false;
	}
	
	
	
	/**
	 * Clears all of the dirty flags to indicate that this record is up to date.
	 */
	function clearFlags(){
		$keys = array_keys($this->_dirtyFlags);
		foreach ( $keys as $i) {
			$this->_dirtyFlags[$i] = false;
			$this->vetoFields[$i] = false;
		}
		foreach (array_keys($this->_relatedDirtyFlags) as $rel_name){
			foreach ( array_keys($this->_relatedDirtyFlags[$rel_name]) as $field_name){
				$this->_relatedDirtyFlags[$rel_name][$field_name] = false;
			}
		}
		
		// Clear the snapshot of old values.
		$this->clearSnapshot();
		
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->clearFlags();
		}
		
	}
	
	/**
	 * Clears the dirty flag on a particular field.
	 */
	function clearFlag($name){
		if ( strpos($name, '.') !== false ){
			// This is a related field.  We store dirty flags in the relationship array.
			$path = explode('.', $name);
			
			if ( !isset($this->_relatedDirtyFlags[$path[0]]) ){
				return;
			}
			if ( !isset($this->_relatedDirtyFlags[$path[0]][$path[1]]) ){
				return;
			}
			$this->_relatedDirtyFlags[$path[0]][$path[1]] = false;
		} else {
			
			$this->_dirtyFlags[$name] = false;
			$this->vetoFields[$name] = false;
			$parent =& $this->getParentRecord();
			if ( isset($parent) and $parent->_table->hasField($name) ){
				$parent->clearFlag($name);
			}
		}
	}
		
	
	/**
	 * Sets a dirty flag on a field to indicate that it has been changed.
	 */
	function setFlag($fieldname, $index=0){
		if ( strpos($fieldname, '.') !== false ){
			// This is a related field.  We store dirty flags in the relationship array.
			$path = explode('.', $fieldname);
			
			
			if ( !isset($this->_relatedDirtyFlags[$path[0]]) ){
				$this->_relatedDirtyFlags[$path[0]] = array();
			}
			$this->_relatedDirtyFlags[$path[0]][$path[1]] = true;
		} else {
			// This is a local field
			$this->_dirtyFlags[$fieldname] = true;
			$parent =& $this->getParentRecord();
			if ( isset($parent) and $parent->_table->hasField($fieldname)){
				$parent->setFlag($fieldname, $index);
			}
		}
	}
	
	
	/**
	 * Boolean value indicating if a particular field is loaded.
	 */
	function isLoaded($fieldname){
		$parent =& $this->getParentRecord();
		if ( isset($parent) and $parent->_table->hasField($fieldname) ){
			return $parent->isLoaded($fieldname);
		}
		return ( isset( $this->_isLoaded[$fieldname] ) and $this->_isLoaded[$fieldname]);
	}
	
	
	
		
	/**
	 * Sometimes a link is specified to be associated with a field.  These links
	 * will be displayed on the forms next to the associated field.
	 * Links may be specified in the fields.ini file with a "link" attribute; or
	 * in the delegate with the $fieldname__link() method.
	 *
	 * @deprecated See Dataface_Record::getLink()
	 */
	function getLink($fieldname){
		
		$field =& $this->_table->getField($fieldname);
		if ( PEAR::isError($field) ){
			return null;
		}
		$table =& Dataface_Table::loadTable($field['tablename']);
		$delegate =& $table->getDelegate();
		if ( !$table->hasField($fieldname) ) return null;
		
		
		
		// Case 1: Delegate is defined -- we use the delegate's link
		if ( method_exists($delegate, $fieldname."__link") ){
			$methodname = $fieldname."__link";
			$link = $delegate->$methodname($this);
			//$link = call_user_func(array(&$delegate, $fieldname."__link"), $this);
			
		
		// Case 2: The link was specified in an ini file.
		} else if ( isset($field['link']) ){
			
			$link = $field['link'];
			
		// Case 3: The link was not specified
		} else {
			
			$link = null;
		}
		
		
		if ( is_array($link) ){
			foreach ( array_keys($link) as $key){
				$link[$key] = $this->parseString($link[$key]);
			}
			
			
			return $link;
			
		} else if ( $link  ){
			return $this->parseString($link);
		} else {
			
			return null;
		}
	
	}
	
	
	/**
	 * Returns the title of this particular record.
	 */
	function getTitle($dontGuess=false){
		if ( !isset($this->_title) ){
			$delegate =& $this->_table->getDelegate();
			$title = null;
			if ( $delegate !== null and method_exists($delegate, 'getTitle') ){
				
				$title = $delegate->getTitle($this);
			} else {
			
				$parent =& $this->getParentRecord();
				if ( isset($parent) ){
					$title = $parent->getTitle(true);
				}
			}
			
			if ( $dontGuess ){
				if ( isset($title) ) $this->_title = $title;
				return $title;
			}
			
			if ( !isset($title) ){	
				$fields =& $this->_table->fields();
				$found_title = false; // flag to specify that a specific title field has been found
									  // declared by the 'title' flag in the fields.ini file.
									  
				foreach (array_keys($fields) as $field_name){
					if ( isset($fields[$field_name]['title']) ){
						$title = $this->display($field_name);
						$found_title = true;
					}
					else if ( !isset($title) and $this->_table->isChar($field_name) ){
						$title = $this->display($field_name );
					}
					if ( $found_title) break;
				}
				
				if ( !isset( $title) ){
					$title = "Untitled ".$this->_table->tablename." Record";
				}
				
			}
			$this->_title = $title;
		}
		
		return $this->_title;
		
	
	}
	
	
	function getMimetype($fieldname,$index=0,$where=0,$sort=0){
		$field =& $this->_table->getField($fieldname);
		if ( isset($field['mimetype'])  and strlen($field['mimetype']) > 0 ){
			return $this->getValue($field['mimetype'], $index,$where,$sort);
		}
		
		if ( $this->_table->isContainer($fieldname) ){
			$filename = $this->strval($fieldname,$index,$where,$sort);
			if ( strlen($filename) > 0 ){
				$path = $field['savepath'].'/'.$filename;
				$mimetype='';
				if(!extension_loaded('fileinfo')) {
					@dl('fileinfo.' . PHP_SHLIB_SUFFIX);
				}
				if(extension_loaded('fileinfo')) {
					$res = finfo_open(FILEINFO_MIME); /* return mime type ala mimetype extension */
					$mimetype = finfo_file($path);
				} else if (function_exists('mime_content_type')) {
					
				
					$mimetype = mime_content_type($path);
					
				}
				
				
				return $mimetype;
			}
		}
		return '';
		
	}
	
	function callDelegateFunction($function, $fallback=null){
		$del =& $this->_table->getDelegate();
		$parent =& $this->getParentRecord();
		if ( isset($del) && method_exists($del, $function) ){
			return $del->$function($this);
			//return call_user_func(array(&$del, $function), $this);
		} else if ( isset($parent) ){
		
			return $parent->callDelegateFunction($function, $fallback);
		} else {
			return $fallback;
		}
	
	}
	
	function getDescription(){
		if ( $res = $this->callDelegateFunction('getDescription') ){
			return $res;
		} else if ( $descriptionField = $this->_table->getDescriptionField() ){
			return $this->htmlValue($descriptionField);
		} else {
			return '';
		}
	
	}
	
	function getCreated(){
		if ( $res = $this->callDelegateFunction('getCreated') ){
			return $res;
		} else if ( $createdField = $this->_table->getCreatedField() ){
			if ( strcasecmp($this->_table->getType($createdField),'timestamp') === 0 ){
				$date = $this->val($createdField);
				return strtotime($date['year'].'-'.$date['month'].'-'.$date['day'].' '.$date['hours'].':'.$date['minutes'].':'.$date['seconds']);
				
			}
			return strtotime($this->display($createdField));
		} else {
			return '';
		}
	}
	
	function getCreator(){
		if ( ($res = $this->callDelegateFunction('getCreator',-1)) !== -1 ){
			return $res;
		} else if ( $creatorField = $this->_table->getCreatorField() ){
			return $this->display($creatorField);
		} else {
			return '';
		}
	}
	
	function getLastModified(){
		if ( $res = $this->callDelegateFunction('getLastModified') ){
			return $res;
		} else if ( $lastModifiedField = $this->_table->getLastUpdatedField() ){
			if ( strcasecmp($this->_table->getType($lastModifiedField),'timestamp') === 0 ){
				$date = $this->val($lastModifiedField);
				return strtotime($date['year'].'-'.$date['month'].'-'.$date['day'].' '.$date['hours'].':'.$date['minutes'].':'.$date['seconds']);
				
			}
			$strtime = $this->strval($lastModifiedField);
			if ( $strtime){
				return strtotime($strtime);
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
	
	function getBody(){
		if ( $res = $this->callDelegateFunction('getBody') ){
			return $res;
		} else if ( $bodyField = $this->_table->getBodyField() ){
			return $this->htmlValue($bodyField);
		} else {
			return '';
		}
	}
	
	function getPublicLink($params=null){
		if ( $res = $this->callDelegateFunction('getPublicLink') ){
			return $res;
		} else {
			return $this->getURL($params);
		}
	
	}
	

	
	function isImage($fieldname, $index=0, $where=0, $sort=0){
		return preg_match('/^image/', $this->getMimetype($fieldname,$index,$where,$sort));
	
	}
	

	
	
	/**
	 * Returns actions associated with this record.
	 * @param $params An associative array of parameters for the actions to be retrieved.
	 *			Possible keys include:
	 *				category => the name of the category for the actions.
	 */
	function getActions($params=array()){
		$params['record'] =& $this;
		$actions = $this->_table->getActions($params);
		
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$actions = array_merge_recursive_unique($parent->getActions($params), $actions);
		}
		return $actions;
	}
	
	
	/**
	 * Returns an array of parts of the bread-crumbs leading to this record.
	 * The array is of the form [Part Label]->[Part URL]
	 */
	function getBreadCrumbs(){
		$delegate =& $this->_table->getDelegate();
		if ( $delegate !== null and method_exists($delegate, 'getBreadCrumbs') ){
			return $delegate->getBreadCrumbs($this);
		}
		
		
		if ( ( $parent = $this->getParent() ) !== null ){
			$bc = $parent->getBreadCrumbs();
			$bc[$this->getTitle()] = $this->getURL( array('-action'=>'browse'));
			return $bc;
		}
		
		
		return array(
			$this->_table->getLabel() => Dataface_LinkTool::buildLink(array('-action'=>'list', '-table'=>$this->_table->tablename)), 
			$this->getTitle() => $this->getURL(array('-action'=>'browse'))
			);
	}
	
	/**
	 * Returns the URL to this record.
	 *
	 * @param $params An array of parameters to use when building the url.  e.g., array('-action'=>'edit')
	 * would cause the URL to be for editing this record.
	 *
	 * @returns String url to this record.
	 */
	function getURL($params=array()){
		if ( is_string($params) ){
			$pairs = explode('&',$params);
			$params = array();
			foreach ( $pairs as $pair ){
				list($key,$value) = explode('=', $pair);
				$params[$key] = $value;
			}
		}
		$params['-table'] = $this->_table->tablename;
		if ( !isset($params['-action']) ) $params['-action'] = 'browse';
		foreach (array_keys($this->_table->keys()) as $key){
			$params[$key] = '='.$this->strval($key);
		}
		
		$delegate =& $this->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getURL') ){
			$res = $delegate->getURL($this, $params);
			if ( $res and is_string($res) ) return $res;
		}
		
		import('Dataface/LinkTool.php');
		$linkTool =& Dataface_LinkTool::getInstance();
	
		return $linkTool->buildLink($params ,false);
	}
	
	/**
	 * Moves a related record up one in the list.
	 */
	function moveUp($relationship, $index){
		
		$r =& $this->_table->getRelationship($relationship);
		$order_col = $r->getOrderColumn();
		if ( PEAR::isError($order_col) ) return $order_col;
		$order_table =& $r->getTable($order_col);
		if ( PEAR::isError($order_table) ) return $order_table;
		
		if ( $index == 0 ) return PEAR::raiseError("Cannot move up index 0");
		$it =& $this->getRelationshipIterator($relationship, $index-1, 2);
		if ( PEAR::isError($it) ) return $it;
		if ( $it->hasNext() ){
			$prev_record =& $it->next();
			if ( $it->hasNext() ){
				$curr_record =& $it->next();
			}
		}
		
		if ( !isset($prev_record) || !isset($curr_record) ){
			return PEAR::raiseError('Attempt to move record up in "'.$relationship.'" but the index "'.$index.'" did not exist.');
		}
		
		if ( intval($prev_record->val($order_col)) == intval($curr_record->val($order_col)) ){
			// The relationship's records are not ordered yet (consecutive records should have distinct order values.
			$res = $this->sortRelationship($relationship);
			if ( PEAR::isError($res) ) return $res;
			return $this->moveUp($relationship, $index);
		}
		
		
		$prev = $prev_record->toRecord($order_table->tablename);
		$curr = $curr_record->toRecord($order_table->tablename);
		$temp = $prev->val($order_col);
		$res = $prev->setValue($order_col, $curr->val($order_col));
		if (PEAR::isError($res) ) return $res;
		$res = $prev->save();
		if ( PEAR::isError($res) ) return $res;
		$res = $curr->setValue($order_col, $temp);
		if ( PEAR::isError($res) ) return $res;
		$res = $curr->save();
		if ( PEAR::isError($res) ) return $res;
		
		return true;
		
		
	}
	
	/**
	 * Moves a related record down one in the list.
	 */
	function moveDown($relationship, $index){
		return $this->moveUp($relationship, $index+1);
	}
	
	
	
	/**
	 * Sorts the records of this relationship (or just a subset of the
	 * relationship.
	 * @param string $relationship The name of the relationship to sort.
	 * @param int $start The start position of the sorting (optional).
	 * @param array $subset An array of Dataface_RelatedRecord objects representing the new sort order.
	 */
	function sortRelationship($relationship, $start=null, $subset=null){
		$r =& $this->_table->getRelationship($relationship);
		$order_col = $r->getOrderColumn();
		if ( PEAR::isError($order_col) ) return $order_col;
		$order_table =& $r->getTable($order_col);
		if ( PEAR::isError($order_table) ) return $order_table;
		
		// Our strategy for sorting only a subset.
		// Let R be the list of records in the relationship ordered
		// using the default order.
		//
		// Let A be the list of records in our subset of R using the 
		// default order.
		// 
		// Let b = A[0]
		// Let a be the predecessor of b.
		// Let c be the last record in A.
		// Let d be the successor of c.
		// Let B = A union {a,d}
		// For any record x in R, let ord(x) be the value of the order column
		// in x.
		//
		//
		//  The algorithm we will use to sort our subset is as follows:
		// 	if ( !exists(a) or ord(a) < ord(b) )
		//		and 
		//	( !exists(d) or ord(c) < ord(d) )
		//		and
		//	( ord(c) - ord(b) >= count(A) ){
		//		sort(A)
		//  } else {
		//		sort(R)
		//	}
		
		
		if ( isset($start) ){
			// We are dealing with a subset, so let's go through our algorithm
			// to see if we can get away with only sorting the subset
			//$countR = $this->numRelatedRecords($relationship);
			$aExists = ($start > 0);
			$countA = count($subset);
			$B =& $this->getRelatedRecordObjects($relationship, max($start-1,0), $countA+2);
			$countB = count($B);
		
			
			if ( $aExists ){
				$dExists = ( $countB-$countA >=2 );
			} else {
				$dExists = ($countB-$countA >= 1);
			}
			
			
			$AOffset = 0;
			if ( $aExists ) $AOffset++; 
			
			
			if ( (!$aExists or $B[0 + $AOffset]->val($order_col) > $B[0]->val($order_col) )
					and
				 (!$dExists or $B[$countA-1+$AOffset]->val($order_col) < $B[$countB-1]->val($order_col) )
				 	and
				 ( ($B[$countA-1+$AOffset]->val($order_col) - $B[0+$AOffset]->val($order_col)) >= ($countA - 1) ) ){
				 
				
				 $sortIndex = array();
				 $i = $B[0+$AOffset]->val($order_col);
				 foreach ($subset as $record){
				 	$sortIndex[$record->getId()] = $i++;
				 }
				 
				 
				 $i0 = $AOffset;
				 $i1 = $countA+$AOffset;
				 for ( $i = $i0; $i<$i1; $i++ ){
				 	$B[$i]->setValue($order_col, $sortIndex[ $B[$i]->getId() ] );
				 	$res = $B[$i]->save();
				 	if ( PEAR::isError($res) ) echo $res->getMessage();
				 }
				 $this->clearCache();
				 return true;
			}
			
			
			
		}
		
		$it =& $this->getRelationshipIterator($relationship, 'all');
		$i = 1;
		
		while ( $it->hasNext() ){
			$rec =& $it->next();
			//$rec->setValue($order_col, $i++);
			$orderRecord =& $rec->toRecord($order_table->tablename);
			$orderRecord->setValue($order_col, $i++);
			$res = $orderRecord->save();
			if ( PEAR::isError($res) ) return $res;
			unset($rec);
			unset($orderRecord);
			
		}
		$this->clearCache();
		
	}
	

	/*
	 * IO Methods.
	 * As of Dataface 0.6 Dataface_IO is being deprecated in favour
	 * of folding IO methods inside the Dataface_Record methods.
	 */
	
	
	/**
	 * Saves the current record to the database.
	 */
	function save($lang=null, $secure=false){
		return df_save_record($this, $this->strvals(array_keys($this->_table->keys())), $lang, $secure);
	}
	
	/**
	 * Deletes the record from the database.
	 */
	function delete(){
		trigger_error('Not implemented yet: '.Dataface_Error::printStackTrace(), E_USER_ERROR);
	}
	
	function toJS($fields=null){
		$strvals = $this->strvals($fields);
		$out = array();
		foreach ( $strvals as $key=>$val){
			if ( $this->checkPermission('view', array('field'=>$key)) ){
				if ( $this->_table->isInt($key) or $this->_table->isFloat($key) ){
					$out[] = "'{$key}': ".($val ? $val : 'null');
				} else {
	
					$out[] = "'{$key}': '".str_replace("\n","\\n",str_replace("\r","",addslashes($val)))."'";
				}
			}
		}
		$out[] = "'__title__': '".addslashes($this->getTitle())."'";
		$out[] = "'__url__': '".addslashes($this->getURL())."'";
		
		return '{'.implode(',',$out).'}';
		
	}

	
	
	
	
	
}

/**
 * An iterator for iterating through Record objects.
 */
class Dataface_RecordIterator {

	var $_records;
	var $_keys;
	var $_tablename;
	function Dataface_RecordIterator($tablename, &$records){
		$this->_records =& $records;
		$this->_keys = array_keys($records);
		$this->_tablename = $tablename;
		$this->reset();
	}
	
	function &next(){
		$out =& new Dataface_Record($this->_tablename, $this->_records[current($this->_keys)]);
		next($this->_keys);
		return $out;
	}
	
	function &current(){
		return new Dataface_Record($this->_tablename, $this->_records[current($this->_keys)]);
	}
	
	function reset(){
		return reset($this->_keys);
	}
	
	function hasNext(){
		
		return (current($this->_keys) !== false);
	}
	
}


/**
 * An iterator for iterating through related records.
 */
class Dataface_RelationshipIterator{
	var $_record;
	var $_relationshipName;
	var $_records;
	var $_keys;
	var $_where;
	var $_sort;
	function Dataface_RelationshipIterator(&$record, $relationshipName, $start=null, $limit=null, $where=0, $sort=0){
		$this->_record =& $record;
		$this->_relationshipName = $relationshipName;
		$this->_where = $where;
		$this->_sort = $sort;
		if ( $start !== 'all' ){
		
			$this->_records =& $record->getRelatedRecords($relationshipName, true, $start, $limit, $where, $sort);
		} else {
			$this->_records =& $record->getRelatedRecords($relationshipName, 'all',$where, $sort);
		}
		if ( is_array($this->_records) ){
			$this->_keys = array_keys($this->_records);
		} else {
			$this->_keys = array();
		}
	}
	
	function &next(){
		$out =& $this->current();
		next($this->_keys);
		return $out;
	}
	
	function &current(){
		$rec =& new Dataface_RelatedRecord($this->_record, $this->_relationshipName, $this->_records[current($this->_keys)]);
		$rec->setValues($this->_record->_relatedMetaValues[$this->_relationshipName][$this->_where][$this->_sort][current($this->_keys)]);
		return $rec;
	}
	
	function reset(){
		return reset($this->_keys);
	}
	
	function hasNext(){
		return (current($this->_keys) !== false);
	}
}

