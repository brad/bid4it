<?php
class dataface_actions_history_restore_record {

	function handle(&$params){
	
		if ( !@$_POST['history__id'] ) return PEAR::raiseError("History id not specified", DATAFACE_E_ERROR);
		$historyid = $_POST['history__id'];
		if ( !preg_match('/\d+/', $historyid) ) return PEAR::raiseError("Invalid history id.", DATAFACE_E_ERROR);
		
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		if ( !$record ) return PEAR::raiseError("Record not specified", DATAFACE_E_ERROR);
		
		import("Dataface/HistoryTool.php");
		$ht = new Dataface_HistoryTool();
		$hrecord = $ht->getRecordById($record->_table->tablename, $historyid);
		
		// Check if history record matches current record.
		$keys = array_keys($record->_table->keys());
		if ( $record->strvals($keys) != $hrecord->strvals($keys) ) 
			return PEAR::raiseError("Trying to restore record history.", DATAFACE_E_ERROR);
			
		
		// Restore the correct record that has just been found.
		if ( @$_POST['-fieldname'] ) $fieldname = $_POST['-fieldname'];
		else $fieldname = null;
		$res = $ht->restore($record, $historyid, $fieldname);
		
		if ( PEAR::isError($res) ) return $res;
		
		$url = false; //$app->getPreviousUrl(true);
		if ( @$_POST['-locationid'] ) $url = DATAFACE_SITE_HREF.'?'.$app->decodeLocation($_POST['-locationid']);

		if ( !$url ){
			// If no URL is supplied then create a URL to return to record history list.
			$url = $record->getURL('-action=history');
		
		}
		
		if ( $fieldname ){
			$msg = "The field '$fieldname' has been restored from '".$hrecord->strval('history__modified')."'.";
		} else {
			$msg = "Record restored from '".$hrecord->strval('history__modified')."'.";
		}
		$url .= "&--msg=".urlencode($msg);
		
		header('Location: '.$url);
		exit;
		
	}

}

?>
