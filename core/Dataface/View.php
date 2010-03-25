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
import('Dataface/Table.php');
import('SQL/Parser/wrapper.php');
import('Dataface/DB.php');


/**
 * A Surrogate view class.  This is like a Table except that it is defined 
 * with an SQL query.
 */
class Dataface_View extends Dataface_Table {

	/**
	 * The string sql query defining this view.
	 */
	var $sql;
	
	/**
	 * The parsed sql query data structure - as produced by SQL_Parser
	 */
	var $sql_data = null;
	
	/**
	 * A cache to store calculated values.
	 */
	var $_cache = array();
	
	/**
	 * The name of the view.
	 */
	var $name;
	
	var $app;
	
	
	function Dataface_View($name, $sql=null){
		import('Dataface/ViewRecord.php');
		$this->name = $name;
		$this->tablename = $name;
		if ( is_array($sql) ){
			// The sql is parsed SQL
			$this->sql_data = $sql;
		} else {
			
			$this->sql = $sql;
		}
		
		$this->app =& Dataface_Application::getInstance();
		$this->_atts = array();
		$this->_atts['name'] =& $this->tablename;

		$this->_atts['label'] = (isset( $this->app->_tables[$this->tablename] ) ? $this->app->_tables[$this->tablename] : $this->tablename);
		
		$this->_permissions = Dataface_PermissionsTool::getRolePermissions($this->app->_conf['default_table_role']);
		
	}
	
	/**
	 * Loads a view.
	 * @param name The name of the view to load.
	 * @param sql Optional the sql that generates the view.
	 *  If trying to obtain an already initialized view then the second parameter
	 *  must be left blank or null.
	 */
	function &loadView($name, $sql=null){
		static $views = -1;
		if ( $views == -1 ){
			$views = array();
		}
		if ( isset($sql) and isset($views[$name]) ){
			return PEAR::raiseError('Attempt to create new view with name "'.$name.'" but another view by that name doesn\'t exist.  If you would like to obtain the existing view, then the second parameter to loadView must  be left blank or set null.');
		}
		
		if ( isset($sql) ){
			$view[$name] =& new Dataface_View($name, $sql);
		}
		return $views[$name];
	}
	
	
	/**
	 * Returns an associative array of field definitions for this view.
	 */
	function &fields(){
		// check the cache first.
		if ( isset($this->_cache[__FUNCTION__]) ){
			return $this->_cache[__FUNCTION__];
		}
		
		// it is not in the cache yet, let's calculate it.
		$out = array();
		$data = $this->_parseSQL();
		$this->_expandGlobs($data);
		$numCols = count($data['columns']);
		for ($i=0; $i<$numCols; $i++){
			$column = $data['columns'][$i];
			if ( $column['type']  == 'ident' ){
				// This column is just a normal column that is drawn from a table.
				// vs. the alternative which is a function.
				if ( $column['table'] ){
					$table =& $this->getTableOrView($column['table']);
				} else {
					if ( $column['alias'] ){
						$key = $column['alias'];
					} else {
						$key = $column['value'];
					}
					$tablename = $this->guessColumnTableName($key);
					$table =& $this->getTableOrView($tablename);
					
				}
				$out[$key] =& $table->getField($key);
				unset($table);
			} else {
				$out[$column['alias']] = Dataface_Table::_newSchema($column['type'],$column['alias'], $this->name);
			}
		}
		
		$this->_cache[__FUNCTION__] =& $out;
		$this->_fields =& $out;
		return $out;
		
		
	}
	
	/**
	 * Guesses the name of the table from where the given field comes.
	 */
	function guessColumnTableName($fieldname){
		if ( isset($this->_cache[__FUNCTION__][$fieldname] ) ) return $this->_cache[__FUNCTION__][$fieldname];
		$data = $this->_parseSQL();
		$this->_expandGlobs($data);
		
		$numTables = count($data['tables']);
		for ($i=$numTables-1; $i>=0; $i--){
			$curr = $data['tables'][$i];
			if ( $curr['type'] == 'ident' ){
				// This table is an actual table -- not a subselect
				$table =& Dataface_Table::loadTable($curr['value']);
				if ( $table->hasField($fieldname) ){
					if ( @$curr['alias'] ) return $curr['alias'];
					else return $curr['value'];
				}
				unset($table);
				
			} else if ($curr['type'] == 'subselect' ){
				// The table is a subselect
				$subview = new Dataface_View($curr['value'], $curr['value']);
				$field = $subview->getField($fieldname);
				if ( isset($field) ) return $curr['value'];
				unset($subview);
				unset($field);
				
			} else {
				trigger_error('Unspecified table type');
			}
		}
		
		
	}
	
	/**
	 * Returns a reference to the associative array that describes the specified field.
	 */
	function &getField($name){
		$fields =& $this->fields();
		if ( !isset($fields[$name]) ){
			return null;
		} else {
			return $fields[$name];
		}
	}
	
	/**
	 * Returns an associative array of the table objects associated with this view.
	 * Some of these tables may actually be Dataface_View objects (if the SQL query
	 * for this view has a subselect.
	 */
	function &tables(){
		if ( isset( $this->_cache[__FUNCTION__] ) ){
			return $this->_cache[__FUNCTION__];
		}
		
		// It is not in the cache yet, let's calculate it.
		$data = $this->_parseSQL();
		$out = array();
		$numTables = count($data['tables']);
		for ($i=0; $i<$numTables; $i++){
			$tableinfo = $data['tables'][$i];
			if ( $tableinfo['alias'] ) {
				$out[$tableinfo['alias']] =& $this->getTableOrView($tableinfo['alias']);
			} else if ( $tableinfo['type'] == 'ident' ) {
				$out[$tableinfo['value']] =& $this->getTableOrView($tableinfo['value']);
			} else {
				trigger_error("Problem getting tables for view '".$this->name."' because one of the tables in the query does not have an appropriate value.".Dataface_Error::printStackTrace(), E_USER_ERROR);
			}
		}
		
		$this->_cache[__FUNCTION__] =& $out;
		return $out;
	}
	
	function _expandGlobs(&$data){
		$new_columns = array();
		$numCols = count($data['columns']);
		for ($i=0; $i<$numCols; $i++){
			$column = $data['columns'][$i];
			if ( $column['type'] == 'glob' ){
				if ( $column['table'] ){
					$tables = array($column['table']);
				} else {
					$tables = array();
					foreach ( $data['tables'] as $index=>$table ){
						if ( isset($table['alias']) ){
							$tables[] = $table['alias'];
						} else {
							$tables[] = $table['value'];
						}
					}
				}
				
				foreach ($tables as $table){
					// this is a glob of the form tablename.* so we expand to all columns in that table.
					$t =& $this->getTableOrView($table);
					$fields =& $t->fields(); // Both Dataface_Table and Dataface_View supports the fields() method.
					foreach ( array_keys($fields) as $fieldname){
						$new_columns[] = array('type'=>'ident', 'table'=>$column['table'], 'value'=>$fieldname, 'alias'=>'');
					}
					unset($t);
					unset($fields);
				}
					
				
			} else {
				$new_columns[] = $column;
			}
		}
		
		$data['columns'] = $new_columns;
		return $data;
	}
	
	/**
	 * Returns either a Dataface_Table object or a DatafaceView object depending whether
	 * the table name is a subselect or a table identifier.
	 * @param name The name of the table in the SQL query. May also be the alias of the table.
	 *
	 */
	function &getTableOrView($name){
		if ( isset($this->_cache[__FUNCTION__][$name]) ){
			return $this->_cache[__FUNCTION__][$name];
		}
		
		// This value is not cached yet .. we must calculate it.
		$data = $this->_parseSQL();
		foreach ( $data['tables'] as $index=>$table ){
			if ( $table['alias'] == $name ){
				if  ( $table['type'] == 'subselect' ){
					$view =& new Dataface_View('unnamed_view',$table['value']);
					$this->_cache[__FUNCTION__][$name] =& $view;
					return $view;
				} else {
					$tableref =& Dataface_Table::loadTable($table['value']);
					$this->_cache[__FUNCTION__][$name] =& $tableref;
					return $tableref;
				}
			} else if ( $table['type'] == 'ident' and $table['value'] == $name ){
				$tableref =& Dataface_Table::loadTable($table['value']);
				$this->_cache[__FUNCTION__][$name] =& $tableref;
				return $tableref;
			}
		}
		return null;
	}
	
	
	/**
	 * Returns the parsed version of the sql for this view.
	 */
	function _parseSQL(){
		if ( !isset($this->sql_data) ){
			import('SQL/Parser.php');
			$parser =& new SQL_Parser(null,'MySQL');
			$this->sql_data = $parser->parse($this->sql);
		}
		return $this->sql_data;
	}
	
	/**
	 * Returns the string sql query that this view is based on.
	 */
	function _getSQL(){
		if ( !isset($this->sql) ){
			$compiler =& $this->_getCompiler();
			$this->sql = $compiler->compile($this->sql_data);
		}
		return $this->sql;
	}
	
	/**
	 * Obtains a reference to an SQL compiler for this view.
	 */
	function &_getCompiler(){
		if ( isset( $this->_cache[__FUNCTION__] ) ) return $this->_cache[__FUNCTION__];
		import('SQL/Compiler.php');
		$compiler = SQL_Compiler::newInstance('mysql');
		$compiler->version = 2;
		$this->_cache[__FUNCTION__] =& $compiler;
		return $compiler;
	}
	
	
	/**
	 * Returns the name of the table (or alias of the table) from which the given
	 * field originates.  In the case that this is just an alias, it may
	 * refer to a subselect.
	 */
	function getTableName($fieldname){
		if ( isset( $this->_cache[__FUNCTION__][$fieldname] ) ){
			// Let's check the cache first to see if we have already calculated this value.
			return $this->_cache[__FUNCTION__][$fieldname];
		}
		
		// It is not in the cache, let's check the sql data structure to see if it is in there.
		$data = $this->_parseSQL();
		$this->_expandGlobs($data);
		
		$column = null;
		foreach ($data['columns'] as $columninfo){
			if ( $fieldname == $columninfo['alias'] ) {
				$column = $columninfo;
				break;
			} else if ( !$columninfo['alias'] and $fieldname == $columninfo['value'] ){
				$column = $columninfo;
				break;
			}
		}
		if ( $column['type'] == 'ident' ){
			// The column is an identifier so we should be able to find its table.
			$tablename = null;
			if ( $column['table'] ) $tablename = $column['table'];
			else {
				// The table isn't defined, so we must guess the table.
				$tables =& $this->tables();
				foreach (array_keys($tables) as $key){
					$field = $tables[$key]->getField($fieldname);
					if ( !PEAR::isError($field) and $field ){
						$tablename = $key;
						break;
					}
					unset($field);
				}
			}
		} else {
			// The column is most likely a function result
			// there will be no table.
			return null;
		}
		$this->_cache[__FUNCTION__][$fieldname] = $tablename;
		return $tablename;
	}
	
	/**
	 * In the View we are using field aliases as their names.  This function 
	 * obtains the real field name from its source table.  Note that if its 
	 * source table is a subselect then this will return the alias of the field
	 * in the subselect and not necessary the absolute name of the field in
	 * the underlying table.
	 *
	 * In the case where the column is actually a function or an expression, this
	 * method will return null.
	 */
	function getRealFieldName($fieldname){
		if ( isset( $this->_cache[__FUNCTION__][$fieldname] ) ){
			// Let's check the cache first to see if we have already calculated this value.
			return $this->_cache[__FUNCTION__][$fieldname];
		}
		$data = $this->_parseSQL();
		$this->_expandGlobs($data);
		$column = null;
		
		foreach ($data['columns'] as $columninfo ){
			if ( $fieldname == $columninfo['alias'] ){
				//echo "$fieldname foudn";
				if ( $columninfo['type'] != 'ident' ){
					$out = null;
					break;
				} else {
					
					$out =  $columninfo['value'];
					break;
				}
			} else if ( !$columninfo['alias'] and $fieldname == $columninfo['value'] ){
				$out = $fieldname;
				break;
			}
		}
		
		$this->_cache[__FUNCTION__][$fieldname] = $out;
		return $out;
		
	}
	
	/**
	 * Reads a record from the database and returns it as a Dataface_ViewRecord
	 * object.
	 * @param $params Associative array of key/value pairs to search.
	 *
	 */
	function getRecord($params=array()){

		$data = $this->_parseSQL();
		$wrapper =& new SQL_Parser_wrapper($data);
		$where = array();
		foreach ( $params as $key=>$value ){
			$tablename = $this->getTableName($key);
			if ( isset($tablename) ) $where[] = '`'.addslashes($tablename).'`.`'.$key.'`=\''.addslashes($params[$key]).'\'';
		}
		$where = implode(' AND ', $where);
		$wrapper->addWhereClause($where);
		$compiler =& $this->_getCompiler();
		$sql = $compiler->compile($data);
		$db =& Dataface_DB::getInstance();
		$res = $db->query($sql);
		if ( PEAR::isError($res) ) return $res;
		if ( !$res ) return PEAR::raiseError(mysql_error($this->app->_db));
		if ( mysql_num_rows($res) == 0 ) return null;
		
		$vals = mysql_fetch_assoc($res);
		mysql_free_result($res);
		return $this->newRecord($vals);
		
	}
	
	
	
	/**
	 * Creates an empty Dataface_ViewRecord object.
	 */
	function newRecord($vals=null){
		return new Dataface_ViewRecord($this, $vals);
	}
	
	
	/**
	 * Overrides method from Table.  This method is not appropriate for views.
	 *
	 */
	function &getIndexes(){
		return array();
	}
	
	/**
	 * Overrides method from Table.  This method is not appropriate for views.
	 */
	function &getStatus(){
		return array();
	}
	
	/**
	 * Returns the update time as an SQL DateTime string.  The update time for
	 * a view is the maximum update time of the tables involved in the view.
	 */
	function getUpdateTime(){
		$max = 0;
		$tables =& $this->tables();
		foreach ( array_keys($tables()) as $tablename ){
			$max = max($max, strtotime($tables[$tablename]->getUpdateTime()) );
		}
		return date('Y-m-d H:i:s', $max);
	}
	
	/**
	 * Returns the creation time of this view.  This is essentially the maximum
	 * creation time of all of the tables involved in this view.
	 */
	function getCreateTime(){
		$max = 0;
		$tables =& $this->tables();
		foreach ( array_keys($tables()) as $tablename ){
			$max = max($max, strtotime($tables[$tablename]->getCreateTime()) );
		}
		return date('Y-m-d H:i:s', $max);
	}
	
	/**
	 * Overrides the getTranslations() method of Dataface_Table to include the 
	 * translations for any of the tables involved in this view.
	 */
	function &getTranslations(){
		if ( isset( $this->_cache[__FUNCTION__]) ) return $this->_cache[__FUNCTION__];
		$tables =& $this->tables();
		$translations = array();
		foreach ( array_keys($tables) as $tablename){
			$translations = array_merge($translations, $tables[$tablename]->getTranslations());
		}
		$this->_cache[__FUNCTION__] =& $translations;
		return $translations;
		
	}
	
	/**
	 *	Overrides the getTranslation() method of Dataface_Table.  Obtains an array
	 * of field names that have a translation in the given language.
	 * @param $name The 2-digit language code of the language of translation we 
	 * 				wish to obtain. e.g: en or fr
	 * @return array of field names.
	 */
	function &getTranslation($name){
		if ( isset( $this->_cache[__FUNCTION__][$name] ) ) return $this->_cache[__FUNCTION__][$name];
		$tables =& $this->tables();
		$columns = array();
		foreach ( array_keys($tables) as $tablename ){
			$columns = array_merge($columns, $tables[$tablename]->getTranslation($name));
		}
		$this->_cache[__FUNCTION__][$name] =& $columns;
		return $columns;
	}
	
	
	/**
	 * Overrides getDefaultValue() from Dataface_Table.  Returns the default value of a field.
	 */
	function getDefaultValue($fieldname){
		$field =& $this->getField($fieldname);
		$tablename = $this->getTableName($fieldname);
		if ( !isset($tablename) ) return null;
		$table =& $this->getTableOrView($tablename);
		$delegate =& $table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, $fieldname.'__default') ){
			return call_user_func(array(&$delegate, $fieldname.'__default'));
		} else if ( $field['Default'] ){
			return $field['Default'];
		} else {
			return null;
		}
	}
	
	/**
	 * Overrides Dataface_Table::keys().  Returns all of the keys from all of the 
	 * tables involved in this view.
	 */
	function &keys(){
		if ( isset($this->_cache[__FUNCTION__]) ) return $this->_cache[__FUNCTION__];
		$tables =& $this->tables();
		$out = array();
		foreach ( array_keys($tables) as $tablename){
			$keys =& $tables[$tablename]->keys();
			foreach ( array_keys($keys) as $fieldname){
				$out[$fieldname] =& $keys[$fieldname];
			}
		}
		$this->_cache[__FUNCTION__] =& $out;
		return $out;
		
	}
	
	/**
	 * Takes an associative array of key values for values in this view record,
	 * and outputs an associative arry of key values for values in an underlying
	 * table.  If the key has to be translated to the field name of the underlying
	 * table, then this will take care of it.
	 * @param $vals Associative array of key/value pairs where the keys are field
	 *				names in this view record.
	 * @param $tablename The alias name of an underlying table that the fields are being
	 *				mapped to.
	 */
	function mapValuesToTable($vals, $tablename){
		if ( !isset($vals) ) return null;
		$out = array();
		foreach ($vals as $key=>$value){
			if ( $tablename != $this->getTableName($key) ) continue;
			$out[ $this->getRealFieldName($key) ] = $value;
		}
		return $out;
	}
	
	/**
	 * Checks the given values against this View to make sure that it does not
	 * conflict with the view.  It is possible for values to cause a record to
	 * fall outside of a view.  For example the view based on:
	 * select * from Profiles p left join Properties pp on p.id=pp.ProfileID
	 * if id != ProfileID then the resulting record will not be in the view.
	 * Hence, it is important for id to be equal to ProfileID
	 */
	function checkSatisfiability($values=null){
		$data = $this->_parseSQL();
		$where =& $data['where_clause'];
		
		
	}
	
	
	

}
