<?php

/* Controller class - Allows the editing of a record in the database. */

class dataface_actions_edit {
	function handle(&$params){
		import( 'Dataface/FormTool.php');
		import( 'Dataface/QuickForm.php');
		
				
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$resultSet =& $app->getResultSet();
		
		$currentRecord =& $app->getRecord();
		$currentTable =& Dataface_Table::loadTable($query['-table']);
		if ( !isset($query['--tab']) and count($currentTable->tabs($currentRecord)) > 1 ){
			list($query['--tab']) = array_keys($currentTable->tabs($currentRecord));
		} else if ( count($currentTable->tabs($currentRecord)) <= 1 ){
			unset($query['--tab']);
		}
		
		
		
		// Create quickform for current record
		$formTool =& Dataface_FormTool::getInstance();
		
		
		if ( $resultSet->found()> @$query['-cursor']){
			$form =& $formTool->createRecordForm($currentRecord, false, @$query['--tab'], $query);
			
			// Either editing or creating a new record.			 
			$res = $form->_build();
			if ( PEAR::isError($res) ){
				trigger_error($res->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
			
			}
			$formTool->decorateRecordForm($currentRecord, $form, false, @$query['--tab']);
			
			
			// Add GET parameter flags (GET vars start with'-'). Allows controller to use give control to this method on form submit.
			foreach ( $query as $key=>$value){
				if ( strpos($key,'-')===0 ){
					$form->addElement('hidden', $key);
					$form->setDefaults( array( $key=>$value) );
					
				}
			}
			
			// Current query string (after '?') in the form. Allows retrieval after redirect.
			$form->addElement('hidden', '-query');
			$form->setDefaults( array( '-action'=>$query['-action'],'-query'=>$_SERVER['QUERY_STRING']) );
			
			
			// Either - Form not submitted OR form submitted but didn't validate OR form submitted and was validated.
		
			// Form was submitted and validated.
			if ( $formTool->validateRecordForm($currentRecord, $form, false, @$query['--tab']) ){

				// Form submitted and validated ok. Now it is processed.
				$app->clearMessages();
				$formTool->handleTabSubmit($currentRecord, $form, @$query['--tab']);
				if ( !isset($query['--tab']) ){
					// If no tabs are used
					
					$result = $form->process( array( &$form, 'save') );
				} else {
					// Tabs are used, use the formtool session save function
					$result = $formTool->saveSession($currentRecord);
				}
				$success = true;
				$response =& Dataface_Application::getResponse();
				
				if ( !$result ){
					trigger_error("Error occurred while saving: ".mysql_error( $app->db()).Dataface_Error::printStackTrace(), E_USER_ERROR);
					exit;
				} else if ( PEAR::isError($result) && !Dataface_Error::isNotice($result) ){
					
					if ( Dataface_Error::isDuplicateEntry($result) ){
						return $result;
						
					} else {
						trigger_error($result->toString(). Dataface_Error::printStackTrace(), E_USER_ERROR);
						exit;
					}
				} else if ( Dataface_Error::isNotice($result) ){
					$app->addError($result);

					//$response['--msg'] = @$response['--msg'] ."\n".$result->getMessage();
					$success = false;
				}
				
				
				if ( $success ){
					
					import('Dataface/Utilities.php');
					Dataface_Utilities::fireEvent('after_action_edit', array('record'=>$form->_record));

					// Remove flag from string so user isn't redirected to create another record.
					$vals = $form->exportValues();
					$vals['-query'] = eregi_replace('[&\?]-new=[^&]+', '', $vals['-query']);
					
					$_SESSION['--last_modified_record_url'] = $form->_record->getURL();
					$_SESSION['--last_modified_record_title'] = $form->_record->getTitle();
					
					$msg = implode("\n", $app->getMessages());
					//$msg =@$response['--msg'];
					$msg = urlencode(
						Dataface_LanguageTool::translate(
							/* i18n id */
							'Record successfully saved',
							/* Default success message */
							"Record successfully saved.<br>"
						).$msg
					);
					$link = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?'.$vals['-query'].'&--msg='.$msg;
					
					
					// Redirect user to record.
					header("Location: $link");
					exit;
				}
			}
			
			ob_start();
			$form->display();
			$out = ob_get_contents();
			ob_end_clean();
			
			if ( count($form->_errors) > 0 ){
				$app->clearMessages();
				$app->addError(PEAR::raiseError("Some errors occurred while processing this form: <ul><li>".implode('</li><li>', $form->_errors)."</li></ul>"));
			}
			$context = array('form'=>$out);
			
			
			// Add tabs.
			$context['tabs'] = $formTool->createHTMLTabs($currentRecord, $form, @$query['--tab']);
			
				 
		} else {
			// No records found.
			$context = array('form'=>'');
			
			if ( isset($_SESSION['--last_modified_record_url']) ){
				$lastModifiedURL = $_SESSION['--last_modified_record_url'];
				$lastModifiedTitle = $_SESSION['--last_modified_record_title'];
				unset($_SESSION['--last_modified_record_title']);
				unset($_SESSION['--last_modified_record_url']);
				$app->addMessage(
					df_translate(
						'Return to last modified record',
						'No records matched your request.  Click <a href="'.$lastModifiedURL.'">here</a> to return to <em>'.htmlspecialchars($lastModifiedTitle).'</em>.',
						array('lastModifiedURL'=>$lastModifiedURL,
							 'lastModifiedTitle'=>$lastModifiedTitle
							)
						)
					);
			} else {
				$app->addMessage(
					Dataface_LanguageTool::translate(
						'No records found',
						'No records found'
						)
					);
			}
			
			$query['-template'] = 'Dataface_Main_Template.html';
		}
		
		
		if ( isset($query['-template']) ) $template = $query['-template'];
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
		else $template = 'Dataface_Edit_Record.html';
		

		df_display($context, $template, true);
		
	}
	
}
?>
