<?php

// Action to create an RSS acitons feed
 
class dataface_actions_feed {

	function handle(&$params){
		import('Dataface/FeedTool.php');
		$app =& Dataface_Application::getInstance();
		$ft = new Dataface_FeedTool();
		header("Content-Type: application/xml; charset=".$app->_conf['oe']);
		
		$query = $app->getQuery();
		$conf = $ft->getConfig();
		
		$query['-skip'] = 0;
		if ( !isset($query['-sort']) ){
			$table =& Dataface_Table::loadTable($query['-table']);
			$modifiedField = $table->getLastUpdatedField();
			if ( $modifiedField ){
				$query['-sort'] = $modifiedField.' desc';
			}
		}
		
		if ( !isset($query['-limit']) ){
			$default_limit = $conf['default_limit'];
			if ( !$default_limit ){
				$default_limit = 60;
			}
			$query['-limit'] = $default_limit;
		}
		
		if ( isset($query['--format']) ){
			$format = $query['--format'];
		} else {
			$format = 'RSS1.0';
		}
		echo $ft->getFeedXML($query,$format);
		exit;
	}
}

?>
