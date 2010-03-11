<?php

// Get Binary Large Object from app.

class dataface_actions_getBlob {

	function handle(&$params){
	
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$app->_handleGetBlob($query);
		exit;
	}
}

?>
