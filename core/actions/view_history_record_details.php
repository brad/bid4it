<?php

class dataface_actions_view_history_record_details {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		if ( !@$_GET['history__id'] ){
			return PEAR::raiseError('No history id supplied', DATAFACE_E_ERROR);
		}
		$historyid = $_GET['history__id'];
		$query =& $app->getQuery();
		$table = $query['-table'];
		
		import('Dataface/HistoryTool.php');
		$ht = new Dataface_HistoryTool();
		if ( @$_GET['-show_changes'] ){
			$record = $ht->getDiffs($table, $historyid);
		} else {
			$record = $ht->getRecordById($table, $historyid);
		}
		if ( !$record ) return PEAR::raiseError("No history record for table {$table} with history id {$historyid} could be found", DATAFACE_E_ERROR);
		if ( PEAR::isError($record) ) return $record;
		
		$context = array('history_record'=>&$record);
		
		$t =& Dataface_Table::loadTable($table);
		$numfields = count($t->fields());
		$pts = 0;
		$ppf = array();
		foreach ($t->fields() as $field){
			if ( $t->isText($field['name']) ){
				$pts+=5;
				$ppf[$field['name']] = $pts;
			} else {
				$pts++;
				$ppf[$field['name']] = $pts;
			}
		}
		
		$firstField = null;
		$threshold = floatval(floatval($pts)/floatval(2));
		foreach ( $t->fields()  as $field){
			if ( $ppf[$field['name']] >= $threshold ){
				$firstField = $field['name'];
				break;
			}
		}
		
		$context['first_field_second_col'] = $firstField;
		$context['changes'] = @$_GET['-show_changes'];
		$context['table'] =& $t;
		df_display($context, 'Dataface_HistoryRecordDetails.html');
		
	}

}
?>
