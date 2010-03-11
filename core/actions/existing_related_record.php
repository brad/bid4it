<?php

import('Dataface/LinkTool.php');

class dataface_actions_existing_related_record {
	function handle(&$params){
		import( 'Dataface/ExistingRelatedRecordForm.php');
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$resultSet =& $app->getResultSet();
		
		//$record =& $app->getRecord();	// loads current record 
		
		if ( !isset( $query['-relationship'] ) ){
			return PEAR::raiseError(
				Dataface_LanguageTool::translate(
					'Error: No relationship specified',
					'Error.  No relationship was specified when trying to add existing related record.'
					),
					DATAFACE_E_NOTICE
				);
			
		}
		$record = null;
		$form =& new Dataface_ExistingRelatedRecordForm($record, $query['-relationship']);
		$form->_build();
		
		// Add GET parameter flags (GET vars start with '-') controller passes control to this method.
		foreach ( $query as $key=>$value){
			if ( strpos($key,'-')===0 ){
				$form->addElement('hidden', $key);
				$form->setDefaults( array( $key=>$value) );
				
			}
		}
		
		// Store current query string (after '?') in the form so user can be taken to original location.
		$form->addElement('hidden', '-query');
		$form->setDefaults( array( '-action'=>$query['-action'],'-query'=>$_SERVER['QUERY_STRING']) );
		

		if ( !$form->_record || !is_a($form->_record, 'Dataface_Record') ){
			trigger_error(
				Dataface_LanguageTool::translate(
					'Fatal Error',
					'Fatal Error: Record was null so form could not load. '.Dataface_Error::printStackTrace(),
					array('stack_trace'=>Dataface_Error::printStackTrace(), 'msg'=>'FRecord was null so form could not load.')
					),
				E_USER_ERROR
				);
		}
					
		if ( !Dataface_PermissionsTool::checkPermission('add existing record',$form->_record) ) {
			return Dataface_Error::permissionDenied(
				Dataface_LanguageTool::translate(
					'Error: Permission denied, record not added.',
					'Permission Denied.  Insufficient permissions to add an existing related record.  Required permission: "add existing related record". Current granted permissions: "'.implode(',',$form->_record->getPermissions()).'".',
					array('required_permission'=>'add existing related record', 'granted_permissions'=>implode(',', $form->_record->getPermissions()) )
					)
				);
			
		}
		
		if ( $form->validate() ){
			$res = $form->process(array(&$form, 'save'), true);
			$response =& Dataface_Application::getResponse();
			
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				return $res;
			} else if ( Dataface_Error::isNotice($res) ){
				$app->addError(PEAR::raiseError(
					df_translate(
						'Record not added due to errors',
						'Record not added to relationship due to the following errors:'
						), 
					DATAFACE_E_NOTICE)
				);
				$app->addError($res);
				$success = false;
			} else {
				$success = true;
			}
			if ( $success ){
				import('Dataface/Utilities.php');
				Dataface_Utilities::fireEvent('after_action_existing_related_record');
				$fquery = array('-action'=>'browse');
				$msg = Dataface_LanguageTool::translate(
					'Record has been added to relationship',
					"The record has been added to the ".$query['-relationship']." relationship.\n" ,
					array('relationship'=>$query['-relationship'])
					);
				$msg = urlencode(trim(($success ? $msg :'').@$response['--msg']));
				
				
				$vals = $form->exportValues();
				if ( isset($vals['--redirect']) ){
					$qmark = (strpos($vals['--redirect'],'?') !== false) ? '&':'?';
					header('Location: '.$vals['--redirect'].$qmark.'--msg='.$msg);
					exit;
				}
				foreach ($vals['__keys__'] as $key=>$value){
					$fquery[$key] = "=".$value;
				}
				$link = Dataface_LinkTool::buildLink($fquery);
				header("Location: $link"."&--msg=".$msg);
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
		else $template = 'Dataface_Add_Existing_Related_Record.html';
		df_display($context, $template, true);
	}
}
?>
