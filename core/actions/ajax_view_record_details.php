<?php

class dataface_actions_ajax_view_record_details {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		
		$query =& $app->getQuery();
		$record =& $app->getRecord();
		

		// If no records match the query, throw error to PEAR.
		if ( !$record ) return PEAR::raiseError("No records match that query.", DATAFACE_E_ERROR);
		if ( PEAR::isError($record) ) return $record;
		
		$context = array('record'=>&$record);
		
		$t =& $record->_table;
		$fields = array();
		// Check permissions of name.
		foreach ( $t->fields(false,true) as $field){
			if ( $record->checkPermission('view', array('field'=>$field['name']))){
				$fields[$field['name']] = $field;
			}
		}
		$numfields = count($fields);
		$pts = 0;
		$ppf = array();
		foreach (array_keys($fields) as $field){
			if ( $t->isText($field) ){
				$pts+=5;
				$ppf[$field] = $pts;
			} else {
				$pts++;
				$ppf[$field] = $pts;
			}
		}
		
		$firstField = null;
		$threshold = floatval(floatval($pts)/floatval(2));
		foreach ( array_keys($fields)  as $field){
			if ( $ppf[$field] >= $threshold ){
				$firstField = $field;
				break;
			}
		}
		// Show AjaxRecordDetails.html
		$context['first_field_second_col'] = $firstField;
		$context['table'] =& $t;
		$context['fields'] =& $fields;
		header('Content-type: text/html; charset='.$app->_conf['oe']);
		df_display($context, 'Dataface_AjaxRecordDetails.html');
		
	}

}
?>
