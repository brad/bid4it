<?php

import('Dataface/PermissionsTool.php');

class dataface_actions_reorder_related_records {
	function handle(&$params){
		
		if ( !isset( $_POST['-redirect'] ) and !isset($_POST['relatedList-body']) ){
			return PEAR::raiseError('Cannot reorder related records. No redirect url was given in the POST parameters.'.Dataface_Error::printStackTrace());
		}
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( !($record = df_get_selected_records($query)) ){
			$record =& $app->getRecord();
		} else {
			$record = $record[0];
		}
		if ( PEAR::isError($record) ) return $record;
		if ( !$record ){
			return PEAR::raiseError('The record could not be found.');
		}
		
		if ( !@$query['-relationship'] ){
			return PEAR::raiseError("Relationship not specified.");
		}
		
		$relationship =& $record->_table->getRelationship($query['-relationship']);
		if ( PEAR::isError($relationship) ) return $relationship;
		
		$orderColumn = $relationship->getOrderColumn();
		if ( !$orderColumn ){
			return PEAR::raiseError('Order column not specified - Could not reorder records.');
		}
		
		if ( !Dataface_PermissionsTool::checkPermission('reorder_related_records', $record, array('relationship'=>$query['-relationship']) ) ) {
			return Dataface_Error::permissionDenied('You do not have permission to reorder the records in this relationship.');
		}
		
		if ( isset($_POST['relatedList-body']) ){
			$relatedIds = array_map('urldecode', $_POST['relatedList-body']);
			// Reorder entire list OR ordering a subset of the list.
			// Therefore, reordering of the set of records is done with respect to each other.
			
			// Check to see if ordering has been initialised.
			$records = array();
			foreach ($relatedIds as $recid ){
				$records[] = df_get_record_by_id($recid);
			}
			$start = ( isset($query['-related:start']) ? $query['-related:start'] : 0);
			$record->sortRelationship($query['-relationship'], $start, $records);
		
			echo 'Sorted Successfully';
			exit;
		}
		
		if ( !isset( $_POST['-reorder:direction'] ) ){
			return PEAR::raiseError('Cannot reorder related records. No direction was specified.');
		}
		
		if ( !isset( $_POST['-reorder:index']) ){
			return PEAR::raiseError('Cannot reorder related records. No index was specified.');
		}
		
		$index = intval($_POST['-reorder:index']);
		
		switch ( $_POST['-reorder:direction']){
			case 'up': 
				$res = $record->moveUp($query['-relationship'], $index);
				break;	
			case 'down':
				$res = $record->moveDown($query['-relationship'], $index);
				break;
			default:
				return PEAR::raiseError('Invalid input for direction of reordering. Must be up or down but received "'.$_POST['-reorder:direction'].'"');
		}
		if ( PEAR::isError($res) ) return $res;
		header('Location: '.$_POST['-redirect']);
		exit;
		
	
	}
}
?>
