<?php

import('Dataface/Table.php');

class dataface_actions_related_records_list {
	function handle($params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( !isset($query['-relationship']) ){
			return PEAR::raiseError("No relationship specified.");
		}
		
		$table =& Dataface_Table::loadTable($query['-table']);

		$action = $table->getRelationshipsAsActions(array(), $query['-relationship']);
	
		
		if ( isset($query['-template']) ){
			df_display(array(), $query['-template']);
		} else if ( isset($action['template']) ){
			df_display(array(), $action['template']);
		} else {
			df_display(array(), 'Dataface_Related_Records_List.html');
		}
		
	}
}

?>
