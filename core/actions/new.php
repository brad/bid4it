<?php

// Controller class to create a new record in the database.

class dataface_actions_new {
	function handle(){
		import( 'Dataface/FormTool.php');
		import( 'Dataface/QuickForm.php');
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$new = true;
		
		$currentRecord =& new Dataface_Record($query['-table'], array());
		$currentTable =& Dataface_Table::loadTable($query['-table']);
		if ( !isset($query['--tab']) and count($currentTable->tabs($currentRecord)) > 1 ){
			list($query['--tab']) = array_keys($currentTable->tabs($currentRecord));
		} else if ( count($currentTable->tabs($currentRecord)) <= 1 ){
			unset($query['--tab']);
		}
		$formTool =& Dataface_FormTool::getInstance();
		$form = $formTool->createRecordForm($currentRecord, true, @$query['--tab'], $query);
		
		//$form =& new Dataface_QuickForm($query['-table'], $app->db(),  $query, '',$new);
		$res = $form->_build();
		if ( PEAR::isError($res) ){
			trigger_error($res->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		
		}
		$formTool->decorateRecordForm($currentRecord, $form, true, @$query['--tab']);	
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
				
				
		// Either Form has not been submitted OR The form was submitted but didn't validate OR The form was submitted and validated.

		// Form submitted and validationed			
		if ( $formTool->validateRecordForm($currentRecord, $form, true, @$query['--tab']) ){

			// Form submitted and validated. Now it is processed.
			$formTool->handleTabSubmit($currentRecord, $form, @$query['--tab']);
			if ( !isset($query['--tab']) ){
				// No tabs - Process array
				$result = $form->process( array( &$form, 'save') );
			} else {
				// Using tabs, use the formtool session save function.
				$result = $formTool->saveSession($currentRecord);
			}
			
			$success = true;
			$response =& Dataface_Application::getResponse();
			
			if ( !$result ){
				trigger_error("Error occurred in save: ".mysql_error( $app->db()).Dataface_Error::printStackTrace(), E_USER_ERROR);
				exit;
			} else if ( PEAR::isError($result) && !Dataface_Error::isNotice($result) ){
				if ( Dataface_Error::isDuplicateEntry($result) ){
					$success = false;
					$form->_errors[] = $result->getMessage();
					
				} else {
					trigger_error($result->toString(). Dataface_Error::printStackTrace(), E_USER_ERROR);
					exit;
				}
			} else if ( Dataface_Error::isNotice($result) ){
			
				$app->addError($result);
				$success = false;
			}
			
			if ( $success){
				
				import('Dataface/Utilities.php');
				Dataface_Utilities::fireEvent('after_action_new', array('record'=>$form->_record));

					
				/*
				 * Form created a new record, therefore redirect to newly
				 * created record instead of the old record.  Used "keys" 
				 * of new record to generate a redirect link.
				 */

				//$query = $form->_record->getValues(array_keys($form->_record->_table->keys()));
				$url = $currentRecord->getURL(array('-action'=>'edit'));
				
				$msg = implode("\n", $app->getMessages());//@$response['--msg'];
				$msg = urlencode(trim(
					Dataface_LanguageTool::translate(
						/* i18n id */
						"Record successfully saved",
						/* Default message */
						"Record successfully saved."
					)."\n".$msg));
				$link = $url.'&--msg='.$msg; 
				header("Location: $link");
				exit;
			}
		
		}
		
		ob_start();
		$form->setDefaults($_GET);
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		if ( count($form->_errors) > 0 ){
			$app->clearMessages();
			$app->addError(PEAR::raiseError("Errors occured while processing form: <ul><li>".implode('</li><li>', $form->_errors)."</li></ul>"));
		}
		
		$context = array('form'=>&$out);
		$context['tabs'] = $formTool->createHTMLTabs($currentRecord, $form, @$query['--tab']);
			
		df_display($context, 'Dataface_New_Record.html', true);
	}
}

?>
