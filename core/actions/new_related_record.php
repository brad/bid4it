<?php

class dataface_actions_new_related_record {
	
	function handle(&$params){
		//global $myctr;
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$resultSet =& $app->getResultSet();
		
		//$record =& $app->getRecord();	// loads the current record 
		
		import( 'Dataface/ShortRelatedRecordForm.php');
		if ( !isset( $query['-relationship'])){
			return PEAR::raiseError(Dataface_LanguageTool::translate(
				'Relationship not specified in new related record',
				'Relationship not specified. Please specify a relationship.'
				), DATAFACE_E_ERROR
			);
		}

		$record = null;	// Form handles laoding of record.
		$form =& new Dataface_ShortRelatedRecordForm($record, $query['-relationship']);

		$form->_build();
		
		// Add GET param flags ('-') so control is passed to this method upon form submit.
		foreach ( $query as $key=>$value){
		
			if ( strpos($key,'-')===0 ){
				$form->addElement('hidden', $key);
				
				
				$form->setDefaults( array( $key=>$value) );
			}
		}
		
		// Store the current query string (after '?') in form, so user can be redirected back to original location.
		$form->addElement('hidden', '-query');
		$form->setDefaults( array( '-action'=>$query['-action'],'-query'=>$_SERVER['QUERY_STRING']) );
		
		if ( !Dataface_PermissionsTool::checkPermission('add new related record',$form->_record)){
			return Dataface_Error::permissionDenied(
				Dataface_LanguageTool::translate(
					'Permission denied. You cannot add new related record.',
					'Permission Denied: You do not have the correct permissions to add related records to the current record.'
				)
			);
		}
		
		if ( $form->validate() ){
			$vals = $form->exportValues();
				
			$res = $form->process(array(&$form, 'save'), true);

			$response =& Dataface_Application::getResponse();
			
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				return $res;

			} else if ( Dataface_Error::isNotice($res) ){
				$success = false;
				$app->addError($res);
			} else {
				$success = true;
			}
				
			if ( $success ){
				import('Dataface/Utilities.php');
				Dataface_Utilities::fireEvent('after_action_new_related_record');
				$fquery = array('-action'=>'browse');
				$msg = urlencode(
					trim(
						Dataface_LanguageTool::translate(
							"Record successfully added to relationship",
							"Record successfully added to ".$query['-relationship']." relationship.\n",
							array('relationship'=>$query['-relationship'])
						).
						(isset($response['--msg']) ? $response['--msg'] : '')
					)
				);

				foreach ($vals['__keys__'] as $key=>$value){
					$fquery[$key] = "=".$value;
				}
				$fquery['-relationship'] = $query['-relationship'];
				$fquery['-action'] = 'related_records_list';
				$link = Dataface_LinkTool::buildLink($fquery);
				header("Location: $link"."&--msg=".$msg);
		 		exit;
		 	}
		 }
		 
		ob_start();
		$gdefs = array();
		foreach ( $_GET as $gkey=>$gval ){
			if ( substr($gkey,0, 4) == '--q:' ){
				$gdefs[substr($gkey, 4)] = $gval;
			}
		}
		if ( count($gdefs) > 0 ){
			$form->setDefaults($gdefs);
		}
		
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		$context = array('form'=>$out);
		if ( isset($query['-template']) ) $template = $query['-template'];
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
		else $template = 'Dataface_Add_New_Related_Record.html';
		df_display($context, $template, true);
		
	}
}

?>
