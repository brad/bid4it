<?php

class dataface_actions_custom {
	function handle($params){
		if ( !isset($params['action']['page']) ){
			trigger_error(
				df_translate(
					'Page not specified',
					'No page specified at '.Dataface_Error::printStackTrace(),
					array('stack_trace'=>Dataface_Error::printStackTrace())
					)
				,
				E_USER_ERROR
				);
		} else {
			$page = $params['action']['page'];
		}
		$app =& Dataface_Application::getInstance();
		$pages = $app->getCustomPages();
		if (!isset( $pages[$page] ) ){
			trigger_error( 
				df_translate(
					'Custom page not found',
					"Request for custom page '$page' failed because page does not exist in pages directory.". Dataface_Error::printStackTrace(),
					array('page'=>$page, 'stack_trace'=>Dataface_Error::printStackTrace())
					), 
					E_USER_ERROR
				);
		}
		ob_start();
		include $pages[$page];
		$out = ob_get_contents();
		ob_end_clean();
		df_display(array('content'=>$out), 'Dataface_Custom_Template.html');
		return true;	
	}

}

?>
