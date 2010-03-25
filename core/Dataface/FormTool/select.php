<?php


class Dataface_FormTool_select {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$table =& $record->_table;
		
		$widget =& $field['widget'];
		$factory = Dataface_FormTool::factory();
		$attributes = array('class'=>$widget['class'], 'id'=>$field['name']);
		if ( $field['repeat'] ){
			$attributes['multiple'] = true;
			$attributes['size'] = 5;
		}
		$options = $record->_table->getValuelist($field['vocabulary']);//Dataface_FormTool::getVocabulary($record, $field);
		if ( !isset( $options) ) $options = array();
		$emptyOpt = array(''=>df_translate('scripts.GLOBAL.FORMS.OPTION_PLEASE_SELECT',"Please Select..."));
		$opts = $emptyOpt;
		foreach($options as $kopt=>$opt){
			$opts[$kopt] = $opt;
		}
		
		$el =  $factory->addElement('select', $formFieldName, $widget['label'], $opts, $attributes  );
		//$el->setFieldDef($field);
		//return $el;
		return $el;
	}
	
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		// quickform stores select fields as arrays, and the table schema will only accept 
		// array values if the 'repeat' flag is set.
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		//$formFieldName =& $element->getName();
		
		if ( !$field['repeat'] ){
		
			$val = $element->getValue();
			
			if ( count($val)>0 ){
				return $val[0];
				
			} else {
				return null;
				
			}
		} else {
			return $element->getValue();
		}
			
		
		
	}
	
	
}
