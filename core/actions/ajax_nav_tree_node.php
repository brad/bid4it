<?php
// Returns a tree node for for the record tree menu. Called by the RecordNavMenu.html via HTTPRequest ajax call.
class dataface_actions_ajax_nav_tree_node {

	function handle(&$params){
		
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		if ( !$record ){
			echo '{}';
		}
		
		$relationships = $record->_table->getRelationshipsAsActions();
		if ( isset($_GET['-relationship']) ){
			$relationships = array($relationships[$_GET['-relationship']]);	
		}
		$outerOut = array();
		foreach ($relationships as $relationship){
			$innerOut = array();
			$relatedRecords = $record->getRelatedRecordObjects($relationship['name'],0,60);
			foreach ($relatedRecords as $relatedRecord){
				$domainRecord = $relatedRecord->toRecord();
				$innerOut[] = "'".$domainRecord->getId()."': ".$domainRecord->toJS(array());
			}
			// If a relationship is specified show as a js associative array of objects.
			// If no relationship is specified then relationships for the record will be returned in an array with keys as relationship names.
			if ( count($relationships) > 1 ){
				$outerOut[] = "'".$relationship['name']."': {'__title__': '".$relationship['label']."', '__url__': '".$record->getURL('-action=related_records_list&-relationship='.urlencode($relationship['name']))."','records': {".implode(',',$innerOut)."}}";
			} else {
				$outerOut[] = implode(',',$innerOut);			
			}	
			
		}
		echo '{'.implode(',',$outerOut).'}';
		exit;
		
	}	
}
