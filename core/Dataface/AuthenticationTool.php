<?php
/*
 *	Handles authentication for Dataface application.
 */
import('Dataface/Table.php');
class Dataface_AuthenticationTool {

	var $authType = 'basic';

	var $conf;
	/**
	 * Delegate object that can override login functionality.
	 */
	var $delegate;
	
	/**
	 * Name of the table that contains the Users records.
	 */
	var $usersTable;
	
	/**
	 * Name of the column that contains the username
	 */
	var $usernameColumn;
	
	/**
	 * Name of the column that contains the password
	 */
	var $passwordColumn;
	
	/**
	 * Optional name of the column that contains the level of the user.
	 */
	var $userLevelColumn;
	
	/**
	 * A flag to indicate if authentication is enabled.
	 */
	var $authEnabled = true;
	
	function &getInstance($params=array()){
		static $instance = 0;
		if ( $instance === 0 ){
			$instance = new Dataface_AuthenticationTool($params);
			if ( !defined('DATAFACE_AUTHENTICATIONTOOL_LOADED') ){
				define('DATAFACE_AUTHENTICATIONTOOL_LOADED', true);
			}
		}
		
		return $instance;
	}
	
	function Dataface_AuthenticationTool($params=array()){
		$this->conf = $params;
		$this->usersTable = ( isset($params['users_table']) ? $params['users_table'] : null);
		$this->usernameColumn = ( isset($params['username_column']) ? $params['username_column'] : null);
		$this->passwordColumn = (isset( $params['password_column']) ? $params['password_column'] : null);
		$this->userLevelColumn = (isset( $params['user_level_column']) ? $params['user_level_column'] : null);
		
		$this->setAuthType($params['auth_type']); 
	}
	
	function setAuthType($type){
		if ( isset( $type ) and $type != $this->authType ){
			$this->authType = $type;
			$this->delegate = null;
			// It is possible to define a delegate to this tool by adding the
			// auth_type option to the conf.ini file _auth section.
			$module = basename($type);
			$module_path = array(
				DATAFACE_SITE_PATH.'/modules/Auth/'.$module.'/'.$module.'.php',
				DATAFACE_PATH.'/modules/Auth/'.$module.'/'.$module.'.php'
				);
			foreach ( $module_path as $path ){
				if ( is_readable($path) ){
					import($path);
					$classname = 'dataface_modules_'.$module;
					$this->delegate = new $classname;
					break;
				}
			}
			
		} 
	}
	
	function getCredentials(){
	
		if ( isset($this->delegate) and method_exists($this->delegate, 'getCredentials') ){
			return $this->delegate->getCredentials();
		} else {
			$username = (isset($_REQUEST['UserName']) ? $_REQUEST['UserName'] : null);
			$password = (isset($_REQUEST['Password']) ? $_REQUEST['Password'] : null);
			return array('UserName'=>$username, 'Password'=>$password);
		}
	}
	
	function checkCredentials(){
		$app =& Dataface_Application::getInstance();
		if ( !$this->authEnabled ) return true;
		if ( isset($this->delegate) and method_exists($this->delegate, 'checkCredentials') ){
			return $this->delegate->checkCredentials();
		} else {
			// The user is attempting to log in.
			$creds = $this->getCredentials();
			if ( !isset( $creds['UserName'] ) || !isset($creds['Password']) ){
				// The user did not submit a username of password for login.. trigger error.
				//trigger_error("Username or Password Not specified", E_USER_ERROR);
				return false;
			}
			import('Dataface/Serializer.php');
			$serializer =& new Dataface_Serializer($this->usersTable);
			//$res = mysql_query(
			$sql =	"SELECT `".$this->usernameColumn."` FROM `".$this->usersTable."`
				 WHERE `".$this->usernameColumn."`='".addslashes(
					$serializer->serialize($this->usernameColumn, $creds['UserName'])
					)."'
				 AND `".$this->passwordColumn."`=".
					$serializer->encrypt(
						$this->passwordColumn,
						"'".addslashes($serializer->serialize($this->passwordColumn, $creds['Password']))."'"
					);
			$res = mysql_query($sql, $app->db());
			if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
				
			if ( mysql_num_rows($res) === 0 ){
				return false;
			}
			@mysql_free_result($res);
			return true;
		}
	
	}

	function authenticate(){
		$app =& Dataface_Application::getInstance();
		if ( !$this->authEnabled ) return true;
		
		
		$app->startSession($this->conf);
		
		if ( isset( $_REQUEST['-action'] ) and $_REQUEST['-action'] == 'logout' ){
			// the user has invoked a logout request.
			$appdel =& $app->getDelegate();
			if ( isset($appdel) and method_exists($appdel, 'before_action_logout' ) ){
				$res = $appdel->before_action_logout();
				if ( PEAR::isError($res) ) return $res;
			}
			session_destroy();
			
			if ( isset($this->delegate) and method_exists($this->delegate, 'logout') ){
				$this->delegate->logout();
			}
			if ( isset($_REQUEST['-redirect']) and !empty($_REQUEST['-redirect']) ){
				header('Location: '.$_REQUEST['-redirect']);
			} else if ( isset($_SESSION['-redirect']) ){
				$redirect = $_SESSION['-redirect'];
				unset($_SESSION['-redirect']);
				header('Location: '.$redirect);
				exit;
			
			} else {
				header('Location: '.DATAFACE_SITE_HREF);
			}
				// forward to the current page again now that we are logged out
			exit;
		}
		
		if ( isset( $_REQUEST['-action'] ) and $_REQUEST['-action'] == 'login' ){
			if ( $this->isLoggedIn() ){
				header('Location: '.DATAFACE_SITE_HREF.'?--msg='.urlencode("You are logged in"));
				exit;
			}
			// The user is attempting to log in.
			$creds = $this->getCredentials();
			$approved = $this->checkCredentials();
			
			if ( isset($creds['UserName']) and !$approved ){
				return PEAR::raiseError(
					df_translate('Incorrect Password',
							'Sorry, you have entered an incorrect username /password combination.  Please try again.'
							),
					DATAFACE_E_LOGIN_FAILURE
					);
			} else if ( !$approved ){
				
				$this->showLoginPrompt();
				exit;
			}
				
			// If we are this far, then the login worked..  We will store the 
			// userid in the session.
			$_SESSION['UserName'] = $creds['UserName'];
			
			if ( isset( $_REQUEST['-redirect'] ) and !empty($_REQUEST['-redirect']) ){
				header('Location: '.$_REQUEST['-redirect']);
				exit;
			} else if ( isset($_SESSION['-redirect']) ){
				$redirect = $_SESSION['-redirect'];
				unset($_SESSION['-redirect']);
				header('Location: '.$redirect);
				exit;
			}
			// Now we forward to the homepage:
			header('Location: '.$_SERVER['HOST_URI'].DATAFACE_SITE_HREF);
			exit;
		}
		
		if ( isset($this->delegate) and method_exists($this->delegate, 'authenticate') ){
			$res = $this->delegate->authenticate();
			if ( PEAR::isError($res) and $res->getCode() == DATAFACE_E_REQUEST_NOT_HANDLED ){
				// we just pass the buck
			} else {
				return $res;
			}
		}
		
		if ( isset($this->conf['pre_auth_types']) ){
			$pauthtypes = explode(',',$this->conf['pre_auth_types']);
			if ( $pauthtypes ){
				$oldType = $this->authType;
				foreach ($pauthtypes as $pauthtype){
					$this->setAuthType($pauthtype);
					if ( isset($this->delegate) and method_exists($this->delegate, 'authenticate') ){
						$res = $this->delegate->authenticate();
						if ( PEAR::isError($res) and $res->getCode() == DATAFACE_E_REQUEST_NOT_HANDLED) {
							// pass the buck
						} else {
							return $res;
						}
					}
				}
				$this->setAuthType($oldType);
			}
		}
		
		
	}
	
	/**
	 * Indicates whether there is a user logged in or not.
	 */
	function isLoggedIn(){
		if ( !$this->authEnabled ) return true;
		if ( isset($this->delegate) and method_exists($this->delegate, 'isLoggedIn') ){
			return $this->delegate->isLoggedIn();
		}

		return (isset($_SESSION['UserName']) and $_SESSION['UserName']);
	}
	
	/**
	 * Displays the login prompt for an application.
	 * @param $msg Optional error message to display.  e.g. 'Incorrect password'
	 */
	function showLoginPrompt($msg=''){
		if ( !$this->authEnabled ) return true;
		if ( isset($this->delegate) and method_exists($this->delegate, 'showLoginPrompt') ){
			return $this->delegate->showLoginPrompt($msg);
		}
		header("HTTP/1.1 401 Please Log In");
		$app =& Dataface_Application::getInstance();
		$url = $app->url('-action=login_prompt');
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( $msg ) $msgarray = array($msg);
		else $msgarray = array();
		if ( isset($query['--msg']) ){
			$msgarray[] = $query['--msg'];
		}
		$msg = trim(implode('<br>',$msgarray));
		if ( $msg ) $url .= '&--msg='.urlencode($msg);
		if ( $query['-action'] != 'login' and $query['-action'] != 'login_prompt' ) $_SESSION['-redirect'] = $app->url('');
		header("Location: $url");
		exit;
		//df_display(array('msg'=>$msg, 'redirect'=>@$_REQUEST['-redirect']), 'Dataface_Login_Prompt.html');
	
	}
	
	/**
	 * Returns reference to a Dataface_Record object of the currently logged in
	 * user's record.
	 */
	function &getLoggedInUser(){
		$null = null;
		if ( !$this->authEnabled ) return $null;
		if ( isset($this->delegate) and method_exists($this->delegate, 'getLoggedInUser') ){
			$user =&  $this->delegate->getLoggedInUser();
			return $user;
		}
		if ( !$this->isLoggedIn() ) return $null;
		static $user = 0;
		if ( $user === 0 ){
			$user = df_get_record($this->usersTable, array($this->usernameColumn => '='.$_SESSION['UserName']));
			if ( !$user ){
				$user = new Dataface_Record($this->usersTable, array($this->usernameColumn => $_SESSION['UserName']));
			}
		}
		return $user;
		
	}
	
	function getLoggedInUsername(){
		$null = null;
		if ( !$this->authEnabled ) return $null;
		if ( isset($this->delegate) and method_exists($this->delegate, 'getLoggedInUsername') ){
			return $this->delegate->getLoggedInUsername();
		}
		
		$user =& $this->getLoggedInUser();
		if ( isset($user) ){
			return $user->strval($this->usernameColumn);
		}
		
		return $null;
		
	}


}

