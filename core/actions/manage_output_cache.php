<?php

class dataface_actions_manage_output_cache {

	function handle(&$params){
		
		$app =& Dataface_Application::getInstance();
		$context=array();
		if ( !is_array(@$app->_conf['_output_cache']) or !(@$app->_conf['_output_cache']['enabled']) ){
			
			$context['enabled'] = false;

		} else {
			$context['enabled'] = true;
		}
		
		if ( @$_POST['--clear-cache'] ){
			// Clear cache
			@mysql_query("delete from `__output_cache`", df_db());
			header('Location: '.$app->url('').'&--msg='.urlencode('Output cache has been cleared.'));
			exit;
		}
		
		$res = mysql_query("select count(*) from `__output_cache`", df_db());
		if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
		list($numrows) = mysql_fetch_row($res);
		$context['numrows'] = $numrows;
		
		df_display($context, 'manage_output_cache.html');
		
	}
}
