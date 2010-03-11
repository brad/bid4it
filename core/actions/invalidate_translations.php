<?php

// Queues current record for retranslation.

class dataface_actions_invalidate_translations {

	function handle(&$params){
		// Invalidated current translation
		$app =& Dataface_Application::getInstance();
		if ( !isset($_POST['--confirm_invalidate']) ){
			return PEAR::raiseError("Cannot invalidate translations with GET. Please supply the POST parameter '--confirm_invalidate'");
		}
		
		$record =& $app->getRecord();
		if ( !$record ){
			return PEAR::raiseError("No record matches the query parameters.");
		}
		// Imports the Dataface translation tool
		import('Dataface/TranslationTool.php');
		$tt =& new Dataface_TranslationTool();
		$res = $tt->markNewCanonicalVersion($record, $app->_conf['default_language']);
		if ( PEAR::isError($res) ){
			return $res;
		}
		
		$query =& $app->getQuery();
		if ( isset($query['--redirect']) ){
			header('Location: '.$query['--redirect'].'&--msg='.urlencode("Translations successfully invalidated."));
			exit;
		} else {
			header('Location: '.$record->getURL('-action=edit').'&--msg='.urlencode('Translations successfully invalidated.'));
			exit;
		}
	}
}

?>
