<?php

import('Dataface/JSON.php');
import('Dataface/ValuelistTool.php');

class dataface_actions_ajax_valuelist_append {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		
		// Check for valuelist, display JSON error and exit if is not present.
		if ( !@$_POST['-valuelist'] ){
			echo JSON::error("No valuelist specified.");
			exit;
		}
		
		$valuelist = $_POST['-valuelist'];
		$query =& $app->getQuery();
		
		$table =& Dataface_Table::loadTable($query['-table']);
		
		// Throw error if no value is provided to be added to the valuelist.
		if ( !@$_POST['-value'] ){
			echo JSON::error("A value was not given to be added to the valuelist.");
			exit;
		}
		
		// Key becomes null if not present.
		$value = $_POST['-value'];
		if ( @$_POST['-key'] ){
			$key = $_POST['-key'];
		} else {
			$key = null;
		}	
		
		$vt =& Dataface_ValuelistTool::getInstance();
		$res = $vt->addValueToValuelist($table, $valuelist, $value, $key, true);
		if ( PEAR::isError($res) ){
			echo JSON::error($res->getMessage());
			exit;
		}
		// Successful.
		echo JSON::json(array(
			'success'=>1,
			'value'=>array('key'=>$res['key'], 'value'=>$res['value'])
			)
		);
		exit;
		
		
	}

}

?>
