<?php
class dataface_actions_export_json {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		// Get records
		$records = df_get_selected_records($query);
		if ( !$records ){
			if ( $query['-mode'] == 'list' ){
				$records = df_get_records_array($query['-table'], $query);
			} else {
				$records = array( $app->getRecord() );
			}
		}
		
		$out = array();
		if ( isset( $query['--fields'] ) ){
			$fields = explode(' ', $query['--fields']);
		} else {
			$fields = null;
		}
		
		foreach ($records as $record){
			if ( !$record->checkPermission('export_json')  ){
				continue;
			}
			
			if ( is_array($fields) ){
				$allowed_fields = array();
				foreach ($fields as $field ){
					if ( !$record->checkPermission('export_json', array('field'=>$field) ) ){
						continue;
					}
					$allowed_fields[] = $field;
				}
			} else {
				$allowed_fields = null;
			}
			$out[] = $record->vals($allowed_fields);
		}
		
		import('Services/JSON.php');
		$json = new Services_JSON;
		$enc_out = $json->encode($out);
		header('Content-type: application/json; charset='.$app->_conf['oe']);
		echo $enc_out;
		exit;
	}
}
