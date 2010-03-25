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

/*
 * 	Encapsulates a table of a data driven data type.
 */

if ( !defined('DATAFACE_EXTENSION_LOADED_APC') ){
	
	define('DATAFACE_EXTENSION_LOADED_APC',extension_loaded('apc'));

}

import( 'PEAR.php');
import( 'Dataface/Error.php');
import( 'Dataface/Globals.php');
import( 'Dataface/Relationship.php');
import( 'Dataface/converters/date.php');
import( 'Dataface/Application.php');
//require_once dirname(__FILE__).'/../config.inc.php';
import( 'SQL/Parser.php');
import( 'SQL/Parser/wrapper.php');
import( 'Dataface/Serializer.php');
import( 'Dataface/ConfigTool.php');

define('Dataface_Table_UseCache', false);

/**
 * Default permission values.  Exact usage and definition of permissions is not
 * yet complete.
 */
$GLOBALS['Dataface_Table_DefaultFieldPermissions'] = array(
	"view"=>'View',
	"edit"=>'Edit'
	);
	
$GLOBALS['Dataface_Table_DefaultTablePermissions'] = array(
	"view"=>"View",
	"edit"=>"Edit",
	"delete"=>"Delete"
	);

define( 'SCHEMA_INVALID_ADDRESS_ERROR', 1);
define( 'SCHEMA_NO_SUCH_FIELD_ERROR',2);
define( 'SCHEMA_AMBIGUOUS_FIELD_ERROR',3);
define( 'Dataface_SCHEMA_NO_VALUE_ASSIGNED', 4);
define( 'Dataface_SCHEMA_INDEX_OUT_OF_BOUNDS_ERROR', 5);
define( 'Dataface_SCHEMA_SQL_ERROR', 6);
define( 'Dataface_SCHEMA_NO_SUCH_RELATIONSHIP_ERROR',7);
define( 'Dataface_SCHEMA_INVALID_VALUE_ERROR',8);
define( 'DATAFACE_TABLE_SQL_PARSE_ERROR', 9);
define( 'DATAFACE_TABLE_RELATED_RECORD_CREATION_ERROR', 10);
define( 'DATAFACE_TABLE_RELATED_RECORD_REQUIRED_FIELD_MISSING_ERROR',12);
define( 'DATAFACE_TABLE_RECORD_RELATED_RECORD_BLOCKSIZE', 30);
define( 'DATAFACE_TABLE_SQL_ERROR',11);
define( 'SCHEMA_TABLE_NOT_FOUND', 12);


/**
 * A class that represents the table of a table in a database.  This models (and loads)
 * all information about a table and its columns (names, types, keys, etc..), but it also
 * augments these definitions by adding relationships, value lists, and widget types
 * to the fields so that the system knows how a user will interact with the fields.
 * <h2>Usage Example:</h2>
 * <code><pre>
 * $db = mysql_connect('localhost','foo','bar');
 * $table =& Dataface_Table::loadTable('Profiles', $db);
 *		// loads the table ($table is a Singleton - ie: it is a reference to 
 * 		// the table for the 'Profiles' table.
 *  </pre></code>
 */
class Dataface_Table {
	/**
	 * The name of the table that this table represents.
	 */
	var $tablename;
	
	/**
	 * DB connection handle.
	 */
	var $db;
	
	/**
	 * Associative array of field definitions.  Keys are the field names.
	 */
	var $_fields = array();
	
	/**
	 * Associative array of fields that are not a part of this table, but 
	 * have been "grafted" on by a custom query.
	 */
	var $_grafted_fields = null;
	
	
	/**
	 * Associative array of transient fields.  Transient fields are fields
	 * that are not saved in the database.  The are useful for adding fields
	 * to the edit/new record forms without having a corresponding field
	 * in the database.
	 */
	var $_transient_fields = null;
	
	var $_parentTable = null;
	
	
	/**
	 * A List of the tables that are join tables of this table.  A join table
	 * is a table that is keyed on the same primary key as this table and 
	 * store additional information about records of this table.  There must
	 * be a one-to-one correspondence between this table and each of its 
	 * join tables.  This is useful for modelling inheritance.
	 *
	 * For example if we have a people table and a teachers table.  The 
	 * teachers table will contain only attributes relevant to teachers,
	 * but they are also people.  Then the teachers table could be a join
	 * table of the people table.
	 *
	 * This variable is intended to be an associative array with keys
	 * being the names of the join tables, and the values being the Label
	 * for the tables in this context of this join.
	 *
	 * e.g. [teachers] -> 'Teacher Details'
	 *
	 * Please see the __join__() delegate class method if you want to be able
	 * to change the list of join tables depending on the record.
	 */
	var $_joinTables = null;
	
	/**
	 * To contain optional SQL query to obtain records of this table.
	 * Can be specified at the top of the fields.ini file with the __sql__
	 * parameter.
	 */
	var $_sql;
	
	/**
	 * An associative array of fields arranged by tab.  
	 * e.g.: [tab1]=>array('field1','field2', ...)
	 *		 [tab2]=>array('field3','field4', ...)
	 */
	var $_fieldsByTab = null;
	
	
	/**
	 * A cache to keep references to remote fields.
	 */
	var $_relatedFields = array();
	
	/**
	 * Associative array of groups that are used to group fields.
	 */
	var $_fieldgroups = array();
	
	/**
	 * Associative array of tabs that are used to group fields and join records.
	 */
	var $_tabs = array();
	
	/**
	 * Associative array of field definitions for keys.  Each field definition
	 * in this array is simply a reference to the actual field definition in the 
	 * $_fields array.
	 */
	var $_keys = array();
	
	/**
	 * The name of the ini file that stores the information.
	 * @deprecated.
	 */
	var $_iniFile = '';
	
	/**
	 * Associative array that contains the attributes of this table.
	 */
	var $_atts;
	
	/**
	 * Associative array that contains the relationship definitions for this table.
	 */
	var $_relationships = array();
	
	/**
	 * Array to keep track of relationship ranges to be returned.  When Dataface_Record
	 * objects for this table return related records, these values are used as guidelines
	 * for which range of related records should be returned.
	 * array( [Relationship name] -> array(Lower, Upper) )
	 */
	var $_relationshipRanges;
	
	/**
	 * The default range that is used for related records.
	 */
	var $_defaultRelationshipRange = array(0, DATAFACE_TABLE_RECORD_RELATED_RECORD_BLOCKSIZE);
	
	/**
	 * Associative array that contains the valuelist definitions for this table.
	 */
	var $_valuelists;
	
	/**
	 * Reference to a delegate object to handle customizations of behavior.
	 */
	var $_delegate;
	
	/**
	 * Associative array containing status information about the table, such as when it was last updated.
	 */
	var $status;
	

	/**
	 * @type boolean
	 * Whether the relationships have been loaded or not.
	 */
	var $_relationshipsLoaded = false;
	

	/**
	 * Store errors that occur in methods of this class.
	 */
	var $errors = array();
	
	var $_permissions;
	
	var $_serializer;
	
	/**
	 * Security constraints (aka filters) to be applied to all queries created by QueryBuilder.  This is an array of 
	 * key-value pairs [Column name] -> [Column value] so that any records NOT matching the constraint will not be
	 * included in results.  They will be invisible.
	 */
	var $_filters = array();
	
	
	/**
	 * Import filters tasked with handling importing of data into the table.
	 * @type array(Dataface_ImportFilter)
	 */
	var $_importFilters = null;
	
	/**
	 * A reference to the PEAR Config_Container object with this table's 
	 * configuration settings -- including comments.
	 *
	 * @type Config_Container
	 */
	var $_fieldsConfig;
	var $_relationshipsConfig;
	var $_valuelistsConfig;
	
	var $_actionsLoaded = false;
	var $_actionsConfig = null;
	
	// reference to application object
	var $app = null;
	
	
	// flag to indicate of permissions have been loaded yet
	var $_permissionsLoaded = false;
	
	var $translations = null;
	
	var $_cache = array();
	
	/**
	 * A list of column names in the metadata table.
	 */
	var $metadataColumns = null;
	
	/**
	 * A query array of security terms to secure queries made on this table.
	 */
	var $_securityFilter = array();
	var $_securityFilterLoaded = false;
	
	
	/**
	 * Summary information.  These track useful information like which field
	 * contains the description of the record or the last modified date, etc..
	 * In essence this is like the dublin core info.
	 */
	var $descriptionField;
	var $createdField;
	var $creatorField;
	var $lastUpdatedField;
	var $publicLinkTemplate;
	var $bodyField;
	
	var $_global_field_properties;
	
	function guessField($types, $patterns, $forcePattern=false){
		$candidates = array();
		$max = null;
		foreach ($this->fields(false,true) as $field){
			$type = strtolower($this->getType($field['name']));
			if ( !isset($types[$type]) ){
				continue;
			}
			
			$score = $types[$type];
			$found=false;
			foreach ($patterns as $pattern=>$value){
				if ( preg_match($pattern, $field['name']) ){
					$score *= $value;
					$found=true;
				}
			}
			if ( $forcePattern and !$found ){
				$score = 0;
			}
			$candidates[$field['name']] = $score;
			if ( !isset($max) ) $max = $field['name'];
			else if ( $candidates[$max] < $score ){
				$max = $field['name'];
			}
		}
		return $max;
	
	}
	
	
	
	function getDescriptionField(){
		if ( !isset($this->descriptionField) ){
			
			$this->descriptionField = $this->guessField(
				array('text'=>10, 'mediumtext'=>10, 'shorttext'=>10, 'longtext'=>2,
					  'varchar'=>1, 'char'=>1),
				array('/description|summary|overview/'=>10, '/desc/'=>2)
				);
			
		}
		return $this->descriptionField;
		
	}
	
	
	function getCreatedField(){
		if ( !isset($this->createdField) ){

			$this->createdField = $this->guessField(
				array('datetime'=>10, 'timestamp'=>10, 'date'=>1),
				array('/created|inserted|added|posted|creation|insertion/i'=>10, '/timestamp/i'=>5)
				);
		
		}
		return $this->createdField;
	}
	
	function getCreatorField(){
		if ( !isset($this->creatorField) ){
			
			$this->creatorField = $this->guessField(
				array('varchar'=>10,'char'=>10,'int'=>5),
				array('/(created.*by)|(owner)|(posted*by)|(author)|(creator)/i'=>10),
				true /** Force a pattern match for field to be considered **/
				);
		
		}
		return $this->creatorField;
	}
	
	function getLastUpdatedField(){
		if ( !isset($this->lastUpdatedField) ){
			$this->lastUpdatedField = $this->guessField(
				array('datetime'=>10,'timestamp'=>12),
				array('/updated|modified|change|modification|update/i'=>10,'/timestamp/i'=>5)
			)	;
		}
		return $this->lastUpdatedField;
	}
	
	function getBodyField(){
		if ( !isset($this->bodyField) ){
			$this->bodyField = $this->guessField(
				array('text'=>10,'longtext'=>10,'mediumtext'=>10),
				array('/main|body|content|profile|writeup|bio/i'=>10)
			);
		}
	}


	/**
	 * Constructor. Please use Dataface_Table::loadTable() instead.
	 * @param tablename The name of the table to load.
	 * @param db A db connection handle.
	 */
	function Dataface_Table($tablename, $db=null, $quiet=false){
		if ( !$tablename || !is_string($tablename) ){
			trigger_error(Dataface_Error::printStackTrace("Invalid tablename specified.", E_USER_ERROR));
		}
		if ( strpos($tablename,'`') !== false ){
			trigger_error(Dataface_Error::printStackTrace("Invalid character found in table '$tablename'."), E_USER_ERROR);
			
		}
		import('Dataface/Record.php');
		$this->app =& Dataface_Application::getInstance();
		// register this table name with the application object so we can keep
		// track of which tables are used on each request.  This helps with 
		// caching.
		$this->app->tableNamesUsed[] = $tablename;
		$this->tablename = $tablename;
		if ( $db === null and defined('DATAFACE_DB_HANDLE') ) $db = DATAFACE_DB_HANDLE;
		$this->db = $db;
		$this->_permissions = Dataface_PermissionsTool::getRolePermissions($this->app->_conf['default_table_role']);
		
		
		$this->tablename = ereg_replace(' ', '', $this->tablename);
			// prevent malicious SQL injection
			
		$this->_atts = array();
		$this->_atts['name'] = $this->tablename;

		$this->_atts['label'] = (isset( $this->app->_tables[$this->tablename] ) ? $this->app->_tables[$this->tablename] : $this->tablename);
		
		$mod_times =& Dataface_Table::getTableModificationTimes();
		
		$apc_key = DATAFACE_SITE_PATH.'-Table.php-'.$this->tablename.'-columns';
		$apc_key_fields = $apc_key.'-fields';
		$apc_key_keys = $apc_key.'-keys';
		$apc_key_mtime = $apc_key.'__mtime';
		if ( DATAFACE_EXTENSION_LOADED_APC 
			and
				( !@$_GET['--refresh-apc'] )
			and 
				( @$mod_times[$this->tablename] < apc_fetch($apc_key_mtime) )
			and 
				( $this->_fields = apc_fetch($apc_key_fields) )
			and
				( $this->_keys = apc_fetch($apc_key_keys) )
			){
				// no need to refresh the cache
				$fieldnames = array_keys($this->_fields);
					
		} else { 
						
			 
			
			
			$res = mysql_query("SHOW COLUMNS FROM `".$this->tablename."`", $this->db);
			if ( !$res ){
				if ( $quiet ){
					return PEAR::raiseError("Error performing mysql query to get column information from table '".$this->tablename."'.  The mysql error returned was : '".mysql_error($this->db)."'.\n<br>".Dataface_Error::printStackTrace());
				} else {
					trigger_error("Error performing mysql query to get column information from table '".$this->tablename."'.  The mysql error returned was : '".mysql_error($this->db)."'.\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
				}
				
			}
	
			if ( mysql_num_rows($res) > 0 ){
				while ( $row = mysql_fetch_assoc($res) ){
					/*
					 Example row as follows:
					 Array
					(
						[Field] => id
						[Type] => int(7)
						[Null] =>  
						[Key] => PRI
						[Default] =>
						[Extra] => auto_increment
					)
					*/
					
					
					
					$widget = array();
					$widget['label'] = ucfirst(str_replace('_',' ',$row['Field']));
					$widget['description'] = '';
					$widget['label_i18n'] = $this->tablename.'.'.$row['Field'].'.label';
					$widget['description_i18n'] = $this->tablename.'.'.$row['Field'].'.description';
					$widget['macro'] = '';
					$widget['helper_css'] = '';
					$widget['helper_js'] = '';
					$widget['type'] = 'text';
					$widget['class'] = '';
					$widget['atts'] = array();
					if ( ereg( 'text', $row['Type']) ){
						$widget['type'] = 'textarea';
					} else if  ( ereg( 'blob', $row['Type']) ){
						$widget['type'] = 'file';
					}
						
					
					
					$widget['class'] = 'default';
				
					$row['tablename'] = $this->tablename;
					$row['widget'] =& $widget;
					$row['tableta'] = 'default';
					$row['vocabulary'] = '';
					$row['enforceVocabulary'] = false;
					$row['validators'] = array();
					$row['name'] = $row['Field'];
					$row['permissions'] = Dataface_PermissionsTool::getRolePermissions($this->app->_conf['default_field_role']);
					$row['repeat'] = false;
					$row['visibility'] = array('list'=>'visible', 'browse'=>'visible', 'find'=>'visible');
					
	
					
					
					
						
					
					
					
					
					
					$this->_fields[ $row['Field'] ] = $row;
					if ( strtolower($row['Key']) == strtolower('PRI') ){
						$this->_keys[ $row['Field'] ] =& $this->_fields[ $row['Field'] ];
					}
					
					unset($widget);
				}
			}
			
			mysql_free_result($res);
			
			
			
			
			
			// check for obvious field types
			$fieldnames = array_keys($this->_fields);
			foreach ($fieldnames as $key){
				$matches = array();
	
				if ( preg_match( '/^(.*)_mimetype$/', $key, $matches) and 
					isset( $this->_fields[$matches[1]] ) /*and 
					($this->isBlob($matches[1]) or $this->isContainer($matches[1]))*/ ){
					
					$this->_fields[$key]['widget']['type'] = 'hidden';
					$this->_fields[$matches[1]]['mimetype'] = $key;
					$this->_fields[$key]['metafield'] = true;
				} else if ( preg_match( '/^(.*)_filename$/', $key, $matches) and 
					isset( $this->_fields[$matches[1]] ) and 
					$this->isBlob($matches[1]) ){
					$this->_fields[$key]['widget']['type'] = 'hidden';
					$this->_fields[$matches[1]]['filename'] = $key;
					$this->_fields[$key]['metafield'] = true;
				} else if ( preg_match('/password/', strtolower($key) ) ){
					$this->_fields[$key]['widget']['type'] = 'password';
				} else if ( $this->_fields[$key]['Extra'] == 'auto_increment'){
					$this->_fields[$key]['widget']['type'] = 'hidden';
				} else if ( preg_match('/^date/', strtolower($this->_fields[$key]['Type']) ) ){
					$this->_fields[$key]['widget']['type'] = 'calendar';
					
					if ( !preg_match('/time/', strtolower($this->_fields[$key]['Type']) ) ){
						$this->_fields[$key]['widget']['showsTime'] = false;
						$this->_fields[$key]['widget']['ifFormat'] = '%Y-%m-%d';
					}
				} else if ( preg_match('/timestamp/', strtolower($this->_fields[$key]['Type']) ) ){
					$this->_fields[$key]['widget']['type'] = 'static';
				} else if ( strtolower(substr($this->_fields[$key]['Type'],0, 4)) == 'time'){
					$this->_fields[$key]['widget']['type'] = 'time';
				} else if ( substr($this->_fields[$key]['Type'], 0,4) == 'enum' ){
					$this->_fields[$key]['widget']['type'] = 'select';
				}
			}
			if ( DATAFACE_EXTENSION_LOADED_APC ){
				apc_store($apc_key_fields, $this->_fields);
				apc_store($apc_key_keys, $this->_keys);
				apc_store($apc_key_mtime, time());
			}
		}
			
		$this->_loadFieldsIniFile();
		
		$parent =& $this->getParent();
		if ( isset($parent) ){
			foreach ( array_keys($this->keys()) as $currkey ){
				$this->_fields[$currkey]['widget']['type'] = 'hidden';
			}
		}
		
		$curr_order = 0;
		$needs_sort = false; // flag to indicate if any "order" attributes were set in the fields.ini file
		
		foreach (array_keys($this->_fields) as $field_name ){
			if ( isset($this->_fields[$field_name]['order']) ) {
				$needs_sort = true;
				$curr_order++;
			}
			else $this->_fields[$field_name]['order'] = $curr_order++;
		}
		
		//$this->_loadValuelistsIniFile();
			// lazily created now in valuelists() and getValuelist()
		//$this->_loadRelationshipsIniFile();
			// had to be removed to prevent possibility of infinite loops.  This is now called lazily as relationships 
			// are needed.
			
		//$GLOBALS['DATAFACE_QUERYBUILDER_SECURITY_CONSTRAINTS'][$this->tablename] = $this->_filters;
			
		
		// get some validation information to start with
		foreach ($fieldnames as $key){
			$row =& $this->_fields[$key];
		
			
			// handle case where this is an enumerated field
			$matches = array();
			if ( preg_match('/^(enum|set)\(([^\)]+)\)$/', $row['Type'], $matches )){

				$valuelists =& $this->valuelists();
				$options = explode(',', $matches[2]);
				
				$vocab = array();
				foreach ( $options as $val){
					$val = substr($val,1,strlen($val)-2); // strip off the quotes
					$vocab[$val] = $val;
				}
				
				$valuelists[$row['name']."_values"] =& $vocab;
				$row['vocabulary'] = $row['name']."_values";
				
				
				if ( strtolower($matches[1]) == 'set'){
					$row['repeat'] = true;
				} else {
					$row['repeat'] = false;
				}
				
				$row['widget']['type'] = 'select';
				
				$opt_keys = array_keys($vocab);
				
				$dummy = '';
				if ( $this->isYesNoValuelist($row['name']."_values", $dummy, $dummy) ){
					$widget['type'] = 'checkbox';
				}

				unset( $valuelists);
				unset( $vocab);
				
			}
	
			
			
			
			
			if ( 		!$this->isBlob($row['name']) 	and 
						!$this->isText($row['name']) 	and 
						!$this->isDate($row['name']) 	and
						!$this->isPassword($row['name']) and
						$row['Null'] != 'YES' 			and
						strlen($row['Default']) == 0	and
						$row['Extra'] != 'auto_increment' ) {
				$row['validators'][ 'required' ] = array('message' => $row['widget']['label'] ." is a required field.",
												  'arg' => '' );
			}
			
			
			// check for signs that this is a repeated field
			if ( $row['widget']['type'] == 'checkbox' and isset( $row['vocabulary'] ) and ( $this->isText($key) or $this->isChar($key) ) ){
				if ( isset( $row['repeat'] ) and !$row['repeat'] ){
					//do nothing
				} else {
					$row['repeat'] = true;
				}
			}
			if ( !isset($row['repeat']) ) $row['repeat'] = false;
			if ( $row['repeat'] and !isset( $row['separator'] )){
				$row['separator'] = "\n";
			}
			
			if ( !isset($row['display']) and $this->isText($row['name']) ) $row['display'] = 'block';
			else if ( !isset($row['display']) ) $row['display'] = 'inline';
				
			unset($row);
		}
		
		// check for obvious signs that this is a repeating field
		
		// sort the fields now based on their order attribute
		if ($needs_sort){
			uasort($this->_fields, array(&$this, '_compareFields'));
		}
		
		
	}
	
	/**
	 * To be called after initialization.
	 */
	function postInit(){
		// call init method of delegate
		$delegate =& $this->getDelegate();
		
		
		$parent =& $this->getParent();
		if ( isset($parent) ){
			$pdelegate =& $parent->getDelegate();
			if ( isset($pdelegate) and method_exists($pdelegate, 'init') ){
				$res = $pdelegate->init($this);
			}
		}
		
		if ( $delegate !== null and method_exists($delegate, 'init') ){
			$res = $delegate->init($this);
		}
		
		foreach ( array_keys($this->_fields) as $key){
			$this->_fields[$key]['widget']['description'] = $this->getFieldProperty('widget:description', $key);
			$this->_fields[$key]['widget']['label'] = $this->getFieldProperty('widget:label', $key);
			$this->_fields[$key]['vocabulary'] = $this->getFieldProperty('vocabulary', $key);
			$this->_fields[$key]['widget']['type'] = $this->getFieldProperty('widget:type', $key);
			$this->_fields[$key]['widget'] = array_merge($this->_fields[$key]['widget'], $this->getFieldProperty('widget', $key));
		}
		
		if ( count($this->_securityFilter) == 0 ){
			$this->setSecurityFilter();
		}

	}
	
	/**
	 * Compares two fields to see which one should come first in the sort order.
	 * If field $a should come first then -1 is returned.  If field $b should
	 * come first, then 1 is returned.  Otherwise 0 is returned.
	 */
	function _compareFields($a,$b){
		if ( @$a['order'] == @$b['order'] ) return 0;
		return ( @$a['order'] < @$b['order'] ) ? -1 : 1;
	}
	
	/**
	 * Indicates whether this table is declared as implementing the given
	 * ontology.
	 * @param string $ontologyName The name of the ontology.
	 * @returns boolean
	 */
	function implementsOntology($ontologyName){
		return (isset($this->_atts['__implements__']) and isset($this->_atts['__implements__'][$ontologyName]));
	}
	
	/**
	 * Method to be overridden by derived classes to indicate whether
	 * records of this table are read only.
	 */
	function readOnly(){
		return false;
	}
	
	
	/**
	 * Gets the parent table of this table to help model inheritance.  The 
	 * parent table can be specified by the __isa__ attribute of the fields.ini
	 * file.
	 * @returns Dataface_Table The parent table of this table.
	 */
	function &getParent(){
		if ( !isset($this->_parentTable) ){
			if ( isset($this->_atts['__isa__']) ){
				$this->_parentTable =& Dataface_Table::loadTable($this->_atts['__isa__']);
			}
		}
		return $this->_parentTable;
	}
	
	/**
	 * Returns an associative array of indexes in this table.
	 */
	function &getIndexes(){
		if ( !isset( $this->_indexes) ){
			$this->_indexes = array();
			$res = mysql_query("SHOW index FROM `".$this->tablename."`", $this->db);
			if ( !$res ){
				trigger_error("Failed to get index list due to a mysql error: ".mysql_error($this->db).Dataface_Error::printStackTrace(), E_USER_ERROR);
			}
			
			while ( $row = mysql_fetch_array($res) ){
				if ( !isset( $this->_indexes[ $row['Key_name'] ] ) )
					$this->_indexes[ $row['Key_name'] ] = array();
				$index =& $this->_indexes[$row['Key_name']];
				$index['name'] = $row['Key_name'];
				if ( !isset( $index['columns'] ) )
					$index['columns'] = array();
				$index['columns'][] = $row['Column_name'];
				$index['unique'] = ( $row['Non_unique'] ? false : true );
				$index['type'] = $row['Index_type'];
				$index['comment'] = $row['Comment'];
				unset($index);
			}
			mysql_free_result($res);
			
		}
		
		return $this->_indexes;
		
	
	}
	
	/**
	 * Returns an array of field names that have full text indexes.  A full-text
	 * index allows MySQL full-text searches to be performed on the contents of 
	 * those fields.  In MySQL 4+ full text searches may be performed on fields 
	 * without a fulltext index but this will be slow.
	 *
	 * @returns array(string) Names of fields with full text indexes.
	 */
	function getFullTextIndexedFields(){
		
		$indexes =& $this->getIndexes();
		$fields = array();
		foreach ( array_keys($indexes) as $indexName ){
			if ( strtolower($indexes[$indexName]['type']) === 'fulltext' ){
				foreach ( $indexes[$indexName]['columns'] as $col ){
					$fields[] = $col;
				}
			}
		}
		
		return $fields;
	}
	
	/**
	 * Returns array of names of char, varchar, and text fields.  These are the 
	 * fields that can be searched using full text searches.
	 *
	 * @returns array(string) Names of fields.
	 */
	function getCharFields($includeGraftedFields=false){
		if ( !isset($this->_cache[__FUNCTION__]) ){
			$out = array();
			foreach ( array_keys($this->fields(false, $includeGraftedFields)) as $field){
				if ( $this->isChar($field) or $this->isText($field) or (strtolower($this->getType($field)) == 'enum') ){
					$out[] = $field;
				}
			}
			$this->_cache[__FUNCTION__] = $out;
		}
		return $this->_cache[__FUNCTION__];
	
	}
	
	/**
	 * Indicates if the given field acts as a meta field that describes an aspect of another field.
	 * For example, some fields simply contain the mimetype of a blob field.  Such a field is a 
	 * meta field.
	 */
	function isMetaField($fieldname){

		$field =& $this->getField($fieldname);
		if ( !isset($field['metafield']) ){
			$fields =& $this->fields();
			$field_names = array_keys($fields);
			foreach ( $field_names as $fn){
				if ( ( isset($fields[$fn]['mimetype']) and $fields[$fn]['mimetype'] == $fieldname ) or 
				 	( isset($fields[$fn]['filename']) and $fields[$fn]['filename'] == $fieldname ) ) {
				 	$field['metafield'] = true;
				 	break;
				 }
			}
			if ( !isset($field['metafield']) ){
				$field['metafield'] = false;
			}
		}
		return $field['metafield'];

	
	}
	
	/**
	 * Returns the status of this table.  Includes things like modification time, etc...
	 */
	function &getStatus(){
		if ( !isset( $this->status ) ){
			/*
			 * Get the table status - when was it last updated, etc...
			 */
			$res = mysql_query("SHOW TABLE STATUS LIKE '".addslashes($this->tablename)."'",$this->db);
			if ( !$res ){
				trigger_error("Error performing mysql query to obtain status for table '".$this->tablename."': ".mysql_error($this->db).Dataface_Error::printStackTrace(), E_USER_ERROR);
			}
			
			$this->status = mysql_fetch_array($res);
			mysql_free_result($res);
		} 
		
		return $this->status;
		
	
	}
	
	function &getTableModificationTimes(){
		static $mod_times = 0;
		if ( $mod_times === 0 ){ 
			$mod_times = array();
		

			$res = mysql_query("show table status", df_db());
			if ( !$res ){
				trigger_error(mysql_error(df_db()));
			}
			
			while ( $row = mysql_fetch_assoc($res) ){
				$mod_times[$row['Name']] = @strtotime($row['Update_time']);
			}
		}
		return $mod_times;
	}
	
	/**
	 * Returns the update time as an SQL DateTime string for this table.
	 */
	function getUpdateTime(){
		$status =& $this->getStatus();
		return $status['Update_time'];
	}
	
	/**
	 * Returns the creation time of this table as an SQL DateTime string.
	 */
	function getCreateTime(){
		$status =& $this->getStatus();
		return $status['Create_time'];
	}
	

	
	function isYesNoValuelist($valuelist_name, &$yes, &$no){
	
		$options =& $this->getValuelist($valuelist_name);
		if ( !$options ) return false;
		
		if (count($options) != 2) return false;
		$opt_keys = array_keys($options);
		$yes_val = false;
		$no_val = false;
		foreach ($opt_keys as $opt_key){
			if ( stristr($opt_key,'n') == $opt_key ) $no_val = $opt_key;
			else if ( stristr($opt_key,'y') == $opt_key) $yes_val = $opt_key;
			else if ( $opt_key == "0" ) $no_val = $opt_key;
			else if ( $opt_key == "1" ) $yes_val = $opt_key;
			else if ( in_array(strtolower($opt_key), array('on','active','true','t') ) ){
				$yes_val = $opt_key;
			} else if ( in_array(strtolower($opt_key), array('off','inactive','false','f') ) ){
				$no_val = $opt_key;
			}
		}
		
		if ( $yes_val and $no_val ){
			$yes = $yes_val;
			$no = $no_val;
			return true;
		}
		return false;
	
	}
	
	
	
	
	/**
	 * Returns the path to the ini file containing field information.
	 */
	function _fieldsIniFilePath(){
		return $this->basePath().'/tables/'.$this->tablename.'/fields.ini';
	}
	
	/**
	 * Returns the path to the ini file containing relationship information.
	 */
	function _relationshipsIniFilePath(){
		return $this->basePath().'/tables/'.$this->tablename.'/relationships.ini';
	}
	
	/**
	 * Returns the path to the ini file containing valuelist information.
	 */
	function _valuelistsIniFilePath(){
		return $this->basePath().'/tables/'.$this->tablename.'/valuelists.ini';
	}
	
	/**
	 * Returns the path to the php delegate file (not used yet).
	 */
	function _delegateFilePath(){
		$path =$this->basePath().'/tables/'.$this->tablename.'/'.$this->tablename.'.php';
		
		return $path;
	}
	
	/**
	 * Boolean:  whether there exists a fields.ini file.
	 */
	function _hasFieldsIniFile(){
		
		return file_exists( $this->_fieldsIniFilePath() );
		
	}
	
	/**
	 * Boolean:  whether there exists a valuelists.ini file.
	 */
	function _hasValuelistsIniFile(){
		return file_exists( $this->_valuelistsIniFilePath() );
	}
	
	/**
	 * Boolean:  whether there exists a relationships.ini file.
	 */
	function _hasRelationshipsIniFile(){
		
		return file_exists( $this->_relationshipsIniFilePath() );
	}
	
	/**
	 * Boolean:  whether there exists a delegate file (not used yet).
	 */
	function _hasDelegateFile(){
		return file_exists( $this->_delegateFilePath() );
	}	
	
	
	/**
	 * Loads the delegate file.
	 */
	function _loadDelegate(){
		
		if ( $this->_hasDelegateFile() ){
			
			import( $this->_delegateFilePath() );
			$delegate_name = "tables_".$this->tablename;
			$this->_delegate =& new $delegate_name();
			
			if ( method_exists($this->_delegate, 'tablePermissions') ){
				// table permissions are now just done inside the getPermissions() method.
				// so the tablePermissions() method is no longer supported.  Let the developer
				// know in case he has old code.
				trigger_error(
					Dataface_LanguageTool::translate(
						'tablePermissions method no longer supported',
						'Dataface noticed that the delegate class for the table "'.$this->tablename.'" contains a tablePermissions() method.  This method is no longer supported as of Dataface version 0.6.  Please use the getPermissions() method instead with first parameter null to achieve the same results.
						For example:
						function getPermissions(&$record, $params){
							if ( $record === null ){
								// return generic table permissions
							} else {
								// return record-specific permissions
							}
						}',
						array('table'=>$this->tablename)
					), E_USER_NOTICE
				);
			}
			
			return true;
		} else {
			return false;
		}
	}
	

	
	/**
	 * Loads the valuelists from the ini file.
	 */
	function _loadValuelistsIniFile(){
		if ( !isset( $this->_valuelists ) ){
			$this->_valuelists = array();
		}
		$valuelists =& $this->_valuelists;
		
		import( 'Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$conf =& $configTool->loadConfig('valuelists', $this->tablename);
		
		
		foreach ( $conf as $vlname=>$vllist ){
			$valuelists[$vlname] = array();
			if ( is_array( $vllist ) ){
				foreach ( $vllist as $key=>$value ){
					if ( $key == '__import__' ){
						// we import the values from another value list.  The value of this
						// entry should be in the form tablename.valuelistname
						list( $ext_table, $ext_valuelist ) = explode('.', $value);
						if ( isset( $ext_table ) && isset( $ext_valuelist ) ){
							$ext_table =& Dataface_Table::loadTable($ext_table, $this->db);
						} else if ( isset( $ext_table ) ){
							$ext_valuelist = $ext_table;
							$ext_table =& $this;
						}
						
						if ( isset( $ext_table ) ){
							$ext_valuelist = $ext_table->getValuelist( $ext_valuelist );
							foreach ( $ext_valuelist as $ext_key=>$ext_value ){
								$valuelists[$vlname][$ext_key] = $ext_value;
							}
						}
						// clean up temp variables so they don't confuse us
						// in the next iteration.
						unset($ext_table);
						unset($ext_table);
						unset($ext_valuelist);
					} else if ( $key == '__sql__' ) {
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
							mysql_free_result($res);
						} else {
							trigger_error("Valuelist query '".$value."' failed. ".Dataface_Error::printStackTrace(), E_USER_NOTICE);
						}
					
					} else {
						$valuelists[$vlname][$key] = $value;
					}
				}
			}
			
			
		}
		
	}
		
	function _parseINISection($section, &$curr){
		
		foreach ($section as $valuekey=>$valuevalue){
			if ( strpos($valuekey,':') > 0 ){
				$path = explode(':', $valuekey);
				$temp =& $curr;
				for ( $i=0; $i<count($path); $i++){
					if ( $i<count($path)-1){
						if ( !isset($temp[$path[$i]])) $temp[$path[$i]] = array();
						$temp2 =& $temp[$path[$i]];
						unset($temp);
						$temp =& $temp2;
						unset($temp2);
					} else {
						$temp[$path[$i]] = $valuevalue;
					}
				}
			} else {
				$curr[$valuekey] = $valuevalue;
			}
		}
		
	
	}
	
	/**
	 * Load information about the fields in this table from the fields.ini file.
	 */
	function _loadFieldsIniFile(){

	
		import( 'Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$conf =& $configTool->loadConfig('fields', $this->tablename); //$temp['root'];
		
		$this->_global_field_properties = array();
		if ( isset($conf['__global__']) ) $this->_parseINISection($conf['__global__'], $this->_global_field_properties);
		else $this->_global_field_properties = array();
		
		foreach ($conf as $key=>$value ){
			if ( $key == '__sql__' and !is_array($value) ){
				$this->_sql = $value;
				continue;
			}
			
			/*
			 * Iterate through all of the fields.
			 */
			$matches = array(); // temp holder for preg matches
			if ( preg_match('/fieldgroup:(.+)/', $key, $matches) ){
				// This is a group description - not a field description
				$this->_fieldgroups[trim($matches[1])] = $value;
				$this->_fieldgroups[trim($matches[1])]['name'] = $matches[1];
				
				$grp =& $this->_fieldgroups[trim($matches[1])];
				foreach ($grp as $grpkey=>$grpval){
					$tmp = explode(':',$grpkey);
					switch (count($tmp)){
						case 2: $grp[$tmp[0]][$tmp[1]] =& $grp[$grpkey]; break;
						case 3: $grp[$tmp[0]][$tmp[1]][$tmp[2]] =& $grp[$grpkey]; break;
						case 4: $grp[$tmp[0]][$tmp[1]][$tmp[2]][$tmp[3]] =& $grp[$grpkey]; break;
						case 5: $grp[$tmp[0]][$tmp[1]][$tmp[2]][$tmp[3]][$tmp[4]] =& $grp[$grpkey]; break;

					}
				}
				
				if ( !isset( $grp['label'] ) ) $grp['label'] = ucfirst($grp['name']);
				if ( !isset( $grp['description']) ) $grp['description'] = '';
				if ( !isset( $grp['display']) ) $grp['display'] = "inline";
				if ( !isset( $grp['element_label_visible'])) {
					$grp['element_label_visible'] = true;
				}
				if ( !isset($grp['element_description_visible'])){
					$grp['element_description_visible'] = 
						($grp['display'] == 'inline' ? false : true);
					
				}
				
				// Now do the translation stuff
				$grp['label'] = df_translate('tables.'.$this->tablename.'.fieldgroups.'.$grp['name'].'.label', $grp['label']);
				$grp['description'] = df_translate('tables.'.$this->tablename.'.fieldgroups.'.$grp['name'].'.description', $grp['description']);
				if ( !isset($grp['order']) ) $grp['order'] = 0;
				unset($grp);
			} 
			
			else if ( preg_match('/tab:(.+)/', $key, $matches) ){
				// This is a tab description
				$tabname = trim($matches[1]);
				$this->_parseINISection($value, $this->_tabs[$tabname]);
				$tabarr =& $this->_tabs[$tabname];
				$tabarr['name'] = $tabname;
				
				if ( !isset($tabarr['label']) ) $tabarr['label'] = ucfirst($tabname);
				if ( !isset($tabarr['description']) ) $tabarr['description'] = '';
				
				unset($tabarr);
				
			
			}
			
			else if ( $key == "__filters__"){
				// THis is a filter to be added to queries of this table.
				$this->_filters=$value;
			
			}
			
			else if ($key == "__title__"){
				$this->_atts['title'] = $value;
			}
			
			else if ( $key == '__join__' ){
				$this->_joinTables = $value;
			
			}
			
			else if ( strpos($key, ':') > 0 ){
				// This is the definition of a subfield (ie: a field within a
				// table.
				list($parent, $child) = explode(':', $key);
				if ( !isset( $this->_fields[$parent] ) ){
					trigger_error("Error while loading definition for subfield '$key' from the fields.ini file for the table '".$this->tablename."'.  The field '$parent' does not exist.\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
				}
				
				$field =& $this->_fields[$parent];
				if ( !isset($field['fields']) ) $field['fields'] = array();
				$curr = $this->_newSchema('varchar(255)', $child);
				
				$this->_parseINISection($value, $curr);
				$field['fields'][$child] =& $curr;
				unset($curr);
				unset($field);
				
			
			}
			
			else if ( isset( $this->_fields[ $key ] ) ){
				
				$field =& $this->_fields[$key];
				$widget =& $field['widget'];
				$permissions =& $field['permissions'];
				$validators =& $field['validators'];
		
				
				// get the attributes defined in the ini file
				foreach ( $value as $att => $attval ){
					// some of the attributes will be prefixed to indicate
					// widget, vocabulary, etc...
					$attpath = explode( ":", $att );
					
					if ( count( $attpath ) == 1 ){
						// there was no prefix ... attribute goes straight into table
						if ( is_array($attval) ){
							$field[ $att ] = $attval;
						} else {
							$field[ $att ] = trim( $attval );
							if ( strcasecmp($att, 'key') === 0 and strcasecmp($attval, 'pri')===0 and !isset($this->_keys[$field['name']])){
								// This field is a surrogate primary key
								$this->_keys[$field['name']] =& $field;
								
							}
						}
						// todo handle validators which can be in the form of a list
						// **  ** **
					} else {
						// There was a prefix
						
						switch ( $attpath[0] ){
							case "widget":
								if ( count($attpath) > 2 ){
									$widget[ $attpath[1] ][ $attpath[2] ] = trim($attval);
								} else {
									$widget[ $attpath[1] ] = trim($attval);
								}
								
								break;
							case "permissions":
								$permissions[ $attpath[1] ] = trim($attval);
								break;
							case "validators":
								if ( !$attval || $attval == 'false' ) {
									if ( isset($validators[ $attpath[1] ]) ){
										unset($validators[ $attpath[1] ]);
									}
									continue;
								}
								if ( !isset( $validators[ $attpath[1] ] ) ){
									$validators[ $attpath[1] ] = array();
								}
								if ( count( $attpath ) <= 2 ){
									$validators[ $attpath[1] ]['arg'] = trim($attval);
								} else {
									$validators[ $attpath[1] ][ $attpath[2] ] = trim($attval);
								}
								if ( !isset( $validators[ $attpath[1] ]['message'] )){
									$validators[ $attpath[1] ]['message'] = "Illegal input for field ".$field['name'];
								}
								break;
								
							case "visibility":
								if ( !isset($field['visibility'] ) ) $field['visibility'] = array('list'=>'visible',
																								  'browse'=>'visible',
																								  'find'=>'visible');
								$field['visibility'][ $attpath[1] ] = trim($attval);
								
								break;
	
						}
						
						
					}
					
					
				}
				
				// prevent ourselves from doing damage
				unset($field);
				unset($widget);
				unset($permissions);
				unset($validators);
			} else {
				$this->_atts[$key] = $value;
			}
		}
		
		// Create missing fieldgroups
		// Explanation:  It is possible that fields reference
		// groups that don't have an explicit definition in the ini files.
		// This step creates those implicit groups.
		foreach (array_keys($this->_fields) as $key){
			if ( isset($this->_fields[$key]['group'])  ){
				$grpname = $this->_fields[$key]['group'];
				if ( !isset( $this->_fieldgroups[$grpname] ) ){
					$this->_fieldgroups[$grpname] = array(
						"name"=>$grpname, 
						"label"=>ucfirst($grpname), 
						"description"=>'',
						"display"=>'inline',
						"element_label_visible"=>true,
						"element_description_visible"=>false,
						'order'=>0
					);
				}
			}
			if ( strcasecmp($this->_fields[$key]['Type'], 'container') === 0){
				/*
				 * This field is a Container field.  We will need to set up the save path.
				 * If no save path is specified we will create a directory by the name
				 * of this field inside the table's directory.
				 */
				if ( $this->_fields[$key]['widget']['type'] == 'text' ) $this->_fields[$key]['widget']['type'] = 'file';
				if ( !isset( $this->_fields[$key]['savepath'] ) ){
					$this->_fields[$key]['savepath'] = DATAFACE_SITE_PATH.'/tables/'.$this->tablename.'/'.$key;
				} else if ( strpos($this->_fields[$key]['savepath'], '/') !== 0 and !preg_match('/^[a-z0-9]{1,5}:\/\//', $this->_fields[$key]['savepath']) ) {
					$this->_fields[$key]['savepath'] = DATAFACE_SITE_PATH.'/'.$this->_fields[$key]['savepath'];
				}
				
				if ( !isset($this->_fields[$key]['url']) ){
					$this->_fields[$key]['url'] = str_replace(DATAFACE_SITE_PATH, DATAFACE_SITE_URL, $this->_fields[$key]['savepath']);
					
				} else if ( strpos( $this->_fields[$key]['url'], '/') !== 0 and strpos($this->_fields[$key]['url'], 'http://') !== 0 ){
					$this->_fields[$key]['url'] = DATAFACE_SITE_URL.'/'.$this->_fields[$key]['url'];
				}
			}
			
			
			if ( !isset($this->_fields[$key]['tab']) ) $this->_fields[$key]['tab'] = '__main__';
			$tab = $this->_fields[$key]['tab'];	
			if ( !isset($this->_tabs[$tab]) ){

				$this->_tabs[$tab] = array('name'=>$tab, 'label'=>ucfirst($tab), 'description'=>'');
				
			}
		
			
			if ( $this->_fields[$key]['widget']['type'] == 'checkbox' and ($this->isText($key) || $this->isChar($key)) and @$this->_fields[$key]['vocabulary'] ){
				// This is a checkbox field with a vocabulary.  It should be a repeating field
				$this->_fields[$key]['repeat'] = true;
			}
			
			$widget =& $this->_fields[$key]['widget'];
			switch ($widget['type']){
				case 'text':
					if ( !isset($widget['atts']['size']) ){
						if ( $this->isInt($key) or $this->isFloat($key) ){
							$widget['atts']['size'] = 10;
						} else {
							$widget['atts']['size'] = 30;
						}
					}
					break;
				case 'textarea':
					if (!isset($widget['atts']['rows']) ){
						$widget['atts']['rows'] = 6;
					}
					if ( !isset($widget['atts']['cols']) ){
						$widget['atts']['cols'] = 60;
					}
					break;
			}
			
			// Now we do the translation stuff
			$widget['label'] = df_translate('tables.'.$this->tablename.'.fields.'.$key.'.widget.label',$widget['label']);
			$widget['description'] = df_translate('tables.'.$this->tablename.'.fields.'.$key.'.widget.description',$widget['description']);
			if ( isset($widget['question']) ){
				$widget['question'] = df_translate('tables.'.$this->tablename.'.fields.'.$key.'.widget.question',$widget['question']);
			
			}
			$this->_fields[ $key ] = array_merge_recursive_unique($this->_global_field_properties, $this->_fields[$key]);

			unset($widget);
		}
		
	}
	
	
	/**
	 * Load the relationship information about the table from the tables/<table_name>/relationships.ini file.
	 * <b>Note:</b> To prevent deadlocks, this file should never be called from the Dataface_Table constructor.
	 * Two tables could name each other in relations and attempt to load each others' tables before either
	 * one is finished loading.
	 */
	function _loadRelationshipsIniFile(){
		
	
		import( 'Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$conf =& $configTool->loadConfig('relationships', $this->tablename); 
		
		$r =& $this->_relationships;
		foreach ($conf as $rel_name => $rel_values){
			// Case 1: The we have an array of values - meaning that this is the definition of a relationship.
			// Right now there is only one case, but we could have cases with single entries also.
			if ( is_array( $rel_values ) ){
				
				$r[$rel_name] =& new Dataface_Relationship($this->tablename, $rel_name, $rel_values);
				
			}
		}
		
		$parent =& $this->getParent();
		if ( isset($parent) ){
			$this->_relationships = array_merge($parent->relationships(), $this->_relationships);
		}
		$this->_relationshipsLoaded = true;	
	
	}
	
	/**
	 * Sets a security filter on the table.  A security filter is an array of
	 * key/value pairs that are automatically added to any query of this table
	 * to limit the results.
	 *
	 * @example
	 * <code>
	 * $table->setSecurityFilter(array('schoolID'=>10));
	 *		// Will only show results where schoolID is 10
	 * </code>
	 */
	function setSecurityFilter($filter=null){
		
		if ( !isset($filter)){
			$filter = array();
			$app =& Dataface_Application::getInstance();
			$query =& $app->getQuery();
			if ( class_exists('Dataface_AuthenticationTool') ){
				$auth =& Dataface_AuthenticationTool::getInstance();
				$user =& $auth->getLoggedInUser();
			} else {
				$auth = null;
				$user = null;
			}

			foreach ($this->_filters as $key=>$value){
				if ( isset($this->_securityFilter[$key]) ) continue;
				if ( $value{0} == '$' ){
					if ( !$user and strpos($value, '$user') !== false ) continue;
					eval('$filter[$key] = "=".'.$value.';');
				} else if ( substr($value,0,4) == 'php:' ){
					if ( !$user and strpos($value, '$user') !== false ) continue;
					eval('$filter[$key] = "=".'.substr($value,4).';');
				} else {
					$filter[$key] = "=".$value;
				}
			}
		
		} 
		
		$this->_securityFilter = $filter;
	}
	
	function getSecurityFilter($filter=array()){
		return array_merge($this->_securityFilter, $filter);
	}
	
	
	
	/**
	 * Returns associative array of translations where the key is the 2-digit
	 * language code and the value is an array of column names in the translation.
	 */
	function &getTranslations(){
		if ( $this->translations === null ){
			$this->translations = array();
			$res = mysql_query("SHOW TABLES LIKE '".addslashes($this->tablename)."%'", $this->db);
			if ( !$res ){
				
				trigger_error(
					Dataface_LanguageTool::translate(
						'MySQL query error loading translation tables',
						'MySQL query error while trying to find translation tables for table "'.addslashes($this->tablename).'". '.mysql_error($this->db).'. '.Dataface_Error::printStackTrace(),
						array('sql_error'=>mysql_error($this->db), 'stack_trace'=>Dataface_Error::printStackTrace(), 'table'=>$this->tablename)
					),
					E_USER_ERROR 
				
				);
			}
			if (mysql_num_rows($res) <= 0 ){
				// there should at least be the current table returned.. there is a problem
				// if nothing was returned.
				trigger_error(
					Dataface_LanguageTool::translate(
						'Not enough results returned loading translation tables',
						'No tables were returned when trying to load translation tables for table "'.$this->tablename.'".  This query should have at least returned one record (the current table) so there must be a problem with the query.'.Dataface_Error::printStackTrace(),
						array('table'=>$this->tablename)
					),
					E_USER_ERROR
				);
			}
			
			while ( $row = mysql_fetch_array($res ) ){
				$tablename = $row[0];
				if ( $tablename == $this->tablename ){
					continue;
				}
				
				$matches = array();
				if ( preg_match( '/^'.$this->tablename.'_([a-zA-Z]{2})$/', $tablename, $matches) ){
					$this->translations[$matches[1]] = 0;
				}
				
			}
			mysql_free_result($res);
					
			
		}
		return $this->translations;
	
	}
	
	/**
	 * Returns an array of column names that are available in a given language.
	 * @param $name The 2-digit language code for the translation.
	 * @returns Array of column names - if translation exists.  Null if translation
	 *          does not exist.
	 */
	function &getTranslation($name){
		$translations =& $this->getTranslations();
		if ( isset( $translations[$name] )){
			// the translation exists
			if ( !$translations[$name]  ){
				// the columns are not loaded yet, we need to load them.
				$res = mysql_query("SHOW COLUMNS FROM `".addslashes($this->tablename)."_".addslashes($name)."`", $this->db);
				if ( !$res ){
					trigger_error(
						Dataface_LanguageTool::translate(
							'Problem loading columns from translation table',
							'Problem loading columns from translation table for table "'.$this->tablename.'" in language "'.$name.'". '.mysql_error($this->db).Dataface_Error::printStackTrace(),
							array('table'=>$this->tablename,'langauge'=>$name,'stack_trace'=>Dataface_Error::printStackTrace(),'sql_error'=>mysql_error($this->db))
						),
						E_USER_ERROR
					);
				}
				$translations[$name] = array();
				while ( $row = mysql_fetch_assoc($res) ){
					$translations[$name][] = $row['Field'];
				}
				mysql_free_result($res);
			}

			return $translations[$name];
		}
		$null = null;
		return $null;
	}
	
	function getTranslationStates(){
		return array(
			'o-a-c'=>'Original translation',
			'm-u-c'=>'Machine translation - unapproved',
			'm-a-c'=>'Machine translation - approved',
			'm-u-o'=>'Machine translation - unapproved - out of date',
			'm-a-o'=>'Machine translation - approved - out of date',
			'h-u-c'=>'Human translation - unapproved',
			'h-a-c'=>'Human translation - approved',
			'h-u-o'=>'Human translation - unapproved - out of date',
			'h-a-o'=>'Human translation - approved - out of date'
			);
	
	}
	
	
	/**
	 * Returns an array of names of columns in the metadata table for this
	 * table.  The metadata table contains metadata such as state and 
	 * translation state for the corresponding records of this table.
	 * Fields of this table that are considered to be metadata must begin
	 * with two underscores to signify that they are metadata.
	 *
	 * If no metadata table yet exists, it will be created.
	 * @returns array List of column names.
	 */
	function getMetadataColumns(){
		if ( !isset($this->metadataColumns) ){
			$metatablename = $this->tablename.'__metadata';
			$sql = "SHOW COLUMNS FROM `{$metatablename}`";
			$res = mysql_query($sql, $this->db);
			if ( !$res || mysql_num_rows($res) == 0){
				Dataface_MetadataTool::refreshMetadataTable($this->tablename);
				$res = mysql_query($sql, $this->db);
			}
			if ( !$res ) trigger_error(mysql_error($this->db), E_USER_ERROR);
			if ( mysql_num_rows($res) == 0 ) trigger_error("No metadata table set up for table '{$this->tablename}'", E_USER_ERROR);
			$this->metadataColumns = array();
			while ($row = mysql_fetch_assoc($res) ){
				if ( substr($row['Field'],0,2) == '__' ){
					$this->metadataColumns[] =  $row['Field'];
				}
			}
			
		}
		return $this->metadataColumns;
	}
	
	
	
	/**
	 * Returns the actions for this table.
	 * @param $params An associative array of options.  Possible keys include:
	 *		record => reference to a Dataface_Record or Dataface_RelatedRecord object
	 *		relationship => The name of a relationship.
	 *		category => A name of a category for the actions to be returned.
	 */
	function getActions(&$params,$noreturn=false){
		import( 'Dataface/ActionTool.php');
		$actionsTool =& Dataface_ActionTool::getInstance();
		if ( !$this->_actionsLoaded  ){
			import( 'Dataface/ConfigTool.php');
			$configTool =& Dataface_ConfigTool::getInstance();
			$actions =& $configTool->loadConfig('actions',$this->tablename);
			foreach ($actions as $key=>$action){
				$action['table'] = $this->tablename;
				$action['name'] = $key;
				if ( !isset($action['id']) ) $action['id'] = $action['name'];
				if ( !isset($action['label']) ) $action['label'] = str_replace('_',' ',ucfirst($action['name']));
				if ( !isset($action['accessKey'])) $action['accessKey'] = substr($action['name'],0,1);
				if ( !isset($action['label_i18n']) ) $action['label_i18n'] = 'action:'.$action['name'].' label';
				if ( !isset($action['description_i18n'])) $action['description_i18n'] = 'action:'.$action['name'].' description';
				
				$actionsTool->addAction($key, $action);
			}
			
			
		}
		
		$params['table'] = $this->tablename;
		if ( $noreturn ) return true;
		return $actionsTool->getActions($params);
			
	}
	
	/**
	 * Returns the default value for a field.
	 */
	function getDefaultValue($fieldname){
		$field =& $this->getField($fieldname);
		if ( @$field['tablename'] and $field['tablename'] != $this->tablename ){
			// Some fields may have been taken from another table.
			$table =& Dataface_Table::loadTable($field['tablename']);
			return $table->getDefaultValue($fieldname);
		}
		$delegate =& $this->getDelegate();
		if ( isset($delegate) and method_exists($delegate, $fieldname.'__default') ){
			return call_user_func(array(&$delegate, $fieldname.'__default'));
		} else if ( $field['Default'] ){
			return $field['Default'];
		} else {
			return null;
		}
	}
	
	/**
	 * Adds a relationship to the table.  
	 * @param $name The name of the relationship
	 * @type string
	 *
	 * @param array $relationship Associative array of options.  Keys are the same as expected keys in the 
	 * 						relationships.ini file.
	 */
	function addRelationship($name, $relationship){
		if ( !is_array($relationship) ){
			trigger_error("In Dataface_Table::addRelationship() 2nd argument expected to be an array, but received ".get_class($relationship).Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$this->_relationships[$name] =& new Dataface_Relationship($this->tablename, $name, $relationship);
		
	}
	
	/**
	 * Gets the relationship that should be used as the parent relationship.
	 * This is useful for representing heirarchical structures.
	 *
	 * @return Dataface_Relationship The relationship to be used as the parent relationship.
	 */
	function &getParentRelationship(){
		$r =& $this->relationships();
		if ( array_key_exists(__FUNCTION__,$this->_cache) ) return $this->_cache[__FUNCTION__];
		foreach ( array_keys($r) as $name ){
			if ( $this->_relationships[$name]->isParentRelationship() ){
				$this->_cache[__FUNCTION__] =& $r[$name];
				return $r[$name];
			}
		}
		$null = null;
		return $null;
	}
	
	/**
	 * Gets the relationship that should be used as the children relationship.
	 * This is useful for representing heirarchical structures.
	 * @return Dataface_Relationship The relationship used as the children relationship.
	 */
	function &getChildrenRelationship(){
		$r =& $this->relationships();
		if ( array_key_exists(__FUNCTION__,$this->_cache) ) return $this->_cache[__FUNCTION__];
		foreach ( array_keys($r) as $name ){
			if ( $r[$name]->isChildrenRelationship() ){
				$this->_cache[__FUNCTION__] =& $r[$name];
				return $r[$name];
			}
		}
		$null = null;
		return $null;
	}
	
	function getRelationshipRange($relationshipName){
		if ( isset( $this->_relationshipRanges[$relationshipName] ) ){
			return $this->_relationshipRanges[$relationshipName];
		}  else {
			return $this->_defaultRelationshipRange;
		}
	}
	
	function setRelationshipRange($relationshipName, $lower, $upper){
		if ( !isset( $this->_relationshipRanges ) ) $this->_relationshipRanges = array();
		$this->_relationshipRanges[$relationshipName] = array($lower, $upper);
		
	}
	
	function getDefaultRelationshipRange(){
		return $this->_defaultRelationshipRange;
	}
	
	function setDefaultRelationshipRange($lower, $upper){
		$this->_defaultRelationshipRange = array($lower, $upper);
	}
	
	
	function &getFieldsConfig(){
		return $this->_fieldsConfig;
	}
	
	function &getRelationshipsConfig(){
		return $this->_relationshipsConfig;
	}
	
	function &getValuelistsConfig(){
		return $this->_valuelistsConfig;
	}
	
	/**
	 * Returns the base url of the system.  Can be set in the $GLOBALS['Dataface_Globals_BaseUrl'] variable.
	 */
	function baseUrl(){
		return DATAFACE_SITE_URL;
		//return $GLOBALS['Dataface_Globals_BaseUrl'];
	
	}
	
	/**
	 * Returns the base path to the system. Can be set in the $GLOBALS['Dataface_Globals_BasePath'] variable.
	 */
	function basePath(){
		
		return DATAFACE_SITE_PATH;
		//return realpath($GLOBALS['Dataface_Globals_BasePath']);
	}
	
	function getLabel(){
		return $this->_atts['label'];
	}

	/**
	 * @deprecated.
	 */
	function getIniFile(){
		return $this->_iniFile;
	}
	
	/**
	 * @deprecated
	 */
	function setIniFile($file){
		$this->_iniFile = $file;
	}
	
	/**
	 * Returns the fields that should be included in forms for editing.
	 * This includes fields from parent tables.
	 * @returns array
	 */
	function &formFields($byTab=false, $includeTransient=false){
		if ( !isset($this->_cache[__FUNCTION__][intval($byTab)][intval($includeTransient)]) ){
			$fields = $this->fields($byTab,false,$includeTransient);
			$parent =& $this->getParent();
			if ( isset($parent) ){
				
				$fields = array_merge_recursive_unique($parent->fields($byTab,false,$includeTransient), $fields);
				uasort($fields, array(&$this, '_compareFields'));
			}
			
			$this->_cache[__FUNCTION__][intval($byTab)][intval($includeTransient)] =& $fields;
		}
		return $this->_cache[__FUNCTION__][intval($byTab)][intval($includeTransient)];
	}

	/**
	 * Returns reference to the fields array that contains field definitions.
	 */
	function &fields($byTab=false, $includeGrafted=false, $includeTransient=false){
		if ( !$byTab) {
			//if ( $includeGrafted or $includeTransient){
			if ( !isset($this->_cache[__FUNCTION__][intval($includeGrafted)][intval($includeTransient)]) ){
				//return $this->_cache[intval($includeGrafted)][intval($includeTransient)];
				
				$fields = array();
				
				if ( $includeGrafted ){
					$grafted_fields =& $this->graftedFields();
					foreach (array_keys($grafted_fields) as $fname){
						$fields[$fname] =& $grafted_fields[$fname];
					}
				}
				
				if ( $includeTransient ){
					$transient_fields =& $this->transientFields();
					foreach ( array_keys($transient_fields) as $fname){
						if ( !isset($fields[$fname]) ) $fields[$fname] =& $transient_fields[$fname];
						
					}
				}
				
				if ( count($fields) > 0 ){
					$fields = array_merge_recursive_unique($this->_fields, $fields);
					uasort($fields, array(&$this, '_compareFields'));
					$this->_cache[__FUNCTION__][intval($includeGrafted)][intval($includeTransient)] =& $fields;
				
				} else {
					$this->_cache[__FUNCTION__][intval($includeGrafted)][intval($includeTransient)] =& $this->_fields;
				}
			}
			
			return $this->_cache[__FUNCTION__][intval($includeGrafted)][intval($includeTransient)];
			
		}
		else {
			if ( !isset( $this->_fieldsByTab ) ){
				$this->_fieldsByTab = array();
				
				foreach ( $this->fields(false,$includeGrafted, $includeTransient) as $field){
				
					$tab = ( isset( $field['tab'] ) ? $field['tab'] : '__default__');
					
					if ( !isset( $this->_fieldsByTab[ $tab] ) ){
						$this->_fieldsByTab[ $tab ] = array();
					}
					$this->_fieldsByTab[ $tab ][$field['name']] = $field;
				
				}
			}
			return $this->_fieldsByTab;
			
		}
	}
	
	/**
	 * Field definitions of fields that have been grafted onto this table.
	 * It is possible to provide an '__sql__' parameter to the fields.ini
	 * file that will provide a custom query to be performed.  This is really
	 * only meant for grafting extra columns onto the table that weren't there
	 * before.  These columns will appear in list and view mode, but you won't 
	 * be able to edit them.
	 * @returns array Array of field definitions of grafted fields.  Each field 
	 * 	def is of the form:
	 *			array('Field'=>fieldname, 'Type'=>varchar(32), 'widget'=>array('label'=>.., 'description'=>..))
	 */
	function &graftedFields($includeParent=true){
		$tsql = $this->sql();
		if ( $includeParent ) $includeParent = 1;
		else $includeParent = 0;
		
		if ( !isset($this->_cache[__FUNCTION__][intval($includeParent)]) ){
		//if ( !isset($this->_grafted_fields) ){
			
			$this->_grafted_fields = array();
			if (isset($tsql)){
			
				$this->_grafted_fields = array();
				import('SQL/Parser.php');
				$parser =& new SQL_Parser(null,'MySQL');
				$data = $parser->parse($tsql);
				foreach ( $data['columns'] as $col ){
					if ( $col['type'] != 'glob' ){
						$alias = ( $col['alias'] ? $col['alias'] : $col['value']);
						$this->_grafted_fields[$alias] = $this->_newSchema('varchar(32)', $alias);
						if ( isset($this->_atts[$alias]) and is_array($this->_atts[$alias]) ){
							
							$this->_parseINISection($this->_atts[$alias], $this->_grafted_fields[$alias]);
						}
						//array('Field'=>$alias, 'name'=>$alias, 'Type'=>'varchar(32)', 'widget'=>array('label'=>$alias, 'description'=>''));
						
					}
				}
			}
			if ( $includeParent ){
				// We now want to load the parent table columns as well.
				$parent =& $this->getParent();
				if ( isset($parent) ){
					$this->_grafted_fields = array_merge( $parent->fields(false,true), $this->_grafted_fields);
				}
			}
			$this->_cache[__FUNCTION__][intval($includeParent)] = $this->_grafted_fields;
			
		}
		
		return $this->_cache[__FUNCTION__][intval($includeParent)];
	}
	
	/**
	 * Returns the transient fields in this table.  Transient fields are fields
	 * that do not get saved in the database (i.e. have no corresponding field
	 * in the database.  They are useful for creating fields on the new/edit
	 * forms.
	 */
	function &transientFields($includeParent=false){
		if ( !isset($this->_cache[__FUNCTION__][intval($includeParent)]) ){
		//if ( !isset($this->_transient_fields) ){
			$this->_transient_fields = array();
			foreach ( $this->_atts as $fieldname=>$field ){
				if ( !is_array($field) ) continue;
				if ( @$field['transient'] ){
					$curr = array();
					$this->_parseINISection($field, $curr);

					$curr = array_merge_recursive_unique($this->_global_field_properties, $curr);
					$schema = $this->_newSchema('text',$fieldname);

					$curr = array_merge_recursive_unique($schema, $curr);
					$this->_transient_fields[$fieldname] = $curr;
				}
			}
			if ( $includeParent){
				$parent =& $this->getParent();
				if ( isset($parent) ){
					$this->_transient_fields = array_merge( $parent->transientFields(), $this->_transient_fields);
				}
			}
			$this->_cache[__FUNCTION__][intval($includeParent)] = $this->_transient_fields;
			
		
		}
		//return $this->_transient_fields;
		return $this->_cache[__FUNCTION__][intval($includeParent)];
	}
	
	function &delegateFields($includeParent=false){
		if ( !isset($this->_cache[__FUNCTION__][intval($includeParent)]) ){
		//if ( !isset($this->_transient_fields) ){
			$fields = array();
			
			$del =& $this->getDelegate();
			if ( isset($del) ){
				$delegate_methods = get_class_methods(get_class($del));
				
				$delegate_fields = preg_grep('/^field__/', $delegate_methods);
				
				foreach ($delegate_fields as $dfield){
					$dfieldname = substr($dfield,7);
					$fields[$dfieldname] = $this->_newSchema('varchar(32)', $dfieldname);
					$fields[$dfieldname]['visibility']['browse'] = 'hidden';
					if ( isset($this->_atts[$dfieldname]) and 
						 is_array($this->_atts[$dfieldname]) ){
						$this->_parseINISection($this->_atts[$dfieldname], $fields[$dfieldname]);
						
					}
					
					
				}
				
				
				if ( $includeParent){
					$parent =& $this->getParent();
					if ( isset($parent) ){
						$fields = array_merge( $parent->delegateFields(), $fields);
					}
				}
			}
			$this->_cache[__FUNCTION__][intval($includeParent)] = $fields;
			
		
		}
		//return $this->_transient_fields;
		return $this->_cache[__FUNCTION__][intval($includeParent)];
		
	}
	
	function sql(){
		$del =& $this->getDelegate();
		if ( isset($del) and method_exists($del,'__sql__') ){
			return $del->__sql__();
		} else if ( isset($this->_sql) ){
			return $this->_sql;
		} else {
			return null;
		}
	
	}
	
	/**
	 * Returns an array of strings that are the names of the tabs for this table.
	 * Fields can be grouped into tabs by specifying a 'tab' attribute in the
	 * fields.ini file.  This is just a list of the tabs that are specified.
	 *
	 * @deprecated This method should no longer be used.  Rather, you should use
	 * array_keys($this->tabs());
	 */
	function getTabs(){
		return array_keys($this->tabs());
	}
	
	/**
	 * Returns an associative array of tab definitions of the form:
	 * [tabname] -> [tab_properties]
	 *
	 * This merges together any join tables that haven't been defined as a tab
	 * explicitly also.
	 *
	 * @param Dataface_Record &$record A record to provide context.  This will
	 * 		allow us to return different tabs for different records via the 
	 *		__tabs__ method of the delegate class.
	 *
	 * @returns array
	 */
	function &tabs(&$record){
		
		$del =& $this->getDelegate();
		if ( isset($del) and method_exists($del, '__tabs__') ){
			$tabs = $del->__tabs__($record);
			return $tabs;
		} else {
			
			$tabs = $this->_tabs;

			foreach ( $this->__join__($record) as $tablename=>$tablelabel ){
				if ( !isset($tabs[$tablename]) ){
					$tabs[$tablename] = array('name'=>$tablename, 'label'=>$tablelabel, 'description'=>'');
				}
			}
			return $tabs;
		}
	}
	
	
	
	/**
	 * Returns an array of tables for a particular record that can be treated
	 * as join tables.
	 */
	function __join__(&$record){
		$del =& $this->getDelegate();
		if ( isset($del) and method_exists($del, '__join__') ){
			return $del->__join__($record);
		} else if ( isset($this->_joinTables) ){
			return $this->_joinTables;
		} else {
			return array();
		}
	}
	
	/**
	 * Indicates whether or not this table has a join table.
	 */
	function hasJoinTable($tablename, &$record){
		return array_key_exists($tablename,$this->__join__($record));
	}
	
	/**
	 * Returns reference to the keys array that contains field definitions for keys.
	 */
	function &keys(){
		return $this->_keys;
	}
	
	/**
	 * A list of the keys that are NOT auto incremented.
	 */
	function &mandatoryFields(){
		$fields = array();
		foreach ( array_keys($this->keys()) as $key){
			if ( $this->_fields[$key]['Extra'] == 'auto_increment') continue;
			$fields[ $key ] =& $this->_fields[$key];
		}
		
		return $fields;
		
	
	}
	
	
	/**
	 * Loads the table for a table.
	 * This is the prefered way to create a new table.  If the table already exists for 
	 * the specified table, then a reference to that table is returned.  If it does not
	 * exist, then the table is created, and a reference to it is returned.
	 * @param name The name of the table for which the table should be returned.
	 * @param db A db connection handle.
	 * @param getAll If true then this will return an array of all of the tables loaded.
	 */
	function &loadTable($name, $db=null, $getAll=false, $quiet=false){
		if ( !is_string($name) ){
			trigger_error("In Dataface_Table::loadTable() expected first argument to be a string but received '".get_class($name)."'".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		if ( $db === null and defined('DATAFACE_DB_HANDLE')) $db = DATAFACE_DB_HANDLE;
		if ( !isset( $_tables ) ){
			static $_tables = array();
			
			static $_db = '';
		}
		if ( $getAll ){
			return $_tables;
		
		}
		if ( $db ) $_db = $db;
		if ( !isset( $_tables[$name] ) ){
			$app =& Dataface_Application::getInstance();
			$_tables[$name] =& new Dataface_Table($name, $_db, $quiet);
			
			
			$_tables[$name]->postInit();
		}
		
		return $_tables[$name];
	
	}

	/**
	 * Builds a new empty schema for a field.  Note that this method should be 
	 * callable in static context.  Hence it should not require the use of $this.
	 */
	function _newSchema($type, $fieldname, $tablename=null, $permissions = null){
		/*
				 Example row as follows:
				 Array
				(
					[Field] => id
					[Type] => int(7)
					[Null] =>  
					[Key] => PRI
					[Default] =>
					[Extra] => auto_increment
		)
		*/
		if ( !isset($tablename) ) $tablename = $this->tablename;
		if ( !isset($permissions) and is_a($this, 'Dataface_Table') ){
			$permissions = Dataface_PermissionsTool::getRolePermissions($this->app->_conf['default_field_role']);
		} else if ( !isset($permissions) ){
			$permissions = Dataface_PermissionsTool::READ_ONLY();
		}
		
		$schema = array("Field"=>$fieldname, "Type"=>$type, "Null"=>'', "Key"=>'', "Default"=>'', "Extra"=>'');
		$schema = array_merge_recursive_unique($this->_global_field_properties, $schema);
		$widget = array();
		$widget['label'] = ucfirst($schema['Field']);
		$widget['description'] = '';
		$widget['label_i18n'] = $tablename.'.'.$fieldname.'.label';
		$widget['description_i18n'] = $tablename.'.'.$fieldname.'.description';
		$widget['macro'] = '';
		$widget['helper_css'] = '';
		$widget['helper_js'] = '';
		$widget['class'] = '';
		$widget['type'] = 'text';
		$widget['atts'] = array();	//html attributes
		if ( ereg( 'text', $schema['Type']) ){
			$widget['type'] = 'textarea';
		} else if  ( ereg( 'blob', $schema['Type']) ){
			$widget['type'] = 'file';
		}
		$schema['widget'] =& $widget;
		$schema['tab'] = '__main__';
		
		$schema['tablename'] = $tablename;
		$schema['tableta'] = 'default';
		$schema['vocabulary'] = '';
		$schema['enforceVocabulary'] = false;
		$schema['validators'] = array();
		$schema['name'] = $schema['Field'];
		$schema['permissions'] = $permissions;
		$schema['repeat'] = false;
		$schema['visibility'] = array('list'=>'visible', 'browse'=>'visible', 'find'=>'visible');
		
		
		return $schema;
	}
	

	function getRelationshipPermissions($relationshipName, $params=array()){
	
		$params['relationship'] = $relationshipName;
		return $this->getPermissions($params);
	}
	
	/**
	 * Obtains the permissions for a particular record or for this table.
	 * @param Dataface_Record record
	 * @param string field
	 * @param string relationship
	 * @param boolean nobubble
	 * @param array recordmask
	 */
	function getPermissions($params=array()){
	
		// First let's try to load permissions from the cache
		$pt =& Dataface_PermissionsTool::getInstance();
		$params['table'] = $this->tablename;
		if ( isset($params['record']) ) $record =& $params['record'];
		else $record = null;
		$cachedPermissions = $pt->getCachedPermissions($record, $params);
		if ( isset($cachedPermissions) ) return $cachedPermissions;
		
		
		$delegate =& $this->getDelegate();
		$app =& Dataface_Application::getInstance();
		$appDelegate =& $app->getDelegate();
		$parent =& $this->getParent();
		
		$methods = array();
		if ( isset($params['field']) ){
			$methods[] = array('object'=>&$delegate, 'name'=>$params['field'].'__permissions', 'type'=>'permissions');
			$methods[] = array('object'=>&$delegate, 'name'=>$params['field'].'__roles', 'type'=>'roles');
			$methods[] = array('object'=>&$delegate, 'name'=>'__field__permissions', 'type'=>'permissions');
			$methods[] = array('object'=>&$delegate, 'name'=>'__field__roles', 'type'=>'roles');
			if ( isset($parent) ) $methods[] = array('object'=>&$parent, 'name'=>'getPermissions', 'type'=>'Dataface_Table');
			if ( @$params['nobubble'] ) $methods[] = 'break';

		} else if ( isset($params['relationship']) ){
			if ( isset($params['relationshipmask']) ) $rmask =& $params['relationshipmask'];
			else $rmask = array();
			$methods[] = array('object'=>&$delegate, 'name'=>'rel_'.$params['relationship'].'__permissions', 'type'=>'permissions', 'mask'=>&$rmask);
			$methods[] = array('object'=>&$delegate, 'name'=>'rel_'.$params['relationship'].'__roles', 'type'=>'roles', 'mask'=>&$rmask);
			if ( isset($parent) ) $methods[] = array('object'=>&$parent, 'name'=>'getPermissions', 'type'=>'Dataface_Table');
			if ( @$params['nobubble'] ) $methods[] = 'break';
		}
		if ( isset($params['recordmask']) ) $mask =& $params['recordmask'];
		else $mask = array();
		$methods[] = array('object'=>&$delegate, 'name'=>'getPermissions', 'type'=>'permissions','mask'=>&$mask);
		$methods[] = array('object'=>&$delegate, 'name'=>'getRoles', 'type'=>'roles');
		$methods[] = array('object'=>&$appDelegate, 'name'=>'getPermissions', 'type'=>'permissions', 'mask'=>&$mask);
		$methods[] = array('object'=>&$appDelegate, 'name'=>'getRoles', 'type'=>'roles', 'mask'=>&$mask);
		if ( isset($parent) ) $methods[] = array('object'=>&$parent, 'name'=>'getPermissions', 'type'=>'Dataface_Table');
	
		
		foreach ($methods as $method){
			if ( $method == 'break' ) return null;
			
			if ( isset($method['object']) and method_exists($method['object'], $method['name']) ){
				$name = $method['name'];
				if ( $method['type'] == 'Dataface_Table'){
					$res = $method['object']->$name(array_merge($params, array('nobubble'=>1)));
				} else {
					$res = $method['object']->$name($record, $params);
				}
				if ( $method['type'] == 'roles' ){
					$res = $this->convertRolesToPermissions($res);
				}
				if ( is_array($res) ){
					if ( @$method['mask'] and is_array(@$method['mask']) ) $res = array_merge($method['mask'], $res);
					$pt->cachePermissions($record, $params, $res);
					return $res;
				}
			}
		}
		$res = Dataface_PermissionsTool::ALL();
		$pt->cachePermissions($record,$params,$res);
		return $res;
	}
	
	
	
	function convertRolesToPermissions($roles){
		if ( is_array($roles) ){
			$perms = array();
			foreach ($roles as $role){
				if ( is_string($role) ){
					$perms = array_merge($perms, Dataface_PermissionsTool::getRolePermissions($role));
				}
			}
			return $perms;
		} else if ( is_string($roles) ){
			return Dataface_PermissionsTool::getRolePermissions($roles);
		}
		
		return $roles;
	}
	
	
	
	function loadPermissions(){
		$this->_permissionsLoaded = true;
		$configTool =& Dataface_ConfigTool::getInstance();
		$conf =& $configTool->loadConfig('permissions', $this->tablename);
		$permissionsTool =& Dataface_PermissionsTool::getInstance();
		$permissionsTool->addPermissions($conf);
		
	}
	
	/**
	 * A valid SQL select phrase for a single column. This will be used when extracting
	 * the titles of each row from the database.
	 * Example output:
	 * fname
	 * CONCAT(fname,' ',lname)
	 */
	function titleColumn(){
		if (!isset( $this->_atts['title'] ) ){
			$delegate =& $this->getDelegate();
			if ( $delegate !== null and method_exists($delegate, 'titleColumn') ){
				$this->_atts['title'] = $delegate->titleColumn();
			} else {
				$bestCandidate = null;
				$this->fields();
				$fieldnames = array_keys($this->_fields);
				foreach ($fieldnames as $fieldname){
					$field =& $this->_fields[$fieldname];
					if ( $bestCandidate === null and $this->isChar($fieldname) ){
						$bestCandidate = '`'.$fieldname.'`';
					}
					//if ( strpos(strtolower($fieldname),'title') !== false ){
					//	$bestCandidate = $fieldname;
					//}
				}
				if ( $bestCandidate === null ){
					$keynames = array_keys($this->keys());
					$bestCandidate = "CONCAT(".implode(",", $keynames).")";
				}
				$this->_atts['title'] = $bestCandidate;
			}
		}
		return $this->_atts['title'];
	
	}
		
	
	
	/**
	 * Returns a field structure with the given name.  This can also be related field. Simply prepend
	 * the relationship name followed by a period.  eg: relationship_name.field_name
	 * @param fieldname The name of the field for which we with to retrieve the construct.
	 * @throws PEAR_Error if the field requested does not exist or there was a problem processing 
	 * the relationship (if a relationship is specified).
	 * return Associative array with all attributes of the specified field.
	 */
	function &getField($fieldname){
		$path = explode('.', $fieldname);
		if ( count($path)==1){
			if ( !isset( $this->_fields[$fieldname]) ){
				$delegate =& $this->getDelegate();
				
				if ( $delegate !== null and method_exists($delegate, "field__$fieldname")){
					if ( isset($this->_atts[$fieldname]) ){
						$schema = array_merge_recursive_unique($this->_newSchema('calculated',$fieldname), $this->_atts[$fieldname]);
					} else {
						$schema = $this->_newSchema('calculated', $fieldname);
					}
					return $schema;
				}
				$grafted =& $this->graftedFields();
				if ( isset($grafted[$fieldname]) ) return $grafted[$fieldname];
				
				$transient =& $this->transientFields();
				if ( isset($transient[$fieldname]) ) return $transient[$fieldname];
				
				$parent =& $this->getParent();
				if ( isset($parent) and ( $field =& $parent->getField($fieldname) ) ){
					if ( !PEAR::isError($field) ) return $field;
				}
				
				$err = PEAR::raiseError(SCHEMA_NO_SUCH_FIELD_ERROR,null,null,null, "Field $fieldname does not exist in table ".$this->tablename);
				//trigger_error($err->toString()."\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
				return $err;
			}
			return $this->_fields[$fieldname];
		} else {
			// this field is from a relationship.
			
			// first check the cache
			if ( !isset( $this->_relatedFields[$path[0]] ) ) $this->_relatedFields[$path[0]] = array();
			if ( !isset( $this->_relatedFields[$path[0]][$path[1]] ) ) {
				
				$relationship =& $this->getRelationship($path[0]);
				
				//$absolute_name = Dataface_Table::absoluteFieldName($path[1], $relationship->_schema['selected_tables'], null, $relationship->_schema['columns']);
				//echo "Absolute_name = $absolute_name\n<br>"; print_r($relationship->_schema['selected_tables']);
				//echo Dataface_Error::printStackTrace();
			
				
				$this->_relatedFields[$path[0]][$path[1]] =& $relationship->getField($path[1]); //Dataface_Table::getTableField($absolute_name);
			}
			
			return $this->_relatedFields[$path[0]][$path[1]];
			
		}
	
	}
	
	/**
	 * This method returns a property associated with a field, that is defined 
	 * in the fields.ini file.  This method also checks the delegate class to see 
	 * if an equivalent property method has been defined.  Delegate class method
	 * names will be of the form <field_name>__<property_name>().  Note that in
	 * the case where the property name contains illegal characters (e.g., ':'),
	 * the character will be replaced by an underscore ( i.e., '_').
	 *
	 * @param $propertyName The name of the property. (e.g., 'widget:label')
	 * @param $fieldName The name of the field
	 * @param $params Optional named parameters:
	 *		record : A reference to a Dataface_Record object that can be used to
	 *				 provide context about which record is being edited.
	 *
	 * @since 0.6
	 */
	function getFieldProperty($propertyName, $fieldname, $params=array()){
		$field =& $this->getField($fieldname);
		
		if ( $field['tablename'] != $this->tablename ){
			$table =& Dataface_Table::loadTable($field['tablename']);
			return $table->getFieldProperty($propertyName, $fieldname, $params);
		}
		
		$table =& $this->getTableTableForField($fieldname);
		if ( $this->tablename !== $table->tablename ){
			// THis is a related field so we will have to check the delegate 
			// class for that table.
			list($tablename, $fieldname) = explode('.', $fieldname);
			return $table->getFieldProperty($propertyName,$fieldname, $params);
		}
		
		
		// First we will see if the delegate class defines as custom description.
		$delegate =& $this->getDelegate();
		$delegate_property_name = str_replace(':', '_', $propertyName);
		if ( method_exists($delegate, $fieldname.'__'.$delegate_property_name) ){
			
			if ( !isset( $params['record'] ) ) $params['record'] = null;
			$methodname = $fieldname.'__'.$delegate_property_name;
			$res =& $delegate->$methodname($params['record'], $params);
			//$res =& call_user_func(array(&$delegate, $fieldname.'__'.$delegate_property_name), $params['record'], $params);
			
			if ( !PEAR::isError($res) || $res->getCode() !== DATAFACE_E_REQUEST_NOT_HANDLED ){
				return $res;
			}
		} 
		// The delegate class doesn't define a custom description
		// we will just pull the property from the schema
		
		$path = explode(':', $propertyName);
		$arr =& $field;
		while ( count($path)> 0 ){
			$temp =& $arr[array_shift($path)];
			unset($arr);
			$arr =& $temp;
			unset($temp);
		}
		return $arr;
	}
	
	
	/**
	 * Returns the label's i18n id for a specified field. This will first check the 
	 * delegate
	 
	
	/**
	 * Returns the name of the field that is auto incrementing (if it exists).
	 */
	function getAutoIncrementField(){
		foreach (array_keys($this->keys()) as $field){
			if (strtolower($this->_fields[$field]['Extra']) == 'auto_increment'){
				return $field;
			}
		}
		return null;
	}
	
	/**
	 * Returns an associative array describing a field group.
	 * Sample group:
	 * 	array(
	 *		"element-display" => "inline", 			// Elements displayed inline
	 *							   					//  poss vals: ENUM("inline","block")
	 *		"element-label-visible" => true, 		// Show labels for elements
	 *		"element-description-visible" => true,	// Show descriptions for elements
	 *		"label" => "My Group",					// Label for the entire group
	 *		"description" => "This is a group of elements");
	 */
	function &getFieldgroup($fieldgroupname){
		if ( !isset( $this->_fieldgroups[$fieldgroupname] ) ){
			$parent =& $this->getParent();
			if ( isset($parent) ){
				$fg =& $parent->getFieldgroup($fieldgroupname);
				return $fg;
			}
			return PEAR::raiseError("Attempt to get nonexistent field group '$fieldgroupname' from table '".$this->tablename."'\n<br>". Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		return $this->_fieldgroups[$fieldgroupname];
	}
	
	function &getFieldgroups(){
		if ( !isset( $this->_cache[__FUNCTION__] ) ){
			$fg =& $this->_fieldgroups;
			$parent =& $this->getParent();
			if ( isset($parent) ){
				$fg = array_merge_recursive_unique($parent->getFieldgroups(), $fg);
				uasort($fg, array(&$this, '_compareFields'));
			}
			$this->_cache[__FUNCTION__] =& $fg;
		}
		return $this->_cache[__FUNCTION__];
	}
	
	/**
	 * Gets a relationship array.
	 * @param name The name of the relationship to retrieve.
	 * @return Associative array with keys ('sql', 'tables', 'columns')
	 */
	function &getRelationship($name){
		$r =& $this->relationships();
		
		if ( !isset($r[$name]) ){
			$err = PEAR::raiseError("Attempt to get relationship nonexistent '$name' from table '".$this->tablename, E_USER_ERROR);
			return $err;
		}
		
		return $r[$name];
	}
	
	function hasRelationship($name){
		$r =& $this->relationships();
		return isset($r[$name]);
	}
	
	
	
	/**
	 * Returns a list of the relationships for this table as actions.
	 * Relationships can carry any action attributes and indeed can be treated as actions themselves
	 * for purposes of displaying menus of the various relationships primarily.
	 */
	function getRelationshipsAsActions($params=array(), $relationshipName=null, $passthru=false){
		
		$relationships =& $this->relationships();
		$rkeys = array_keys($relationships);

		 if ( isset( $this->_cache['getRelationshipsAsActions']) ){
		 	$actions = $this->_cache['getRelationshipsAsActions'];
		 } else {
			 $actions = array();
			 foreach ( $rkeys as $key){
			 	$srcTable =& $relationships[$key]->getSourceTable();
				$actions[$key] = array(
					'name'=>$key, 
					'id'=>$key, 
					'label'=>$key, 
					'url' =>'{$this->url(\'-action=related_records_list&-relationship='.$key.'\')}',
					'selected_condition' => '$query[\'-relationship\'] == \''.$key.'\'',
					'label_i18n' => $srcTable->tablename.'::'.$key.'.label',
					'description_i18n' => $srcTable->tablename.'::'.$key.'.description',
					'order' => 1,
					'visible'=>(!($relationships[$key]->isParentRelationship()) ? 1 : 0)
					);
				if ( isset($relationships[$key]->_schema['action']) ){
					$actions[$key] = array_merge($actions[$key], $relationships[$key]->_schema['action']);
				}
				unset($srcTable);
			 }
			 $this->_cache['getRelationshipsAsActions'] = $actions;
		 }
		 
		 import('Dataface/ActionTool.php');
		 $actionsTool =& Dataface_ActionTool::getInstance();
		 $out =& $actionsTool->getActions($params, $actions);
		 if ( isset($relationshipName) ) {
		 	if ( isset($out[$relationshipName]) ){
		 		return @$out[$relationshipName];
		 	} else {
		 		return $actionsTool->getAction($params, $actions[$relationshipName]);
		 		
		 	}
		 }
		 return $out;
	}
	
	
	/**
	 * Import filters facilitate the importing of data into the table.
	 * @return Array of Dataface_ImportFilter objects
	 */
	
	function &getImportFilters(){
		import( 'Dataface/ImportFilter.php');
		if ( $this->_importFilters === null ){
			$this->_importFilters = array();
			/*
			 * Filters have not been loaded yet.. let's load them.
			 *
			 * Any method in the delegate file with a name of the form __import__<string>
			 * is considered to be an import filter.
			 *
			 */
			$delegate =& $this->getDelegate();
			if ( $delegate !== null ) {
				$methods = get_class_methods(get_class( $delegate ) );
				foreach ( $methods as $method ){
					$matches = array();
					if ( preg_match( '/^__import__(.*)$/', $method, $matches) ){
						$filter =& new Dataface_ImportFilter($this->tablename, $matches[1], df_translate('import_filters:'.$matches[1].':label', ucwords(str_replace('_',' ',$matches[1]))));
						$this->_importFilters[$matches[1]] =& $filter;
						unset($filter);
					}
				}
			}
			
			$parent =& $this->getParent();
			if ( isset($parent) ){
				$this->_importFilters = array_merge($parent->getImportFilters(), $this->_importFilters);
			}
		
		}
		
		return $this->_importFilters;
	}
	
	
	/**
	 * Registers an import filter for this table.  
	 * @param $filter The import filter to register.
	 * @type Dataface_ImportFilter
	 *
	 */
	function registerImportFilter(&$filter){
		if (!is_a( $filter, 'Dataface_ImportFilter') ){
			trigger_error("In Dataface_Table::registerImportFilter() 2nd argument expected to be of type 'Dataface_ImportFilter' but received '".get_class($filter)."'. ".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$filters =& $this->getImportFilters();
		$filters[$filter->name] =& $filter;
	
	}

	

	/**
	 * Convert a date array to a string.
	 * Can be called statically.
	 * @deprecated Use Dataface_converters_date::date_to_string()
	 */
	function date_to_string($value){
	
		return Dataface_converters_date::date_to_string($value);
		
	
	}
	
	function getSerializer(){
		if ( !isset( $this->_serializer ) ){

			$this->_serializer =& new Dataface_Serializer($this->tablename);
		}
		return $this->_serializer;
	}
	
	/**
	 * Converts a datetime array to a string.
	 * @deprecated Use Dataface_converters_date::datetime_to_string()
	 */
	function datetime_to_string($value){ 
		return Dataface_converters_date::datetime_to_string($value);
	}
	
	/**
	 * Converts a time array to a string.
	 * @deprecated Use Dataface_converters_date::time_to_string()
	 */
	function time_to_string($value){ return Dataface_converters_date::time_to_string($value); }
	
	/**
	 * Converts a timestamp array to a string.
	 * @deprecated Use Dataface_converters_date::timestamp_to_string()
	 */
	function timestamp_to_string($value){ return Dataface_converters_date::timestamp_to_string($value); }
	
	
	/** 
	 * Returns the type of a field , eg: int, float, varchar, blob, etc...
	 * @param fieldname The name of the field whose type we wish to have returned.
	 */
	function getType($fieldname){
		if ( !isset($this->_cache[__FUNCTION__][$fieldname]) ){
			$field =& $this->getField($fieldname);
			if ( PEAR::isError($field) ) return $field;
			
			if ( isset( $field ) ){
				
				$type = $field['Type'];
				
				$matches = array();
				
				if ( preg_match('/^([^\(]+).*/', $type, $matches) ){
					$type = $matches[1];
				}
				
				$type = trim($type);
				$this->_cache[__FUNCTION__][$fieldname] = strtolower($type);
			}
		}
		return $this->_cache[__FUNCTION__][$fieldname];
			
	
	}
	
	/**
	 * Checks a field to see if it is a date type (date, datetime, time, or timestamp).
	 */
	function isDate($fieldname){
		$type = $this->getType($fieldname);
		if ( PEAR::isError($type) ){
			return false;
		}
		return in_array( $type, array('date','datetime','time','timestamp') );
	}
	
	
	/**
	 * Checks a field to see if it is a blob type.
	 */
	function isBlob($fieldname){
		return in_array( $this->getType($fieldname), array('blob', 'longblob','tinyblob','longblob') );
	}
	
	/**
	 * Indicates if a field is a container field.  A container field is a varchar or char field
	 * but it contains the path to a file rather than data itself.
	 */
	function isContainer($fieldname){
		return strtolower($this->getType($fieldname)) == 'container';
	
	}
	
	function isPassword($fieldname){
		$field =& $this->getField($fieldname);
		//if ( !is_array($field) ) return false;
		return ($field['widget']['type'] == 'password');
	}
	
	function isText($fieldname){
		return in_array( $this->getType($fieldname), array('text','longtext','tinytext','mediumtext') );
	}
	
	function isXML($fieldname){
		$fld =& $this->getField($fieldname);
		return in_array( $fld['widget']['type'], array('table','group'));
	}
	
	function isChar($fieldname){
		return in_array( $this->getType($fieldname), array('varchar','char') );
	}
	
	function isInt($fieldname){
		return in_array(strtolower($this->getType($fieldname)), array('int','tinyint','mediumint','smallint','bigint'));
	}
	
	function isFloat($fieldname){
		return in_array(strtolower($this->getType($fieldname)), array('float','double','tinyfloat','mediumfloat','decimal'));
	}
	
	
	
	




	function parse_datetime($value){
		return $this->parse_datetype($value);
	
	
	}
	
	
	function parse_date($value){
	
		return $this->parse_datetype($value);
	
	}
	
	
	function parse_timestamp($value){
		if ( $value == 'CURRENT_TIMESTAMP' ){
			$value = date('Y-m-d H:i:s');
		}
		return $this->parse_datetype($value);
	
	}
	
	
	function parse_time($value){
		return $this->parse_datetype($value);
	}
	
	/**
	 * @deprecated Use Dataface_converters_date::isTimestamp()
	 */
	function isTimeStamp($value){
		$converter =& new Dataface_converters_date;
		return $converter->isTimestamp($value);
	}
	
	/**
	 * @deprecated Use Dataface_converters_date::parseDate()
	 */
	function parse_datetype($value){
		$converter =& new Dataface_converters_date;
		return $converter->parseDate($value);
		
	
	}
			
	
	
	
	function hasKey($name){
		if ( !isset( $this->_fields[$name] ) ) return false;
		if ( isset( $this->_fields[$name]['Key'] ) && strtolower($this->_fields[$name]['Key']) == strtolower('PRI') ){
			return true;
		}
		return false;
	
	}

	

	
	/**
	 * Returns reference to the attributes array for this table.
	 */
	function &attributes(){
		if ( !isset($this->_cache[__FUNCTION__]) ){
			$atts =& $this->_atts;
			$parent =& $this->getParent();
			if ( isset($parent) ){
				$atts = array_merge($parent->attributes(), $atts);
			}
			$this->_cache[__FUNCTION__] =& $atts;
		}
		return $this->_cache[__FUNCTION__];
	}
	
	/**
	 * Returns reference to the relationships array for this table.
	 */
	function &relationships(){
		if ( !$this->_relationshipsLoaded ){
			$start = microtime_float();
			$this->_loadRelationshipsIniFile();
			$end = microtime_float()-$start;
			if ( DATAFACE_DEBUG ){
				$this->app->addDebugInfo("Time to load relationships: $end");
			}
		}
		
		return $this->_relationships;
	}
	
	function clearValuelistCache(){
		$this->_valuelists = null;
	}
	
	/**
	 * Returns a named valuelist in this table.
	 */
	function &getValuelist( $name ){
		$delegate =& $this->getDelegate();
		if ( method_exists($delegate, 'valuelist__'.$name) ){
			$res = call_user_func(array(&$delegate, 'valuelist__'.$name));
			if ( is_array($res) ) return $res;
		}
		
		$parent =& $this->getParent();
		if ( isset($parent) ){
			$res = $parent->getValuelist($name);
			if ( is_array($res) ) return $res;
		}
		
		$app =& Dataface_Application::getInstance();
		$appdel =& $app->getDelegate();
		if ( isset($appdel) and method_exists($appdel, 'valuelist__'.$name) ){
			$res = call_user_func(array(&$appdel, 'valuelist__'.$name));
			if ( is_array($res) ) return $res;
		}
		
		if ( !isset( $this->_valuelists ) ){
			$this->_loadValuelistsIniFile();
		}
		if ( isset( $this->_valuelists[$name] ) ){
			return $this->_valuelists[$name];
			
		} 
		import( 'Dataface/ValuelistTool.php');
		if ( Dataface_ValuelistTool::hasValuelist($name) ){
			
			return Dataface_ValuelistTool::getValuelist($name);
		} else {
			$null = null;
			return $null;
		}
	}
	
	function &valuelists(){
		
		if ( !isset( $this->_valuelists ) ){
			
			$this->_loadValuelistsIniFile();
		}
		return $this->_valuelists;
	}
	
	
	/**
	 * Returns a list of all valuelists that are available to this table.
	 */
	function getAvailableValuelistNames(){
		$valuelists = array_keys($this->valuelists());
		$delegate =& $this->getDelegate();
		if ( isset($delegate) ){
			$delegate_methods = get_class_methods(get_class($delegate));
			$valuelist_methods = preg_grep('/^valuelist__/', $delegate_methods);
			foreach ( $valuelist_methods as $method ){
				$valuelists[] = substr($method, 11);
			}
		}
		import( 'Dataface/ValuelistTool.php');
		
		$valuelists = array_merge($valuelists, array_keys(Dataface_ValuelistTool::valuelists()));
		return $valuelists;
	}
	/**
	 * Takes a value a normalizes it to a string representation of the value as encoded for the specified
	 * field.  This is useful for equality comparisons on values of fields.
	 * @param fieldname The full path of the field on which we are normalizing.
	 * @param value The value which we are normalizing.
	 */
	function normalize($fieldname, $value){
	
		return $this->getValueAsString($fieldname, $this->parse($fieldname, $value));
	}
	
		/**
	 * <p>Gets the values of this record as strings.  Some records like dates and times, are stored as data structures.  getValue()
	 * returns these datastructures unchanged.  This method will perform the necessary conversions to return the values as strings.</p>
	 */
	function getValueAsString($fieldname, $value){
		//return $this->_table->getValueAsString($fieldname, $this->getValue($fieldname), $index);
		
		//$value = $this->getValue($fieldname, $index);
		
		
		$table =& $this->getTableTableForField($fieldname);
		$delegate =& $table->getDelegate();
		$rel_fieldname = $table->relativeFieldName($fieldname);
		if ( $delegate !== null and method_exists( $delegate, $rel_fieldname.'__toString') ){
			$value = call_user_func( array(&$delegate, $rel_fieldname.'__toString'), $value);
		} else 
		
		
		if ( is_array($value) ){
			if ( method_exists( $this, $this->getType($fieldname)."_to_string") ){
				$value = call_user_func( array( &$this, $this->getType($fieldname)."_to_string"), $value );
			} else {
				$value = implode(', ', $value);
			}
		}
		
		return $value;
	}
	
	
	/**
	 *
	 * Parses a value so that it conforms with the value stored in the given field.
	 *
	 * @param fieldname The name of the field that where the value should be storable.
	 * @param value The value we wish to parse.
	 * @param parseRepeat If true, this indicates that if this is a repeating field,
	 * 					then all of it's repeats should also be parsed individually.
	 *
	 */
	function parse($fieldname, $value, $parseRepeat=true){
		if ( strpos($fieldname, '.') !== false ){
			// If this is a related field, we allow the related table to do the 
			// parsing
			$table =& $this->getTableTableForField($fieldname);
			if ( PEAR::isError($table) ){
				trigger_error( $table->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
			}
			list($rel, $fieldname) = explode('.', $fieldname);
			return $table->parse($fieldname, $value, $parseRepeat);
		}
		$type = $this->getType($fieldname);
		
		$field =& $this->getField($fieldname);
		
		
		$delegate =& $this->getDelegate();
		if ( $delegate !== null and method_exists( $delegate, $fieldname.'__parse') ){
			$value = call_user_func( array( &$delegate, $fieldname.'__parse'), $value);
		}
		
		else if ( $parseRepeat and $field['repeat']  and ($this->isText($fieldname) or $this->isChar($fieldname) ) ){
			
			$value = $this->parse_repeated($fieldname, $value, $field['separator']);
		}
		
		else if ( method_exists( $this, 'parse_'.strtolower($type) ) ){
			
			$value = call_user_func( array(&$this, 'parse_'.strtolower($type)), $value);
		} 
	
		else if ( in_array($field['widget']['type'], array('group','table') )  and is_string($value) ){
			//error_log("\nAbout to parse $fieldname for value $value",3,'log.txt');
			//if ( is_string($value) and strlen($value)> 10){
				//error_log('About to serialize '.$fieldname, 3, 'log.txt');
				import( 'XML/Unserializer.php');
				$unserializer =& new XML_Unserializer();
				$parsed = $unserializer->unserialize($value);
				
				if ( !PEAR::isError($parsed) ){
					$value = $unserializer->getUnserializedData();
				} 
				else {
					//echo "Failed to parse $value";
					//$value = $unserializer->getUnserializedData();
					//error_log("\n".'Error parsing '.$fieldname.$parsed->toString().Dataface_Error::printStackTrace(), 3, 'log.txt');
				}
			//}
			
			
		}
		return $value;
	
	}
	
	
	function parse_repeated($fieldname, $value, $separator="\n"){
		if ( !is_array($value) ){
			$value = explode($separator, $value);
		}
		foreach (array_keys($value) as $key) {
			$value[$key] = $this->parse($fieldname, $value[$key], false);
		}
		
		return $value;
	}
	

	
	/**
	 * Parses a string and replaces variables with string representations of the variables.
	 * @deprecated See Dataface_Record::parseString()
	 *
	 */
	function parseString( $str, $values ){
		if ( !is_string($str) ) return $str;
		$matches = array();
		$blackString = $str;
		while ( preg_match( '/(?<!\\\)\$([0-9a-zA-Z\._\-]+)/', $blackString, $matches ) ){
			if ( $this->hasField($matches[1]) ){
				$replacement = $this->normalize($matches[1], $values[$matches[1]]);
				
			} else {
				$replacement = "";
			}
			$str = preg_replace( '/(?<!\\\)\$'.$matches[1].'/', $replacement, $str);
			$blackString = preg_replace( '/(?<!\\\)\$'.$matches[1].'/', "", $blackString);
			
		}
		return $str;
	}
	
	
	
	
	// Some static utility methods
	
	/**
	 * Returns field information given its address.  
	 * <b>Note:</b> To prevent deadlocks, this method should never be called inside the Dataface_Table constructor.
	 * @param address The address to a field.  eg: Profile.fname is the address of the fname field in the Profile table.
	 * @param db A db resource id.
	 * @throws PEAR_Error if Address is invalid or the table or field does not exist.
	 * @return the field associative array from the appropriate table.
	 */
	function &getTableField($address, $db=''){
		$addr = array_map('trim', explode('.', $address) );
		if ( sizeof($addr) != 2 ) {
			return PEAR::raiseError(SCHEMA_NO_SUCH_FIELD_ERROR, null,null,null, "Call to getTableField with invalid address: '$address'.  Address must be absolute in the form 'table_name.column_name'.");
		}
		
		$table =& Dataface_Table::loadTable($addr[0], $db);
		if ( PEAR::isError($table) ){
			return $table;
		}
		
		$fields =& $table->fields(false,true,true);
		if ( !isset( $fields[ $addr[1] ] ) ){
			return PEAR::raiseError(SCHEMA_NO_SUCH_FIELD_ERROR, null,null,null, "Call to getTableField with invalid address: '$address'.  Column $addr[1] does not exists in table $addr[0].");
		}
		
		return $fields[$addr[1]];
	}
	
	
	/**
	 * Returns the Table object that contains a specified field.  The fieldname is given as a relationship path (as opposed to 
	 * an absolute path.  This method cannot be called statically.
	 */
	function &getTableTableForField($fieldname){
	
		if ( strpos($fieldname,'.') !== false ){
			$path = explode('.', $fieldname);
			$relationship =& $this->getRelationship($path[0]);
			
			//print_r($relationship->_schema['selected_tables']);
			foreach ($relationship->_schema['selected_tables'] as $table ){
				$table =& Dataface_Table::loadTable($table, $this->db);
				//if ( in_array( $path[1], array_keys($table->fields()) ) ){
				if ( $table->hasField($path[1]) ){
					return $table;
				}
				unset($table);
			}
			
			return PEAR::raiseError(SCHEMA_TABLE_NOT_FOUND,null,null,null,"Failed to find table table for field '$fieldname' in Dataface_Table::getTableTableForField() on line ".__LINE__." of file ".__FILE__);
		} else {
			return $this;
		}
	
	}
	
	
	/**
	 * Checks if a field exists. 
	 * <b>Note:</b> To prevent deadlocks, this method should never be called inside the Dataface_Table constructor.
	 * @param address The absolute path to a field (of the form table_name.field_name).
	 * @param db A db resource connection.
	 * @return @boolean true if field exists; false otherwise.
	 */
	function fieldExists($address, $db=''){
	
		$res = Dataface_Table::getTableField($address, $db);
		return !PEAR::isError($res);
	
	}
	
	/**
	 * Checks if the given table exists.  This caches the results so that you won't have
	 * to check the existence of the same table twice.
	 * @param string $tablename The name of the table to check.
	 * @param boolean $usecache Whether to use the cache or not.
	 * @returns boolean Whether the table exists or not.
	 */
	function tableExists($tablename, $usecache=true){
		$app =& Dataface_Application::getInstance();
		static $index = 0;
		if ($index === 0 ) $index = array();
		if ( !isset($index[$tablename]) or !$usecache ) {
			$index[$tablename] = mysql_num_rows(mysql_query("show tables like '".addslashes($tablename)."'", $app->db()));
		}
		return $index[$tablename];
	}
	
	function exists($fieldname){
		return $this->hasField($fieldname);
	}
	
	
	function hasField($fieldname, $checkParent=true){
		if ( strpos($fieldname,'.') > 0 ){
			list($rel_name, $fieldname) = explode('.', $fieldname);
			if ( !$this->hasRelationship($rel_name) ) return false;
			$relationship =& $this->getRelationship($rel_name);
			if ( $relationship->hasField($fieldname) ) return true;
			return false;
		} else {
			if ( array_key_exists($fieldname, $this->fields(false,true)) ) return true;
			//if ( isset( $this->_fields[$fieldname] ) ) return true;
			//if ( isset( $this->_graftedFields[$fieldname]) ) return true;
			$delegate =& $this->getDelegate();
			if ( $delegate !== null and method_exists($delegate, 'field__'.$fieldname) ) return true;
			$transient =& $this->transientFields();
			if ( isset($transient[$fieldname]) ) return true;
			
			if ( $checkParent ){
				$parent =& $this->getParent();
				if ( isset($parent) and $parent->hasField($fieldname) ) return true;
			}
		}
		return false;
	
	}
	
	/**
	 * Returns the absolute field name of a field as it appears in one of the given tables.  This is kind of like
	 * a search to find out which of the given tables, the column belongs to.  The absolute field name is a string
	 * of the form table_name.field_name (ie table name and field name separated by a dot).
	 * This method may be called statically.
	 *
	 * <b>Note:</b> To prevent deadlocks, this method should never be called inside the Dataface_Table constructor.
	 * @param field The name of a field.
	 * @param tablenames An array of table names.
	 * @param db A db resource to query the database.
	 * @throws PEAR_Error if none of the specified tables contain a field named $field, or if more than one table
	 *						contains a field named $field.
	 * @return @type string
	 */
	function absoluteFieldName($field, $tablenames, $db='', $columnList=null){
		if ( Dataface_Table::fieldExists($field, $db) ){
			return $field;
		} else if ( strpos($field, '.') > 0 ){
			return Dataface_Table::getTableField($field, $db);
		} else {
			$found = 0;
			$name = '';
			
			if ( is_array($columnList) ){
				foreach ( $columnList as $column ){
					if ( preg_match('/^(\w+)\.'.$field.'$/', $column) ){
						$name = $column;
						$found++;
					}
				}
			} else {
				foreach ($tablenames as $table){
					if ( Dataface_Table::fieldExists($table.'.'.$field, $db) ){
						$name = $table.'.'.$field;
						$found++;
					}
				}
			}
		}
		
		if ( $found == 0 ){
			$err = PEAR::raiseError(SCHEMA_NO_SUCH_FIELD_ERROR,null,null,null, "Field $field does not exist in tables ".implode(',', $tablenames).".");
			//print_r($field); exit;
			
			trigger_error($err->toString()."\n<br>".Dataface_Error::printStackTrace(), E_USER_WARNING);
		}
		//if ( $found > 1 ){
		//	$err = PEAR::raiseError(SCHEMA_AMBIGUOUS_FIELD_ERROR, null, null, null, "Field $field appears in more than one table in (".implode(',',$tablenames).").");
		//	trigger_error($err->toString()."\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
		//	
		//}
		
		return $name;
	
	}
	
	function relativeFieldName($fieldname){
		if ( strpos($fieldname,'.') !== false ){
			$path = explode('.', $fieldname);
			return $path[1];
		}
		return $fieldname;
	
	}
	
	
	/**
	 * Returns a reference to the Table's delegate class.
	 */
	function &getDelegate(){
		$out = null;
		if ( !isset( $this->_delegate ) ){
			if ( $this->_hasDelegateFile() ){
				
				$this->_loadDelegate();
					
			} else {
				
				return $out;
			}
		} 
		return $this->_delegate;
		
	}
	
	/**
	 * Gets the tables from the database that are explicitly for importing data.
	 * They are tables of the form Tablename__import__<timestamp> where <timestamp>
	 * is the unix timestamp of when the import table was created.
	 */
	function getImportTables(){
		$res = mysql_query("SHOW TABLES LIKE '".$this->tablename."__import_%'", $this->db);
		if ( !$res ){
			trigger_error("Error getting import table list for table '".$this->tablename."'.".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		$tables = array();
		while ( $row = mysql_fetch_row($res) ){
			$tables[] = $row[0];
		}
		mysql_free_result($res);
		return $tables;
	}
	
	
	/**
	 * Creates an import table for this table.  An import table is an empty clone
	 * of this table.  It serves as an intermediate step  towards importing data into
	 * the main table.
	 */
	function createImportTable(){
		/*
		 * It is a good idea to clean the import tables before we create them.
		 * That way they don't get cluttered
		 */
		$this->cleanImportTables();
		
		$rand = rand(10000,999999);
		$name = $this->tablename.'__import_'.strval(time()).'_'.strval($rand);
		$qb =& new Dataface_QueryBuilder($this->tablename);
		$res = mysql_query("CREATE TABLE `$name` SELECT * ".$qb->_from()." LIMIT 0", $this->db);
		if (!$res ){
			trigger_error("Failed to create import table `$name` because a mysql error occurred: ".mysql_error($this->db)."\n".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		return $name;
	
	}
	
	/**
	 * Cleans up old import tables.  Any import tables older (in seconds) than the
	 * garbage collector threshold (as defined in $app->_conf['garbage_collector_threshold'])
	 * will be dropped.
	 *
	 */
	function cleanImportTables(){
		
		$tables = $this->getImportTables();
		$app =& Dataface_Application::getInstance();
		$garbageLifetime = $app->_conf['garbage_collector_threshold'];
		foreach ($tables as $table){
			$matches =array();
			if ( preg_match('/^'.$this->tablename.'__import_(\d+)_(\d)$/', $table,  $matches) ){
				if ( time() - intval($matches[1]) > intval($garbageLifetime) ){
					$res = mysql_query("DROP TABLE `$table`", $this->db);
					if ( !$res ){
						trigger_error("Problem occurred attemtping to clean up old import table '$table'. MySQL returned an error: ".mysql_error($this->db)."\n".Dataface_Error::printStackTrace(), E_USER_ERROR);
					}
				}
			}
		}
	}
	
	
	function isImportTable($tablename){
		return preg_match('/^\w+__import_(\d{5,20})_(\d{4,10})$/', $tablename);
		
	
	}
	
	
	/**
	 *
	 * Prepares data to be imported into the table.  It takes raw data and produces an array of
	 * Dataface_Record objects that can be imported into the table.
	 *
	 * @param	$data			Raw data that is to be imported.
	 *		@type raw
	 *
	 * @param	$importFilter	The name of the import filter that is used to import the data.
	 *							If this is null then every import filter is attempted until one is 
	 *							found that works.
	 *		@type string | null
	 *
	 * @return	An array of Dataface_Record objects encapsulating the imported data.  These objects
	 *			must be records of the current table.
	 *
	 * @throws PEAR_Error if the importing fails for some reason.
	 *
	 * Usage:
	 * -------
	 *
	 * $data = '<phonelist>
	 *				<listentry>
	 *					<name>John Smith</name><number>555-555-5555</number>
	 *				</listentry>
	 *				<listentry>
	 *					<name>Susan Moore</name><number>444-444-4444</number>
	 *				</listentry>
	 *			</phonelist>';
	 * 
	 * 		// assume that we have an import filter called 'XML_Filter' that can import the above data.
	 *
	 * $table =& Dataface_Table::loadTable('ListEntry');
	 * $records = $table->parseImportData(	$data,			// The raw data to import
	 *										'XML_Filter'	// The name of the filter to handle the import
	 *										);
	 *
	 * echo get_class($records[0]);		// outputs 'Dataface_Record'
	 * echo $records[0]->val('name');	//outputs 'John Smith'
	 * echo $records[0]->val('number'); // outputs '555-555-5555'
	 * echo $records[1]->val('name');	// outputs 'Susan Moore'
	 * echo $records[1]->val('number');	// outputs '444-444-4444'
	 *
	 * // Note that the records in the $records array are NOT persisted in the database.
	 * 
	 * @see Dataface_Table.loadTable()
	 * @see Dataface_Table.getImportFilters()
	 * @see Dataface_Record.val()
	 *
	 */
	function parseImportData($data, $importFilter=null, $defaultValues=array()){
		$filters =& $this->getImportFilters();
		$delegate =& $this->getDelegate();
		if ( $delegate === null ){
			/*
			 * Currently the only place that Import filters can be defined is in the
			 * delegate file.  If there is no delegate file, then there are no filters.
			 * if there are no filters, then we can't possibly do any importing so we
			 * return an error.
			 */
			return Dataface_Error::noImportFiltersFound();
		}
		$errors = array();
		if ( $importFilter === null ){
			/*
			 * The filter is not specified so we will try every filter until we find one
			 * that works.
			 */
			foreach (array_keys($filters) as $filtername){
				$parsed =& $filters[$filtername]->import($data, $defaultValues);
				if ( PEAR::isError($parsed) ){
					/*
					 * This filter encountered an error.
					 * Record the error, and unset the $parsed variable.
					 */
					$errors[$filtername] =& $parsed;
					unset($parsed);
					continue;
				}
				
				break;
			}
			
			if ( isset($parsed) ){
				/*
				 * The only way that the $parsed variable should be 'set' is if 
				 * one of the filters successfully parsed the data.
				 */
				return $parsed;
			
			} else {
				return Dataface_Error::noImportFiltersFound(
					"No suitable import filter was found to import data into table '".$this->tablename."'.  The following filters were attempted: {".implode(',', array_keys($errors))."}."
				);
			}
		} else {
			/*
			 * A particular import filter was specified so we will try with that one.
			 */
			if ( !isset( $filters[$importFilter] ) ){
				return Dataface_Error::noImportFiltersFound("The import filter '".$importFilter."' was not found while attempting to import data into the table '".$this->tablename."'.  The following import filters are available: {".implode(',', array_keys($errors))."}."
				);
			}
			
			return $filters[$importFilter]->import($data, $defaultValues);
		}
	
	}
	
	
	/**
	 * Validates against a field of this table.  This checks if a value is valid for this
	 * a field of this table.
	 *
	 * @param $fieldname The name of the field
	 * @param $value The value to validate for the field.
	 * @param $params Array of parameters. This may be used to pass parameters OUT of this function.
	 *				  For example.  Setting the 'message' attribute of this array will pass out a message
	 *				  to be displayed to the user along with the error upon failed validation.
	 * @return boolean True if it validates ok, false otherwise.
	 */
	function validate($fieldname, $value, &$params){
		$field =& $this->getField($fieldname);
		if ( $field['widget']['type'] == 'file' and is_uploaded_file(@$value['tmp_name']) and is_array($value)){
			// This bit of validation code is executed for files that have just been uploaded from the form.
			// It expects the value to be an array of the form:
			// eg: array('tmp_name'=>'/path/to/uploaded/file', 'name'=>'filename.txt', 'type'=>'image/gif').
			
			if ( !is_array(@$field['allowed_extensions']) and @$field['allowed_extensions']){
				$field['allowed_extensions'] = explode(',',@$field['allowed_extensions']);
			}
			if ( !is_array(@$field['allowed_mimetypes']) and @$field['allowed_mimetypes'] ){
				$field['allowed_mimetypes'] = explode(',',@$field['allowed_mimetypes']);
			}
			if ( !is_array(@$field['disallowed_extensions']) and @$field['disallowed_extensions'] ){
				$field['disallowed_extensions'] = explode(',',@$field['disallowed_extensions']);
			}
			if ( !is_array(@$field['disallowed_mimetypes']) and @$field['disallowed_extensions']){
				$field['disallowed_mimetypes'] = explode(',',@$field['disallowed_mimetypes']);
			}
			
			$field['allowed_extensions'] = @array_map('strtolower', @$field['allowed_extensions']);
			$field['allowed_mimetypes'] = @array_map('strtolower', @$field['allowed_mimetypes']);
			$field['disallowed_extensions'] = @array_map('strtolower', @$field['disallowed_extensions']);
			$field['disallowed_mimetypes'] = @array_map('strtolower', @$field['disallowed_mimetypes']);
			// We do some special validation for file uploads
			// Validate -- make sure that it is the proper mimetype and extension.
			if ( is_array( @$field['allowed_mimetypes'] ) and count($field['allowed_mimetypes']) > 0 ){
				if ( !in_array($value['type'], $field['allowed_mimetypes']) ){
					$params['message'] = "The file submitted in field '".$field['name']."' is not the correct type.  Received '".$value['type']."' but require one of (".implode(',', $field['allowed_mimetypes']).").";
					
					return false;
				}
			}
			
			if ( @is_array(@$field['disallowed_mimetypes']) and in_array($value['type'], $field['disallowed_mimetypes']) ){
				$params['message'] = "The file submitted in field '".$fieldname."' has a restricted mime type.  The mime type received was '".$value['type']."'.";
				return false;
			}
			
			$extension = '';
			$matches = array();
			if ( preg_match('/\.([^\.]+)$/', $value['name'], $matches) ){
				$extension = $matches[1];
			}
			$extension = strtolower($extension);
			
			
			if ( is_array( @$field['allowed_extensions'] ) and count($field['allowed_extensions']) > 0 ){
				if ( !in_array($extension, $field['allowed_extensions']) ){
					$params['message'] = "The file submitted in field '".$field['name']."' does not have the correct extension.  Received file had extension '".$extension."' but the field requires one of (".implode(',', $field['allowed_extensions']).").";
					
					return false;
				}
			}
	
			if ( @is_array( @$field['disallowed_extensions'] ) and in_array($extension, $field['disallowed_extensions']) ){
				$params['message'] = "The file submitted in field '".$fieldname."' has a restricted extension.  Its extension was '".$extension."' which is disallowed for this form.";
				return false;
			}
			
			if ( @$field['max_size'] and intval($field['max_size']) < intval(@$value['size']) ){
				$params['message'] = "The file submitted in field '".$fieldname."' is {$value['size']} bytes which exceeds the limit of {$field['max_size']} bytes for this field.";
				return false;
			}
		}
		
		//$delegate =& $this->getDelegate();
		//if ( $delegate !== null and method_exists($delegate, $fieldname."__validate") ){
		//	/*
		//	 *
		//	 * The delegate defines a custom validation method for this field.  Use it.
		//	 *
		//	 */
		//	return call_user_func(array(&$delegate, $fieldname."__validate"), $this, $value, $params);
		//}
		return true;
	}
	
	/**
	 * Print information about Table
	 */
	function tableInfo(){
		
		$info = array();
		foreach ( array_keys($this->fields()) as $fieldname){
			$field =& $this->getField($fieldname);
			$info['fields'][$fieldname]['Type'] = $field['Type'];
			$info['fields'][$fieldname]['Extra'] = $field['Extra'];
			$info['fields'][$fieldname]['Key'] = $field['Key'];
			$info['fields'][$fieldname]['widget'] = $field['widget'];
			unset($field);	
		}	
		return $info;
	}
	
	function databaseInfo(){
		$tables =& Dataface_Table::loadTable('',null,true);
		$info = array();
		foreach ( array_keys($tables) as $tablename ){
			$info[$tablename] =& $tables[$tablename]->tableInfo();	
		}
		return $info;
	}
	
	/**
	 * Displays a block from the delegate class.  Blocks are defined in the delegate
	 * class by defining methods with names starting with 'block__'.  Eg: block__header()
	 * @param blockName The name of the block.
	 * @param params Associative array of key/value pairs to pass to the block.
	 *
	 */
	function displayBlock($blockName, $params=array()){
		if ( @$this->app->_conf['debug'] ) echo "<div class=\"debug_marker\">Block &quot;$blockName&quot;</div>";
		$delegate =& $this->getDelegate();
		//echo "Checking for block $blockName";
		$res = false;
		
		// Add the ability for Modules to define blocks without conflicting with
		// defined blocks in the application.
		// Added Feb. 28, 2007 by Steve Hannah for 0.6.14 release.
		$mres = false;
		if ( isset($this->app->_conf['_modules']) and count($this->app->_conf['_modules']) > 0){
			$mtool =& Dataface_ModuleTool::getInstance();
			$mres = $mtool->displayBlock($blockName, $params);
		}
		if ( isset($delegate) and method_exists($delegate, 'block__'.$blockName) ){
			$methodname = 'block__'.$blockName;
			$fres = $delegate->$methodname($params);
			//$fres = call_user_func(array(&$delegate, 'block__'.$blockName), $params);
			if ( !PEAR::isError($fres) ) 
				$res = true;
		} else {
		
			$appDelegate =& $this->app->getDelegate();
			if (isset($appDelegate) and method_exists($appDelegate, 'block__'.$blockName) ){
				$methodname = 'block__'.$blockName;
				$fres = $appDelegate->$methodname($params);
				//$fres = call_user_func(array(&$appDelegate, 'block__'.$blockName), $params);
				if ( !PEAR::isError($fres) )
					$res = true;
			}
		}
		return ($res or $mres);
		
	}
	
	/**
	 * Returns the content of a given block as a string.
	 * @param blockName The name of the block.
	 * @param $params Associative array of parameters to pass to the block.
	 */
	function getBlockContent($blockName, $params=array()){
		ob_start();
		$res = $this->displayBlock($blockName, $params);
		$out = ob_get_contents();
		ob_end_clean();
		if ( !$res ) return null;
		return $out;
	}
	
	/**
	 * Returns a new blank Dataface_Record object for this table.
	 * @param $vals Associative array of values to initialize in this record.
	 */
	function newRecord($vals=null){
		return new Dataface_Record($this->tablename, $vals);
	}
	
	/**
	 * Gets a record from the database that matches the given query.
	 * @param $query associative array of key/value search terms.
	 */
	function &getRecord($query=null){
		return df_get_record($this->tablename, $query);
	}
	

}
