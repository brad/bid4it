<?php 

class Dataface_CopyTool {
	var $warnings;

	function &getInstance(){
		static $instance = 0;
		if ( !is_object($instance) ){
			$instance = new Dataface_CopyTool();
		}
		return $instance;
	}
	
	function copy($record, $vals=array(), $force=true){
		$this->warnings = array();
		// Step 1: Load the record - it has been passed
		// Step 2: build sql query to copy the record
		$query = $this->buildCopyQuery($record, $vals, $force);
		if ( PEAR::isError($query) ){
			return $query;
		}
		$res = df_query($query);
		if ( !$res ){
			return PEAR::raiseError("Failed to copy record '".$record->getTitle()."' due to an SQL error:".mysql_error());
		}
		if ( PEAR::isError($res) ) return $res;
		if ( $auto_field_id = $record->_table->getAutoIncrementField()) {
			$insert_id = df_insert_id();
			$copied =& df_get_record($record->_table->tablename, array($auto_field_id=>$insert_id));
			return $copied;
		} else {
			return new Dataface_Record($record->_table->tablename, array_merge($record->vals(), $vals));
		}
		
	}
	
	/**
	 * Builds an SQL query to copy the given record.  This honours permissions
	 * and will only copy columns for which 'view' access is available in the
	 * source record and 'edit' access is available in the destination record.
	 *
	 * Individual column failures (due to permissions) are recorded in the 
	 * $warnings variable of this class.  It will be an array of Dataface_Error
	 * objects.
	 *
	 * @param Dataface_Record $record The record being copied.
	 * @param array $valls Values that should be placed in the copied version.
	 * @param boolean $force If true this will perform the copy despite individual
	 *			column warnings.
	 * @returns string The SQL query to copy the record.
	 */
	function buildCopyQuery($record,$vals=array(), $force=true){
		
		$dummy = new Dataface_Record($record->_table->tablename, $vals);
		if ( !$record->checkPermission('view') || !$dummy->checkPermission('edit') ){
			return Dataface_Error::permissionDenied("Failed to copy record '".$record->getTitle()."' because of insufficient permissions.");
		}
		
		$copy_fields = array_keys($record->_table->fields());
		
		// Go through each field and see if we have copy permission.
		// Copy permission is two-fold: 1- make sure the source is viewable
		//								2- make sure the destination is editable.
		$failed = false;
		foreach ($copy_fields as $key=>$fieldname){
			if ( !$record->checkPermission('view', array('field'=>$fieldname))
				|| !$dummy->checkPermission('edit', array('field'=>$fieldname)) ){
				$this->warnings[] = Dataface_Error::permissionDenied("The field '$fieldname' could not be copied for record '".$record->getTitle()."' because of insufficient permissions.");
				unset($copy_fields[$key]);
				$failed = true;
			}
		}
		
	
		// If we are not forcing completion, any failures will result in cancellation
		// of the copy.
		if ( !$force and $failed ){
			return Dataface_Error::permissionDenied("Failed to copy the record '".$record->getTitle()."' due to insufficient permissions on one or more of the columns.");
		}
		
		// We don't copy auto increment fields.
		$auto_inc_field = $record->_table->getAutoIncrementField();
		if ( $auto_inc_field ){
			$key = array_search($auto_inc_field, $copy_fields);
			if ( $key !== false ) unset($copy_fields[$key]);
		}
		
		// Now we can build the query.
		$sql = array();
		$sql[] = "insert into `".$record->_table->tablename."`";
		$sql[] = "(`".implode('`,`', $copy_fields)."`)";
		
		$copy_values = array();
		foreach ($copy_fields as $key=>$val){
			if ( isset($vals[$val]) ){
				$copy_values[$key] = "'".addslashes($dummy->getSerializedValue($val))."' as `$val`";
			} else {
				$copy_values[$key] = "`".$val."`";
			}
		}
		$sql[] = "select ".implode(', ', $copy_values)." from `".$record->_table->tablename."`";
		$qb =& new Dataface_QueryBuilder($record->_table->tablename);
		
		$keys = array_keys($record->_table->keys());
		$q = array();
		foreach ($keys as $key_fieldname){
			$q[$key_fieldname] = $record->strval($key_fieldname);
		}
		$where = $qb->_where($q);
		$where = $qb->_secure($where);
		$sql[] = $where;
		return implode(' ', $sql);
		
		
	}
}
