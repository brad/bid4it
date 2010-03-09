<?php
/* Accepts a parameter 'code' for email activation to verify the users id */

class dataface_actions_activate {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		
		if ( !isset($_GET['code']) ){
			// Required param
			return PEAR::raiseError(
				df_translate('actions.activate.MESSAGE_MISSING_CODE_PARAMETER',
					'Validation error.  Please check your url for the code parameter and try again.'
					),
				DATAFACE_E_ERROR
				);
		}
		
		// 0. Finds out the page the user was previously at so they can be redirected.
		if ( isset($_SESSION['--redirect']) ) $url = $_SESSION['--redirect'];
		else if ( isset($_SESSION['-redirect']) ) $url = $_SESSION['-redirect'];
		else if ( isset($_REQUEST['--redirect']) ) $url = $_REQUEST['--redirect'];
		else if ( isset($_REQUEST['-redirect']) ) $url = $_REQUEST['-redirect'];
		else $url = $app->url('-action='.$app->_conf['default_action']);
		
		
		// 1. If registration is older than the time limit then delete it.
		$time_limit = 24*60*60; // 1 day
		if ( isset($params['time_limit']) ){
			$time_limit = intval($params['time_limit']);
		}
		
		$res = mysql_query(
			"delete from dataface__registrations 
				where registration_date < '".addslashes(date('Y-m-d H:i:s', time()-$time_limit))."'",
			df_db()
			);
		if ( !$res ){
			trigger_error(mysql_error(df_db()), E_USER_ERROR);
		}
		
		// 2. Load the registration info.
		
		$res = mysql_query(
			"select registration_data from dataface__registrations
				where registration_code = '".addslashes($_GET['code'])."'",
			df_db()
			);
		
		if ( !$res ){
			trigger_error(mysql_error(df_db()), E_USER_ERROR);
		}
		
		if ( mysql_num_rows($res) == 0 ){
			// If there are no records matching the code, redirect the user and tell them that the registration was unsuccessful.
			$msg = df_translate(
				'actions.activate.MESSAGE_REGISTRATION_NOT_FOUND',
				'No registration information could be found to match this code.  Please try registering again.'
				);
			header('Location: '.$url.'&--msg='.urlencode($msg));
			exit;
		}
		
		// 3. Check that the username is not taken.
		
		list($raw_data) = mysql_fetch_row($res);
		$values = unserialize($raw_data);
		
		$res = mysql_query("select count(*) from 
			`".str_replace('`','',$app->_conf['_auth']['users_table'])."` 
			where `".str_replace('`','',$app->_conf['_auth']['username_column'])."` = '".addslashes($values[$app->_conf['_auth']['username_column']])."'
			", df_db());
		if ( !$res ){
			trigger_error(mysql_error(df_db()), E_USER_ERROR);
		}
		list($num) = mysql_fetch_row($res);
		if ( $num > 0 ){
			$msg = df_translate(
				'actions.activate.MESSAGE_DUPLICATE_USER',
				'Registration failed because a user already exists by that name.  Try registering again with a different name.'
				);
			header('Location: '.$url.'&--msg='.urlencode($msg));
			exit;
		}
		
		
		// 4. Save registration information and login the user.
		$record =& new Dataface_Record($app->_conf['_auth']['users_table'], array());
		$record->setValues($values);
		$res = $record->save();
		if ( PEAR::isError($res) ){
			header('Location: '.$url.'&--msg='.urlencode($res->getMessage()));
			exit;
		} else {
			$res = mysql_query(
				"delete from dataface__registrations
					where registration_code = '".addslashes($_GET['code'])."'",
				df_db()
				);
			
			if ( !$res ){
				trigger_error(mysql_error(df_db()), E_USER_ERROR);
			}
			$msg = df_translate(
				'actions.activate.MESSAGE_REGISTRATION_COMPLETE',
				'Registration complete.  You are now logged in.');
			$_SESSION['UserName'] = $record->strval($app->_conf['_auth']['username_column']);
			header('Location: '.$url.'&--msg='.urlencode($msg));
			exit;
			
		}
		
		
	}
}
?>
