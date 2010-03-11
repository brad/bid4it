<?php

/* Controller class for when there is no controller for current action. */

class dataface_actions_default {
	function handle(&$params){
		import('dataface-public-api.php');
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$action =& $params['action'];
		if ( isset( $action['mode'] ) ){
			$query['-mode'] = $action['mode'];
		}
		
		$context =array();
		if ( @$query['-template'] ){
			$template = $query['-template'];
		} else if ( @$action['template'] ){
			$template = $action['template'];
		} else {
			trigger_error("No template found '".@$action['name']."'.".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$context = array();
		df_display($context, $template);
	}
}

?>
