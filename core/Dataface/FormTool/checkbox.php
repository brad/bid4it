<?php
class Dataface_FormTool_checkbox {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$table =& $record->_table;
		$widget =& $field['widget'];
		
		if ( !@$widget['separator'] ) $widget['separator'] = '<br />';
		$factory =& Dataface_FormTool::factory();
		if ( isset( $field['repeat']) and $field['repeat'] and isset($field['vocabulary']) and $field['vocabulary']){
			$boxes = array();
			$options =& Dataface_FormTool::getVocabulary($record, $field);
			$options__classes =& Dataface_FormTool::getVocabularyClasses($record, $field);
			foreach ($options as $opt_val=>$opt_text){
				if ( !$opt_val ) continue;
				$boxes[] =& HTML_QuickForm::createElement('checkbox',$opt_val , null, $opt_text, array('class'=>'checkbox-of-'.$field['name'].' '.@$options__classes[$opt_val]));
				//$boxes[count($boxes)-1]->setValue($opt_val);
				
			}
			$el =& $factory->addGroup($boxes, $field['name'], $widget['label']);
		} else {
			
			
			
			$el =& $factory->addElement('advcheckbox', $formFieldName, $widget['label']);
			if ( $field['vocabulary'] ){
				$yes = '';
				$no = '';
				if ( $table->isYesNoValuelist($field['vocabulary'], $yes, $no) ){
					$el->setValues(array($no,$yes));
				}
			}
		}
		return $el;
	}
	
	function &pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$formFieldName = $element->getName();
		
		$val = $element->getValue();
		if ( $field['repeat'] ){
			
			//print_r(array_keys($val));
			// eg value array('value1'=>1, 'value2'=>1, ..., 'valueN'=>1)
			if ( is_array($val) ){
				$out = array_keys($val);
			} else {	
				$out = array();
			}
			//$res =& $s->setValue($fieldname, array_keys($val));
		} else {
			$out = $val;
			//$res =& $s->setValue($fieldname, $val);
		}
		if (PEAR::isError($res) ){
			$res->addUserInfo(
				df_translate(
					'scripts.Dataface.QuickForm.pushValue.ERROR_PUSHING_VALUE',
					"Error pushing value for field '$field[name]' in QuickForm::pushWidget() on line ".__LINE__." of file ".__FILE__,
					array('name'=>$field['name'],'file'=>__FILE__,'line'=>__LINE__)
					)
				);
			return $res;
		}
		return $out;
	}
	
	function pullValue(&$record, &$field, &$form, &$element, $new=false){
		
		/*
		 *
		 * Checkbox widgets store values as associative array $a where
		 * $a[$x] == 1 <=> element named $x is checked.
		 * Note:  See _buildWidget() for information about how the checkbox widget is 
		 * created.  It is created differently for repeat fields than it is for individual
		 * fields.  For starters, individual fields are advcheckbox widgets, whereas
		 * repeat fields are just normal checkbox widgets.
		 *
		 */
		$formFieldName = $element->getName();
		$raw =& $record->getValue($field['name']);
		if ( $field['repeat'] and is_array($raw)){
			// If the field is a repeat field $raw will be an array of
			// values.
			$v = array();
			foreach ($raw as $key=>$value){
				$v[$value] = 1;
			}
			/*
			 *
			 * In this case we set this checkbox to the array of values that are currently checked.
			 *
			 */
			$val = $v;
		} else {
			/*
			 * 
			 * If the field is not a repeat, then it is only one value
			 *
			 */
			$val = $record->getValueAsString($field['name']);
		}
		
		
		return $val;
	}
}
