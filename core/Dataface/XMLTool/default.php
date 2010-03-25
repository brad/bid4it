<?php
import('Dataface/XMLTool.php');
class Dataface_XMLTool_default extends Dataface_XMLTool {
	var $expanded = false;
	function toXML(&$arg){
		$out = array();
		if (  is_a($arg, 'Dataface_Record') ){
			$out[] = '<'.$arg->_table->tablename.' id="'.$this->xmlentities($arg->getId()).'">';
			foreach ( $arg->_table->fields(false, true) as $field ){
				if ( @$field['vocabulary'] ){
					$value = ' value="'.$this->xmlentities($arg->val($field['name'])).'"';
				} else {
					$value = '';
				}
				
				$out[] = '	<'.$field['name'].$value.'>'.$this->xmlentities($arg->display($field['name'])).'</'.$field['name'].'>';
			}
			
			if ( $this->expanded ){
			
			
				$joinTables = array_keys($arg->_table->__join__($arg));
				foreach ( $joinTables as $jtable ){
					$out[] = $this->toXML($arg->getJoinRecord($jtable));
				}
			
			
				$relationships =& $arg->_table->relationships();
				foreach ( array_keys($relationships) as $r ){
					$out[] = '	<'.$r.'>';
					$rrecords =& $arg->getRelatedRecordObjects($r, 'all');
					foreach ( $rrecords as $rrecord ){
						$relatedTables = array();
						$relatedTableRecords = array();
						$out[] = '		<related_record>';
						foreach ( $relationships[$r]->_schema['short_columns'] as $col ){
							$table =& $relationships[$r]->getTable($col);
							
							$temp =& $rrecord->toRecord($table->tablename);
							if ( !isset($relatedTables[$table->tablename]) ){
								$relatedTables[$table->tablename] =& $table;
								$relatedTableRecords[$table->tablename] =& $temp;
							}
							
							$tempField =& $temp->_table->getField($col);
							if ( @$tempField['vocabulary'] ){
								$value = ' value="'.$this->xmlentities($temp->val($col)).'"';
							} else {
								$value = '';
							}
							$out[] = '			<'.$col.$value.'>'.$this->xmlentities($temp->display($col)).'</'.$col.'>';
							
							
							unset($temp);
							unset($tempField);
							unset($table);
						}
						foreach (array_keys($relatedTables) as $rtablename){
							$joinTables = array_keys($relatedTables[$rtablename]->__join__($relatedTableRecords[$rtablename]));
							foreach ( $joinTables as $jtable ){
								$out[] = $this->toXML($relatedTableRecords[$rtablename]->getJoinRecord($jtable));
							}
						}
						$out[] = '		</related_record>';
					}
					unset($rrecords);
					$out[] = '	</'.$r.'>';
				}
				
				
			
			}
			$out[] = '</'.$arg->_table->tablename.'>';
			return implode("\n", $out);
			
			
		} else if ( is_array($arg) ){
			return implode("\n", array_map(array(&$this, 'toXML'), $arg));
		} else {
			return '';
		}
	
	}
	
	function header(){
		
		$out = array();
		$app =& Dataface_Application::getInstance();
		header('Content-type: text/xml; charset='.$app->_conf['oe']);
		$out[] = "<?xml version=\"1.0\"?>";
		$out[] = "<record>";
		return implode("\n", $out);
	
	}
	
	function footer(){
		
		return "</record>";
	}
}