<?php

/**
 * Action that allows users to register for bid4it!
 *
 * Enabled if the following are true:
 * a) Authentication is enabled in the [_auth] section of the conf.ini file.
 * b) The allow_register option in the [_auth] section of the conf.ini file is set to 1.
 */

class dataface_actions_register {

	// Holds HTML_QuickForm object that for the registration form.
	var $form;
	var $params;
	var $ontology;
	
	function handle(&$params){
		$this->params =& $params['action'];
		unset($params);
		$params =& $this->params;
		
		$app =& Dataface_Application::getInstance();
		$auth =& Dataface_AuthenticationTool::getInstance();

		import('Dataface/Ontology.php');
		Dataface_Ontology::registerType('Person', 'Dataface/Ontology/Person.php', 'Dataface_Ontology_Person');
		$this->ontology =& Dataface_Ontology::newOntology('Person', $app->_conf['_auth']['users_table']);
		
		$atts =& $this->ontology->getAttributes();
		if ( isset($app->_conf['email_column']) ){
			
			$atts['email'] =& $this->ontology->table->getField( $app->_conf['email_column'] );
			$this->fieldnames['email'] = $app->_conf['email_column'];
		} 
			
		if ( $auth->isLoggedIn() ){
			return Dataface_Error::permissionDenied("You cannot register while logged in.");
		}
		
		$query =& $app->getQuery();
		if ( !is_array(@$app->_conf['_auth']) ){
			return PEAR::raiseError("Registration cannot take place if authentication is not enabled.", DATAFACE_E_ERROR);
		}
		
		if ( !@$app->_conf['_auth']['allow_register'] ){
			return PEAR::raiseError("Registration not allowed. Please contact the administrator.", DATAFACE_E_ERROR);
		}
		
		$pt =& Dataface_PermissionsTool::getInstance();
		
		// Creates new record form on users table.
		$this->form =& df_create_new_record_form($app->_conf['_auth']['users_table']);
		
		// Add the -action element so form will redirect back.
		$this->form->addElement('hidden','-action');
		$this->form->setDefaults(array('-action'=>$query['-action']));
		
		// Check username availability.
		$validationResults = $this->validateRegistrationForm($_POST);
		if ( count($_POST) > 0 and PEAR::isError($validationResults) ){
			$this->form->_errors[$app->_conf['_auth']['username_column']] = $validationResults->getMessage();
		}
		if ( !PEAR::isError($validationResults) and $this->form->validate() ){
			// Using own form processing - Field inputs into the Dataface_Record object are manually pushed.
			$this->form->push();
			
			// Get Dataface_Record object to be added.
			$rec =& $this->form->_record;
			$delegate =& $rec->_table->getDelegate();
			
			// Delegation
			if ( isset($delegate) and method_exists($delegate, 'beforeRegister') ){
				$res = $delegate->beforeRegister($rec);
				if ( PEAR::isError($res) ){
					return $res;
				}
			}
			
			$appdel = & $app->getDelegate();
			if ( isset($appdel) and method_exists($appdel, 'beforeRegister') ){
				$res = $appdel->beforeRegister($rec);
				if ( PEAR::isError($res) ) return $res;
			}
			
			// Processing - Passes control to the processRegistrationForm method in this class.
			$res = $this->form->process(array(&$this, 'processRegistrationForm'), true);
			
			// If error = true then mark and show form again
			// Else redirect to the next page and inform user of success.
			if ( PEAR::isError($res) ){
				$app->addError($res);
				
			} else {
			
				// Delegate classes
				if ( isset($delegate) and method_exists($delegate, 'afterRegister') ){
					$res  = $delegate->afterRegister($rec);
					if ( PEAR::isError($res) ) return $res;
				}
				
				if ( isset($appdel) and method_exists($appdel, 'afterRegister') ){
					$res = $appdel->afterRegister($rec);
					if ( PEAR::isError($res) ) return $res;
				}
			
				// Redirect markers redirect user to previous page to that of the login page.
				if ( isset($_SESSION['--redirect']) ) $url = $_SESSION['--redirect'];
				else if ( isset($_SESSION['-redirect']) ) $url = $_SESSION['-redirect'];
				else if ( isset($_REQUEST['--redirect']) ) $url = $_REQUEST['--redirect'];
				else if ( isset($_REQUEST['-redirect']) ) $url = $_REQUEST['-redirect'];
				else $url = $app->url('-action='.$app->_conf['default_action']);
				
				if ( @$params['email_validation'] ){
					$individual = $this->ontology->newIndividual($this->form->_record);
					$msg = df_translate('actions.register.MESSAGE_THANKYOU_PLEASE_VALIDATE', 
						'Thank you. An email has been sent to '.$individual->strval('email').' with instructions on how to complete the registration process.',
						array('email'=>$individual->strval('email'))
						);
				} else {
					// Automatic login after successful registration.
					$_SESSION['UserName'] = $this->form->exportValue($app->_conf['_auth']['username_column']);
					$msg =  df_translate('actions.register.MESSAGE_REGISTRATION_SUCCESSFUL',
						"Registration successful.  You are now logged in."
						);
				}
				// Forward to the success page.
				header('Location: '.$url.'&--msg='.urlencode($msg));
				exit;
			}
		}
		
		// Output buffer to store the form HTML in a variable and pass it to template.
		ob_start();
		$this->form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		$context = array('registration_form'=>$out);
		
		// Don't keep registration page in history, redirect the user to where they came from.
		$app->prefs['no_history'] = true;
		df_display($context, 'Dataface_Registration.html');
	
	}
	
	// Table to hold the temporary user registration information.
	function createRegistrationTable(){
		if ( !Dataface_Table::tableExists('dataface__registrations', false) ){
			// registration_code stores md5 hash to id the registration
			// registration_date - date registration was made
			// registration_data - serialised array of the data from getValues() on the record.	
			$sql = "create table `dataface__registrations` (
				registration_code varchar(32) not null,
				registration_date timestamp(11) not null,
				registration_data longtext not null,
				primary key (registration_code))";			
				
			$res = mysql_query($sql, df_db());
			if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
		}
		return true;
	}
	
	/**
	 * Validates registration form by checking for duplicate user names.
	 * @param array $values Value map. Usually from $_POST
	 * @return mixed PEAR_Error if there is a problem.
	 */
	function validateRegistrationForm($values){

		$app =& Dataface_Application::getInstance();
		$del =& $app->getDelegate();
		if ( $del and method_exists($del,'validateRegistrationForm') ){
			$res = $del->validateRegistrationForm($values);
			if ( PEAR::isError($res) ) return $res;
		}
		$conf =& $app->_conf['_auth'];
		
		// Make sure username is supplied.
		if ( !@$values[$conf['username_column']] ) 
			return PEAR::raiseError(
				df_translate('actions.register.MESSAGE_USERNAME_REQUIRED', 'Please enter a username')
				);
		
		// Check for duplicate username.
		$res = mysql_query("select count(*) from `".$conf['users_table']."` where `".$conf['username_column']."` = '".addslashes($values[$conf['username_column']])."'", df_db());
		if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
		
		list($num) = mysql_fetch_row($res);
		if ( $num>0 ){
			return PEAR::raiseError(
				df_translate('actions.register.MESSAGE_USERNAME_ALREADY_TAKEN', 'Sorry, that username already exists.')
				);
		}
		
		// Make sure that the user gave a password.
		if ( !@$values[$conf['password_column']] )
			return PEAR::raiseError(
				df_translate('actions.register.MESSAGE_PASSWORD_REQUIRED', 'Please enter a password')
				);
				
		// Make sure that the user supplied an email address - and that the email address is valid
		$emailField = $this->ontology->getFieldname('email');
		if ( !@$values[$emailField] or !$this->ontology->validate('email', @$values[$emailField], false /*No blanks*/))
			return PEAR::raiseError(
				df_translate('actions.register.MESSAGE_EMAIL_REQUIRED', 'Please enter a valid email address to register. Registration instructions swill be sent to this address.')
				);	
		return true;
	}
	
	function _fireDelegateMethod($name, &$record, $params=null){
		$app =& Dataface_Application::getInstance();
		$table = & Dataface_Table::loadTable($app->_conf['_auth']['users_table']);
		
		$appdel =& $app->getDelegate();
		$tdel =& $table->getDelegate();
		
		if ( isset($tdel) and method_exists($tdel, $name) ){
			$res = $tdel->$name($record, $params);
			if ( !PEAR::isError($res) or ($res->getCode() != DATAFACE_E_REQUEST_NOT_HANDLED) ){
				return $res;
			}
		}	
		
		if ( isset($appdel) and method_exists($appdel, $name) ){
			$res = $appdel->$name($record, $params);
			if ( !PEAR::isError($res) or ($res->getCode() != DATAFACE_E_REQUEST_NOT_HANDLED) ){
				return $res;
			}
		}
		return PEAR::raiseError("No delegate method found named '$name'.", DATAFACE_E_REQUEST_NOT_HANDLED);
	}

	function processRegistrationForm($values){
		
		$app =& Dataface_Application::getInstance();
		$conf =& $app->_conf['_auth'];
		$appConf =& $app->conf();
		$table =& Dataface_Table::loadTable($conf['users_table']);
		
		if ( @$this->params['email_validation'] ){
			
			// Create the registration table if it doesn't exist.
			$this->createRegistrationTable();
			
			// Store the registration attempt.
			
			// Unique id code.
			$code = null;
			do {
				$code = md5(rand());
			} while ( 
				mysql_num_rows(
					mysql_query(
						"select registration_code 
						from dataface__registrations 
						where registration_code='".addslashes($code)."'", 
						df_db()
						)
					) 
				);
			
			// Insert the value to unique id.
			$sql = "insert into dataface__registrations 
					(registration_code, registration_data) values
					('".addslashes($code)."',
					'".addslashes(
						serialize(
							$this->form->_record->getValues()
							)
						)."')";
			$res = mysql_query($sql, df_db());
			if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
			
			$activation_url = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?-action=activate&code='.urlencode($code);
			
			// Registration info inserted, send confirmation email.
			$res = $this->_fireDelegateMethod('sendRegistrationActivationEmail', $this->form->_record, $activation_url );
			if ( !PEAR::isError($res) or ( $res->getCode() != DATAFACE_E_REQUEST_NOT_HANDLED) ){
				return $res;
			}
			
			// Email not sent yet. Use Person Ontology to work with user table record
			$registrant =& $this->ontology->newIndividual($this->form->_record);
			// User's email address
			$email = $registrant->strval('email');
			
			/* Obtain email info. 
			* Return an associative array of the params involved in the email registration keys.
			* Subject, message, headers, parameters.
			* These can be passed directly to the mail function 
			*/
			$info = $this->_fireDelegateMethod('getRegistrationActivationEmailInfo', $this->form->_record, $activation_url);
			if ( PEAR::isError($info) ) $info = array();
			$info['to'] = $email;
			
			// Override parts of the message if delegate class requires.
			$subject = $this->_fireDelegateMethod('getRegistrationActivationEmailSubject', $this->form->_record, $activation_url);
			if ( !PEAR::isError($subject) ) $info['subject'] = $subject;
			
			$message = $this->_fireDelegateMethod('getRegistrationActivationEmailMessage', $this->form->_record, $activation_url);
			if ( !PEAR::isError($message) ) $info['message'] = $message;
			
			$parameters = $this->_fireDelegateMethod('getRegistrationActivationEmailParameters', $this->form->_record, $activation_url);
			if ( !PEAR::isError($parameters) ) $info['parameters'] = $parameters;
			
			$headers = $this->_fireDelegateMethod('getRegistrationActivationEmailHeaders', $this->form->_record, $activation_url);
			if ( !PEAR::isError($headers) ) $info['headers'] = $headers;
			
			
			// Fill missing info with defaults.
			if ( !@$info['subject'] ) 
				$info['subject'] = df_translate(
					'actions.register.MESSAGE_REGISTRATION_ACTIVATION_EMAIL_SUBJECT',
					$app->getSiteTitle().': Activate your account',
					array('site_title'=>$app->getSiteTitle())
					);
			
			if ( !@$info['message'] ){
				$site_title = $app->getSiteTitle();
				if ( isset($appConf['abuse_email']) ){
					$admin_email = $appConf['abuse_email'];
				} else if ( isset($appConf['admin_email']) ){
					$admin_email = $appConf['admin_email'];
				} else {
					$admin_email = $_SERVER['SERVER_ADMIN'];
				}
				
				if ( isset( $appConf['application_name'] ) ){
					$application_name = $appConf['application_name'];
				} else {
					$application_name = df_translate('actions.register.LABEL_A_DATAFACE_APPLICATION','a Dataface Application');
				}
				
				if ( file_exists('version.txt') ){
					$application_version = trim(file_get_contents('version.txt'));
				} else {
					$application_version = '0.1';
				}
				
				if ( file_exists(DATAFACE_PATH.'/version.txt') ){
					$dataface_version = trim(file_get_contents(DATAFACE_PATH.'/version.txt'));
				} else {
					$dataface_version = 'unknown';
				}
				
				$msg = <<<END
Thank you for registering with $site_title .  In order to complete your registration,
please visit $activation_url .

If you have not attempted to register on this web site and believe that you have received
this email in error, please contact $admin_email .
-----------------------------------------------------------
This message was sent by $site_title.
END;

				$info['message'] = df_translate(
					'actions.register.MESSAGE_REGISTRATION_ACTIVATION_EMAIL_MESSAGE',
					$msg,
					array(
						'site_title'=>$site_title,
						'activation_url'=>$activation_url,
						'admin_email'=>$admin_email,
						'application_name'=>$application_name,
						'application_version'=>$application_version,
						'dataface_version'=>$dataface_version
						)
					);
			
			
			}

			// Send email.
			if ( @$conf['_mail']['func'] ) $func = $conf['_mail']['func'];
			else $func = 'mail';
			$res = $func($info['to'],
						$info['subject'],
						$info['message'],
						@$info['headers'],
						@$info['parameters']);
			if ( !$res ){
				return PEAR::raiseError('Failed to send activation email.  Please try again later.', DATAFACE_E_ERROR);
			} else {
				return true;
			}
			
		} else {
			// No email validation. Instead pass to the form's standard processing function.
			return $this->form->process(array(&$this->form, 'save'), true);
		}
		
	}
}

?>
