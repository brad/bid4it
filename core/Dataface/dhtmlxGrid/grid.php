<?php
/**
 * A class for generating a dhtmlxGrid to display/edit a set of Record or
 * RelatedRecord objects.
 *
 * <h3>Usage:</h3>
 * <code>
 * $grid = new Dataface_dhtmlxGrid_grid($s->fields(), $records);
 * if ( $_GET['--dhtmlxGrid_xml'] ){
 * 	if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
 *   		header("Content-type: application/xhtml+xml"); } else {
 *   		header("Content-type: text/xml");
 * 	}
 * 	echo("<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n"); 
 * 	
 * 	echo $grid->toXML();
 * 	
 * } else {
 * 	echo "<html><body>";
 * 	echo $grid->toHTML();
 * 	echo "</body></html>";
 * 
 * }
 * </code>
 */
class Dataface_dhtmlXGrid_grid {

	var $columns;
	var $data;
	var $name;
	var $ids;
	var $changes;
	/**
	 * The id of the parent record (if this contains a bunch of related
	 * records) or the name of a table ( if this just contains a bunch of
	 * records.
	 */
	var $parent_id;
	
	
	/**
	 * A cache to cache temp data.
	 */
	var $_cache;
	
	
	/**
	 * @var string The name of the relationship that records in this grid
	 * 		belong to.  This will only be set if the parent_id is actually
	 * 		a record and not a table.
	 */
	var $relationship;

	
	/**
	 * Flags to switch things on and off.
	 */
	var $xmlConfig = false; // whether configure the grid via XML
	var $xmlUrl = null;
	
	
	/**
	 * Creates a new data grid with the specified columns and data.
	 * @param array $columns An array of field definitions from Dataface.
	 * @param array $data An array of Dataface_Record or Dataface_RelatedRecord
	 *              objects that should be displayed in the grid.
	 */
	function Dataface_dhtmlXGrid_grid($columns, $data=array(), $name='mygrid', $parent_id=null, $relationship=null){
		
		$this->name = $name;
		$this->xmlUrl = $_SERVER['PHP_SELF'];
		$this->parent_id=$parent_id;
		$this->relationship=$relationship;
		$firstDataRow = reset($data);
		$table = null;
		if ( $firstDataRow && is_a($firstDataRow, 'Dataface_Record') ){
			$table = $firstDataRow->_table->tablename;
			$this->parent_id = $table;

		}
		$this->setColumns($columns, $table);
		$this->setData($data);
		
		if ( $firstDataRow && is_a($firstDataRow, 'Dataface_RelatedRecord') ){
			$relationship =& $firstDataRow->_relationship;
			$this->relationship = $relationship->getName();
			$parent_record =& $firstDataRow->getParent();
			$this->parent_id = $parent_record->getId();

			foreach (array_keys($this->columns) as $cname){
				$curr_table =& $relationship->getTable($cname);
				$this->columns[$cname]['table'] = $curr_table->tablename;
				unset($curr_table);
			}
		}
		$this->buildIds($data);
	}
	
	function buildIds(&$records){
		$this->ids = array();
		
		$index = 0;
		foreach ( array_keys($this->data) as $index){
			if ( isset($records[$index]) ){
				$this->ids[$index] = $records[$index]->getId();
			} else {
				$this->ids[$index] = '__new__';
			}
		}
		

	}
	
	/**
	 * Checks if a particular row/column is editable.
	 * @param int $row The row index.
	 * @param string $column The name of the column (not the index).
	 * @returns boolean Whether this cell is editable.
	 */
	function isEditable($row, $column){
		$parentObj =& $this->getParentObject();
		
		if ( $this->ids[$row] == '__new__'){
			// we are looking for the add permission
			echo "Parent is a ".get_class($parentObj);
			if ( is_a($parentObj, 'Dataface_Table') ){
				$res = $parentObj->checkPermission('edit', array('field'=>$column));
				return $res;
			} else if ( is_a($parentObj, 'Dataface_Record') ){
				$permissions =& $parentObj->_table->getRelationshipPermissions($this->relationship);
				if ( isset($permissions['add new related record']) and $permissions['add new related record'] ){
					// We are allowed to add a new related record, so we will create a mask to allow this.
					$mask=array('edit'=>1, 'new'=>1);
				}
				$rel =& $parentObj->_table->getRelationship($this->relationship);
				$t =& $rel->getTable($column);
				
				$res = df_check_permission('edit', $t, array('field'=>$column, 'recordmask'=>$mask));
				return $res;
				
			}
		}
		else {
			
			$record =& $this->getRowRecord($row);
			return $record->checkPermission('edit', array('field'=>$column));
		}
		 
	}
	
	/**
	 * Returns the Dataface_Record or Related record object that represents a row of the grid.
	 * @param int $row The row index.
	 * @returns mixed Dataface_Record or Dataface_RelatedRecord or null if this is a new row.
	 *
	 */
	function &getRowRecord($row){
		if ( !isset($this->_cache) ) $this->_cache = array();
		if ( !isset($this->_cache['records']) ) $this->_cache['records'] = array();
		$id = $this->ids[$row];
		if ( $id == '__new__' ){
			$null = null;
			return $null;
		}
		else {
			if ( !isset($this->_cache['records'][$id]) ) $this->_cache['records'][$id] =& df_get_record_by_id($id);
		}
		return $this->_cache['records'][$id];
	}
	
	
	function &getParentObject(){
		if ( !isset($this->parent_record) ){
			$null = null;
			if ( !isset($this->parent_id) ) return $null;
			
			if ( strpos($this->parent_id, '?') !== false ){
				$this->parent_record =& df_get_record_by_id($this->parent_id);
			} else {
				$this->parent_record =& Dataface_Table::loadTable($this->parent_id);
			}
		}
		return $this->parent_record;
		
		
	}
	
	function __sleep(){
		//unset($this->parent_record);
		//unset($this->_cache);
		$vars = array_keys(get_object_vars($this));
		unset($vars['parent_record']);
		unset($vars['_cache']);
		return $vars;
	}
	
	
	
	
	
	/**
	 * Sets the columns for this grid.
	 * @param array $columns Array of field definitions.
	 * @param string $table Optional name of the table for this grid.
	 */
	function setColumns($columns, $table=null){
		$firstRow = reset($columns);
		if ( $firstRow && is_array(@$firstRow['widget']) && @$firstRow['widget']['label'] ){
			$new_columns = array();
			foreach (array_keys($columns) as $cname){
				$new_columns[$cname] = array(
					'field'=> array(
						'name'=>$columns[$cname]['name'],
						'Type'=>$columns[$cname]['Type'],
						'widget'=>array('type'=>$columns[$cname]['widget']['type'], 'label'=>$columns[$cname]['widget']['label']),
						'vocabulary'=>$columns[$cname]['vocabulary'],
						'column'=>$columns[$cname]['name'],
						'field'=>$columns[$cname]['name'],
						'table'=>$columns[$cname]['table']
						),
					'table'=>$columns[$cname]['table']
				);
			}
			unset($columns);
			$columns =& $new_columns;
		}
		$this->columns = $columns;
	}
	
	/**
	 * Sets the data for this grid.
	 * @param array $data An array of Dataface_Record or Dataface_RelatedRecord objects.
	 */
	function setData($data){
		$firstRow = reset($data);
		if ( $firstRow && (is_a($firstRow,'Dataface_Record') || is_a($firstRow,'Dataface_RelatedRecord') ) ){
			$new_data = array();
			foreach ( $data as $id=>$record){
				$new_data[$id] = $record->strvals(array_keys($this->columns));
			}
			$nex = count($new_data);
			$new_data[$nex] = array();
			foreach(array_keys($this->columns) as $col){
				$new_data[$nex][$col] = null;
			}
			
			$data = $new_data;
		}
		$this->data = $data;
		
	}
	
	
	/**
	 * Returns the XML configuration for a grid.  This should generally be used in 
	 * the back-end to feed the grid.  Use toHTML() for the front-end portion of the
	 * grid.
	 * @returns XML string representing the grid data.
	 */
	function toXML(){
		$out = "<rows>\n";
		if ( $this->xmlConfig ){
			// We are using XML configuration so we display the column information in the headers
			$out .= "<head>";
			foreach ( $this->columns as $key=>$column ){
				$out .= $this->columnToXML($key)."\n";
			}
			$out .= "<settings>\n<colwidth>%</colwidth></settings>\n";
			$out .= "</head>\n";
		}
		
		
		foreach ($this->data as $id=>$row){
			$out .= "<row id=\"{$id}\">\n";
			foreach ($row as $key=>$value){
				$out .= $this->cellToXML($key, $value)."\n";
			}
			$out .= "</row>\n";
		}
		$out .= "</rows>";
		
		return $out;
		
	
	}
	
	function printComboboxInitializationHtml(){
		$out = array();
		$out[] = "var cb = [];";
		foreach ( array_keys($this->columns) as $idx=>$colname){
			//$out[] = "In column $colname";
			$fieldDef =& $this->columns[$colname]['field'];
			//print_r($this->columns[$colname]);
			if ( @$fieldDef['vocabulary'] ){
				$out[] = "cb[{$idx}] = {$this->name}.getCombo({$idx});";
				$table =& Dataface_Table::loadTable($this->columns[$colname]['table']);
				$valuelist = $table->getValuelist($fieldDef['vocabulary']);
				$options = array();
				foreach ($valuelist as $key=>$value){
					$out[] = "cb[{$idx}].put('{$key}', '".addslashes($value)."');";
					//$options[] = "<option value=\"{$key}\">{$value}</option>";
				}
				
			}
			unset($table);
			unset($fieldDef);
			unset($valuelist);
		}
		return implode("\n", $out);
	
	}
	
	/**
	 * Returns the HTML to display the grid.
	 */
	function toHTML(){
		$dhtmlx_path = DATAFACE_URL;
		$js_path = DATAFACE_URL;
		if ( $dhtmlx_path{strlen($dhtmlx_path)-1} != '/' ) $dhtmlx_path .= '/';
		if ( $js_path{strlen($js_path)-1} != '/' ) $js_path .= '/';
		
		
		$dhtmlx_path .= 'lib/dhtmlxGrid/';
		if ( in_array('calendar',$this->getColTypes())){
			$calendar_inc = "<script  src=\"{$dhtmlx_path}js/dhtmlXGrid_excell_calendar.js\"></script>";
		} else {
			$calendar_inc = '';
		}
		$session_id = session_id();
		$out = <<<END
		<link rel="STYLESHEET" type="text/css" href="{$dhtmlx_path}css/dhtmlXGrid.css">
		<script>_css_prefix="{$dhtmlx_path}css/"; _js_prefix="{$dhtmlx_path}js/"; </script>
				<script src="{$dhtmlx_path}js/dhtmlXCommon.js"></script> 
				<script src="{$dhtmlx_path}js/dhtmlXGrid.js"></script> 
				<script src="{$dhtmlx_path}js/dhtmlXGridCell.js"></script>
				<script><!--
				var DATAFACE_URL = '{$js_path}';
				//--></script>
				<script src="{$js_path}js/ajax.js"></script>
				
				<script src="{$js_path}Dataface/dhtmlxGrid/dhtmlXGridHandler.js"></script>
				{$calendar_inc}
	
				<div id="{$this->name}" style="width: 400px; height: 400px"></div>
				<script language="javascript" type="text/javascript"><!--
					{$this->name} = new dhtmlXGridObject('{$this->name}');
					{$this->name}.setImagePath("{$dhtmlx_path}lib/dhtmlxGrid/imgs/");

END;
		if ( isset($this->id) ){
			$out .= "{$this->name}.setUserData(null,'id','{$this->id}');\n";
		}

		if ( !$this->xmlConfig ){
			$out .= "{$this->name}.setHeader(\"".implode(",", array_keys($this->columns))."\");\n";
			$out .= "{$this->name}.setColTypes(\"".implode(",", $this->getColTypes())."\");\n";
			$out .= $this->printComboboxInitializationHtml();
			$out .= "{$this->name}.setInitWidths(\"".implode(",",$this->getColWidths())."\");\n";
			$out .= "{$this->name}.init();\n";
			//$out .= "{$this->name}.addRow(".count($this->data).");\n";
			
			
		
		}
		$url = $this->getXMLURL();
		$out .= <<<END
					{$this->name}.loadXML("{$url}");
					{$this->name}.setOnEditCellHandler(onGridCellEdit);
				//--></script>
		
END;
		return $out;
	}
	
	/**
	 * Returns the column types for this grid (see http://scbr.com/docs/products/dhtmlxGrid/doc/index.html)
	 * for more information about the column types.
	 * @returns array Array of string column types.
	 */
	function getColTypes(){

		return array_map(array(&$this, 'getType'), array_keys($this->columns));
		
	}
	
	/**
	 * Returns the column widths for this grid.
	 * @returns array of strings
	 */
	function getColWidths(){
		$widths = array();
		foreach ( array_keys($this->columns) as $col){
			$widths[] = 100;
		}
		return $widths;
	}
	
	/**
	 * Returns the URL for the XML data feed for this grid.  By default, this 
	 * will just append &--dhtmlxGrid_xml=1 to the end of the current page url.
	 */
	function getXMLURL(){
		return DATAFACE_SITE_HREF.'?-action=load_grid&-gridid='.$this->id;
	}
	
	
	/**
	 * Outputs the XML configuration for a given column.
	 * @param string $columnName The name of the column.
	 */
	function columnToXML($columnName){
		$column = $this->columns[$columnName];
		$options = array();
		if ( isset($column['field']) ){
			$fieldDef = $column['field'];
			$width = ((isset($fieldDef['column']) and @$fieldDef['column']['width']) ? $fieldDef['column']['width'] : null);
			$title = $fieldDef['widget']['label'];
			$align = ((isset($fieldDef['column']) and @$fieldDef['column']['align']) ? $fieldDef['column']['align'] : 'left');
			$type = $this->getType($columnName);
			if ( strpos(strtolower($fieldDef['Type']), 'text') !== false || strpos(strtolower($fieldDef['Type']), 'char') !== false || @$fieldDef['vocabulary'] ) {
				$sort = 'str';
			} else if ( strpos(strtolower($fieldDef['Type']), 'date') !== false ){
				$sort = 'date';
			} else {
				$sort = 'int';
			}
			
			if ( @$fieldDef['vocabulary'] and isset($column['table'])){
				$table =& Dataface_Table::loadTable($column['table']);
				$valuelist = $table->getValuelist($fieldDef['vocabulary']);
				$options = array();
				foreach ($valuelist as $key=>$value){
					$options[] = "<option value=\"{$key}\">{$value}</option>";
				}
			}
			
		} else {
		
			$width = ( isset($column['width']) ? $column['width'] : null );
			$title = ( isset($column['title'] ) ? $column['title'] : '');
			$align = ( isset($column['align'] ) ? $column['align'] : 'left');
			$type = $this->getType($columnName);
			$sort = ( isset($column['sort']) ? $column['sort'] : 'str');
		
		}
		
		return "<column width=\"80\" type=\"{$type}\" ".
				( $width ? "width=\"{$width}\" " : '').
				"align=\"{$align}\" sort=\"{$sort}\">{$columnName}".implode("\n", $options)."</column>";
		
	
	}
	
	/**
	 * Returns the type (see http://scbr.com/docs/products/dhtmlxGrid/doc/index.html)
	 * of the given column.
	 * @param string $columnName The name of the column type.
	 * @returns string
	 */
	function getType($columnName){
		$column = $this->columns[$columnName];
		if ( isset($column['field']) ){
			$fieldDef = $column['field'];
			if ( !is_array($fieldDef) ){
				echo Dataface_Error::printStackTrace();
			}
			$type = $fieldDef['widget']['type'];
		} else {
			$type = $column['widget']['type'];
		}
		
		switch ($type){
			case 'checkbox':
				if ( !@$fieldDef['repeat'] ) return 'ch';
				return 'ro';
			case 'select':
				return 'coro';
			case 'combobox':
			case 'autocomplete':
				return 'co';
			case 'file':
				return 'img';
			case 'textarea':
			case 'htmlarea':
				return 'txt';
			case 'text':
				return 'ed';
			case 'date':
			case 'calendar':
				return 'calendar';
			default:
				return 'ro';
		
		}
	}
	
	/**
	 * Outputs the XML for a specific cell.
	 * @param string $key The name of the column.
	 * @param string $value The value to display.
	 */
	function cellToXML($key, $value){
		$column = $this->columns[$key];
		if ( isset($column['field']) ){
			$fieldDef = $column['field'];
			$type = strtolower($fieldDef['Type']);
		} else {
			$type = 'varchar';
		}
		
		if ( strpos($type,'text') !== false ){
			return "<cell><![CDATA[{$value}]]></cell>";
		} else {
			return "<cell>".htmlspecialchars($value)."</cell>";
		}
	}
	
	/**
	 * Sets the value of a cell, of a row given the zero-based row and column
	 * indices.
	 * @param int $row The zero-based row index of the row that is set.
	 * @param int $col_index The zero-based column index that is being set.
	 * @param string $value The Value that is being set.
	 *
	 */
	function setCellValue($row,$col_index,$value){
		$colnames = array_keys($this->columns);
		$this->data[$row][$colnames[$col_index]] = $value;
		$this->trackChange($row, $colnames[$col_index]);
	}
	
	
	/**
	 * Sets the row values for the grid.
	 * @param int $row The zero-based row index.
	 * @param array $values The array of values to be set.  Uses numerical
	 *			indices - not column names.
	 */
	function setRowValues($row, $values){
		$colnames = array_keys($this->columns);
		if ( !isset($this->ids[$row]) ){
			// This must be a new row..
			$this->ids[$row] = '__new__';
			$this->data[$row] = array();
			foreach ($colnames as $key){
				$this->data[$row][$key] = null;
			}
			
		}
		//$parentObject =& $this->getParentObject();
		//$record = df_get_record_by_id($this->ids[$row]);

		$changed = array();
		foreach ($values as $idx=>$val){
			if ( !isset($val) ) continue;
			if ( $this->data[$row][$colnames[$idx]] != $val ){
				if (!$this->isEditable($row, $colnames[$idx])){
					print_r($this->ids);
					echo "Row: $row";print_r($values);
					return PEAR::raiseError('You do not have permission to set the value on the "'.$colnames[$idx].'" field for this record.');
				}
				
				$changed[] = $colnames[$idx];
			
				$this->data[$row][$colnames[$idx]] = $val;
			}
		}
		$this->trackChange($row, $changed);
	}
	
	function trackChange($row, $cells=array()){
		
		if ( !isset($this->changes) ) $this->changes = array();
		if ( !is_array($cells) ){
			$cells = array($cells);
		}
		$rowChange =& $this->getChange($row);
		if ( !isset($rowChange) ){
			$rowChange = new Dataface_dhtmlXGrid_grid_action($row,$this->ids[$row],'update',array('cells'=>$cells)); 
		}
		
		
		
		if ( !isset($rowChange->params['cells']) ) $rowChange->params['cells'] = array();
		$rowChange->params['cells'] = array_merge($rowChange->params['cells'], $cells);
		
		$this->changes[$row] = $rowChange;
	}
	
	function getChange($row){
		return @$this->changes[$row];
	}
	
	
	function commit(){
		foreach ( $this->changes as $change ){
			$change->commit($this);
		}
		$this->changes = array();
	}


}


/**
 * A metadata class to store a single action performed on a grid.
 * The idea is that each time a grid is updated, it tracks the update
 * with an action item so that when it comes time to commit the changes,
 * these update records can be used to describe what needs to be done.
 */
class Dataface_dhtmlXGrid_grid_action {
	/**
	 * The row id of the row in the grid that is being updated.
	 * @var int
	 */
	var $rowid;
	
	/**
	 * The record id of the Dataface_Record that is to be updated.
	 * This is the result of a call to getId() (The Dataface_Record class).
	 * Note that this record id may refer to related records or records.  
	 * Related record ids will have a slightly different form.  See 
	 * Dataface_RelatedRecord::getId() for more information.
	 * @var string
	 */
	var $recordid;
	
	/**
	 * The name of the action that was performed.
	 * Possible values include:
	 *	update
	 *	delete
	 *  remove
	 *	move
	 */
	var $action;
	
	
	/**
	 * If the action requires some parameters they will be stored in
	 * this array.  For example, if the action was 'move', then we will want
	 * to know where it was moved to.
	 */
	var $params=array();
	
	
	function Dataface_dhtmlXGrid_grid_action($rowid,$recordid,$action, $params=array()){
		$this->rowid = $rowid;
		$this->recordid = $recordid;
		$this->action = $action;
		$this->params = $params;
	}
	
	function commit(&$grid){

		$columnnames = array_keys($grid->columns);
		if ( $this->recordid == '__new__' ){
			// this is a new record - so we must create a new one.
			$parentObj =& $grid->getParentObject();
			if ( is_a($parentObj, 'Dataface_Table') ){
				$record = new Dataface_Record($parentObj->tablename, array());
			} else {
				$record = new Dataface_RelatedRecord($parentObj, $grid->relationship, array());
			}
		} else {
			$record =& df_get_record_by_id($this->recordid);
		}
		$rowdata =& $grid->data[$this->rowid];
		$savedata = array();
		foreach ( $this->params['cells'] as $key){
			$savedata[$key] = $rowdata[$key];
		}
		$record->setValues($savedata);
		
		
		if ( $this->recordid == '__new__' and is_a($record, 'Dataface_RelatedRecord')){
			import('Dataface/IO.php');
			
			$io =& new Dataface_IO($parentObj->_table->tablename);
			$io->addRelatedRecord($record);
		} else {
			$record->save();
		}

	}


}
