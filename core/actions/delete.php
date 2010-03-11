<?php
class dataface_actions_delete {

	function handle(&$params){
		import( 'Dataface/DeleteForm.php');
		import( 'Dataface/LanguageTool.php');
		import( 'Dataface/Record.php');
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$record =& new Dataface_Record($query['-table'], @$_REQUEST['--__keys__']);	
		
		$form =& new Dataface_DeleteForm($query['-table'], $app->db(), $query);
		
		$form->_build();
		$form->addElement('hidden','-table');
		$form->setDefaults(array('-table'=>$query['-table']));
		$msg = '';
		
		// Validation - 
		if ( $form->validate() ){
			$res = $form->process( array(&$form, 'delete'), true);
			$response =& Dataface_Application::getResponse();
			if ( !isset($response['--msg']) ) $response['--msg'] = '';
			$failed = false;
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				return $res;
			} else if ( Dataface_Error::isNotice($res) ){
				$app->addError($res);
				$failed = true;
			} else if ( is_array($res) ){
				$msg = df_translate(
					'An error has occured while deleting records.',
					'An error has occured while deleting records.'
					);
				foreach ($res as $warning){
					$response['--msg'] .= "\n".$warning->getMessage();
				}
				
			} else  {
				$msg = Dataface_LanguageTool::translate(
					/* i18n id */
					'Records deleted',
					/* default message */
					'Records deleted.'
				);
			}
			$msg = urlencode(trim($msg."\n".$response['--msg']));
			if ( !$failed ){
				import('Dataface/Utilities.php');
				Dataface_Utilities::fireEvent('after_action_delete', array('record'=>&$record));
				header('Location: '.$_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?-table='.$query['-table'].'&--msg='.$msg);
				exit;
			}
		}
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		
		$context = array('form'=>$out);
		if ( isset($query['-template']) ) $template = $query['-template'];
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
		else $template = 'Dataface_Delete_Record.html';
		df_display($context, $template, true);
	
	}
}
