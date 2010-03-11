<?php

class dataface_actions_manage_build_index {
	function handle(&$params){
		import('Dataface/Index.php');
		$index = new Dataface_Index();
		if ( @$_POST['--build-index'] ){
			
			if ( is_array($_POST['--tables']) ){
				$tables = $_POST['--tables'];
			} else if ( !empty($_POST['--tables']) ){
				$tables = array($_POST['--tables']);
			} else {
				$tables = null;
			}
			
			if ( @$_POST['--clear'] ){
				$clear = true;
			} else {
				$clear = false;
			}
					
			$index->buildIndex($tables, '*', $clear);
			$app =& Dataface_Application::getInstance();
			header('Location: '.$app->url('').'&--msg='.urlencode('Successfully indexed database'));
			exit;
		}
		
		$tables = array_keys(Dataface_Table::getTableModificationTimes());
		foreach ( $tables as $key=>$table ){
			if ( !$index->isTableIndexable($table) ){
				unset($tables[$key]);
			}
		}
		
		df_display(array('tables'=>$tables), 'manage_build_index.html');
		
	}
}
