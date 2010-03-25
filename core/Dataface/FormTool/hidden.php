<?php
class Dataface_FormTool_hidden {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$factory =& Dataface_FormTool::factory();
		$el =& $factory->addElement('hidden', $field['name']);
		if ( PEAR::isError($el) ) {
		
			echo "Failed to get element for field $field[name] of table ".$record->_table->tablename;
			echo "The error returned was ".$el->getMessage();
			echo Dataface_Error::printStackTrace();
		}
		$el->setFieldDef($field);
		return $el;
	}
}
