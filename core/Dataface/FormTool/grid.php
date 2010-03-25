<?php
//error_reporting(E_ALL);
//ini_set('display_errors','on');
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['grid'] = array('HTML/QuickForm/grid.php', 'HTML_QuickForm_grid');

class Dataface_FormTool_grid {
	
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		/*
		 *
		 * This field uses a table widget.
		 *
		 */
		//$field['display'] = 'block';
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$factory =& Dataface_FormTool::factory();
		$widget =& $field['widget'];
		$el =& $factory->addElement('grid',$formFieldName, $widget['label']);
		
		if ( isset($field['relationship']) ){
			$relationship =& $table->getRelationship($field['relationship']);
			if ( isset( $widget['columns'] ) ){
				$columns = array_map('trim',explode(',',$widget['columns']));
			} else {
				$columns = $relationship->_schema['short_columns'];
			}
			$count=0;
			$subfactory = new HTML_QuickForm();
			foreach ($columns as $column){
				$colTable =& $relationship->getTable($column);
				if ( !$colTable ) echo "Could not find table for column $column";
				$dummyRecord =& new Dataface_Record($colTable->tablename, array());
				
				$colFieldDef =& $colTable->getField($column);
				
				$columnElement =& $formTool->buildWidget($dummyRecord, $colFieldDef, $subfactory, $column, false);
				$defaultValue = $colTable->getDefaultValue($column);
				
				$columnElement->setValue($defaultValue);
				
				$el->addField($colFieldDef, $columnElement);
				
				if ( !$record->checkPermission('delete related record', array('relationship'=>$field['relationship']))){
					$el->delete=false;
				}
				if ( !$record->checkPermission('add new related record', array('relationship'=>$field['relationship']))){
					$el->addNew=false;
				}
				$orderCol = $relationship->getOrderColumn();
				if ( !PEAR::isError($orderCol) ){ $el->reorder=false;}
				
				unset($columnElement);
				unset($colFieldDef);
				unset($dummyRecord);
				unset($colTable);
			}
			
		}

		else if ( isset($widget['fields']) ){
			$widget_fields =& $widget['fields'];
			foreach ($widget_fields as $widget_field){
				$widget_field =& Dataface_Table::getTableField($widget_field, $this->db);

				if ( PEAR::isError($widget_field) ){
					return $widget_field;
				}
				
				$widget_widget = $formTool->buildWidget($record, $widget_field, $factory, $widget_field['name']);
				$defaultValue = $table->getDefaultValue($widget_field['name']);
				
				$widget_widget->setValue($defaultValue);
				$el->addField($widget_widget);
			}
		} else if ( isset($field['fields']) ){
			foreach ( array_keys($field['fields']) as $field_key){
				$widget_widget = $formTool->buildWidget($record, $field['fields'][$field_key], $factory, $field['fields'][$field_key]['name']);
				$defaultValue = $table->getDefaultValue($widget_field['name']);
				
				$widget_widget->setValue($defaultValue);
				$el->addField($widget_widget);
				unset($widget_widget);
			
			}
		}

		return $el;
	}
	
	function pullValue(&$record, &$field, &$form, &$element, $new=false){
		$val = $record->getValue($field['name']);
		
		return $val;
	}
	
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		$val = $element->getValue();
		
		$last_id=-1;
		foreach ( $val as $key=>$row ){
			if (is_array($row) and isset($row['__id__']) and ($row['__id__'] == 'new') ){
				$last_id = $key;
			}
			
		}
		
		if ( $last_id != -1 ) unset($val[$last_id]);
		
		return $val;
	}
	
	
	
}
