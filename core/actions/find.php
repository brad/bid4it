<?php

// Controller class to handle 'new' action that creates a new record in the database.

class dataface_actions_find {
	function handle($params){
		import( 'Dataface/SearchForm.php');
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$new = true;
		
		$form =& new Dataface_SearchForm($query['-table'], $app->db(),  $query);
		$res = $form->_build();
		if ( PEAR::isError($res) ){
			trigger_error($res->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
				
		// Add GET parameter flags ('-') so controller can pass control to this method upon submit.		

		$form->setDefaults( array( '-action'=>$query['-action']) );
		if ( $form->validate() ){
			$res = $form->process( array(&$form, 'performFind'));
		}
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		$context = array('form'=>&$out);
		df_display($context, 'Dataface_Find_View.html', true);
	}
}

?>
