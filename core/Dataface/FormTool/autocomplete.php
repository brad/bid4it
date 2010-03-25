<?php

class Dataface_FormTool_autocomplete {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$widget =& $field['widget'];
		$options =& Dataface_FormTool::getVocabulary($record, $field);
		$el =& $form->addElement('autocomplete', $formFieldName, $widget['label'], array('class'=>$widget['class'], 'id'=>$field['name']) );
		$el->setOptions($options);
		return $el;
	}
}
