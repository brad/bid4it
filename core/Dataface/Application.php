<?php
 
if ( !function_exists('sys_get_temp_dir') )
{
 // Based on http://www.phpit.net/
 // article/creating-zip-tar-archives-dynamically-php/2/
 function sys_get_temp_dir()
 {
   // Try to get from environment variable
   if ( !empty($_ENV['TMP']) )
   {
     return realpath( $_ENV['TMP'] );
   }
   else if ( !empty($_ENV['TMPDIR']) )
   {
     return realpath( $_ENV['TMPDIR'] );
   }
   else if ( !empty($_ENV['TEMP']) )
   {
     return realpath( $_ENV['TEMP'] );
   }

   // Detect by creating a temporary file
   else
   {
     // Try to use system's temporary directory
     // as random name shouldn't exist
     $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
     if ( $temp_file )
     {
       $temp_dir = realpath( dirname($temp_file) );
       unlink( $temp_file );
       return $temp_dir;
     }
     else
     {
       return FALSE;
     }
   }
 }
}
require_once dirname(__FILE__)."/../config.inc.php";
import('Dataface/PermissionsTool.php');
import('Dataface/LanguageTool.php');
define('DATAFACE_STRICT_PERMISSIONS', 100);
	// the minimum security level that is deemed as strict permissions.  
	// strict permissions mean that permissions must be explicitly granted to a 
	// table, record, or action or they will not be accessible

/**
 * The main Application object that handles requests and response. 
 * 
 * This is the one object that is presumed to always exist in a Dataface request.
 * <p>This is a paragraph
 * A new line in the same paragraph <br>
 * </p>
 * <p>New paragraph</p>
 * <h1>heading</h1>
 * <ul>
 * <li>List 1</li>
 * <li>List 2</li>
 * </ul>
 *
 * <code>
 * <?php
 * require_once '../dataface/dataface-public-api.php';
 * df_init(__FILE__, '/dataface');
 * $app =& Dataface_Application::getInstance();
 * $app->display();
 * ?>
 * </code>
 * @example ../docs/examples/Application.example.php Simple example
 *
 * @author Steve Hannah (shannah@sfu.ca)
 * @version 0.6
 * @package Dataface
 * @see dataface-public-api.php
 * @smarty-template templates
 */
class Dataface_Application {
	var $_url_filters = array();
	/**
	 * An associative array of the table names that should be included in the tables menu.
	 */
	var $_tables = array();
	
	/**
	 * An associative array of all of the tables in the database.
	 */
	var $tableIndex = array();
	
	/**
	 * The base url of the site.
	 */
	var $_baseUrl;
	var $_currentTable;
	
	var $memcache;
	
	/**
	 * Database resource handle.
	 */
	var $_db;
	/**
	 * @var array Associative array of request variables.  These modified from $_REQUEST
	 *		to fill in missing values and change some values depending on application
	 * 		preferences.  Access this variable only via Dataface_Application::getQuery()
	 */
	var $_query;
	
	/**
	 * @var array The raw request variables straight from $_REQUEST. The only difference
	 * 	between this array and $_REQUEST is that the $_REQUEST[-__keys__] array
	 *  will be used to override key variables for the current table.
	 */
	var $rawQuery;
	var $queryTool = null;
	var $currentRecord = null;
	var $_customPages;
	
	/**
	 * @var array An array of locations that have been visited.  The keys
	 * are md5 encodings of the values so that the location can be passed
	 * as a GET parameter as an MD5 string.
	 */
	var $locations = null;
	
	/**
	 * @var array Registered listeners for various events in the application.
	 * of the form [Event_name] -> array([callback1], [callback2], ...)
	 *			   [Event_name2] -> array([callback1], ...)
	 */
	var $eventListeners = array();
	
	/**
	 * @var array User preferences.
	 */
	var $prefs = array(
		'show_result_stats'=>1, // The result statistics (e.g. found x of y records in table z)
		'show_jump_menu'=>1,	// The drop-down menu that allows you to "jump" to any record in the found set.
		'show_result_controller'=>1,	// Next, previous, page number .. links...
		'show_table_tabs'=>1,			// Details, List, Find, etc...
		'show_actions_menu'=>1,			// New record, Show all, delete, etc...
		'show_logo'=>1,					// Show logo at top right of app
		'show_tables_menu'=>1,			// The tabs to select a table.
		'show_search'=>1,				// Show search field in upper right.
		'show_record_actions'=>1,		// Show actions related to particular record
		'show_recent_records_menu'=>1,	// Menu to jump to recently visited record (deprecated)
		'show_bread_crumbs' => 1,		// Bread crumbs at top of page to show where you are
		'show_record_tabs' => 1,		// View, Edit, Translate, History, etc...
		'show_record_tree' => 1,		// Tree to navigate the relationships of this record.
		'list_view_scroll_horizontal'=>1, // Whether to scroll list horizontal if it exceeds page width
		'list_view_scroll_vertical'=>1	// Whether to scroll list vertical if it exceeds page height.
	
	);
	
	/**
	 * Keeps track of the table names used in the current request. -- just so 
	 * we know the breadth of the request.
	 */
	var $tableNamesUsed = array();
	var $main_content_only = false;
		// IF true then output only includes main content - not the 
		// surrounding frame.
	
	/**
	 * Reference to the delegate object for this application.  The delegate class is an optional
	 * class that can be placed in the conf/ApplicationDelegate.php file with the class name
	 * "conf_ApplicationDelegate";
	 */
	var $delegate = -1;
	
	/**
	 * A configuration array to store configuration information.
	 */
	var $_conf;
	
	var $errors=array();
	var $messages = array();
	var $debugLog = array();
	var $authenticationTool = null;
	
	/**
	 * An array of text that is to be inserted into the head of the template.
	 * This allows a more efficient method for adding a custom javascript or
	 * stylesheet.
	 */
	var $headContent=array();
	
	function db(){ return $this->_db;}
	
	
	function Dataface_Application($conf = null){
		$this->_baseUrl  = $_SERVER['PHP_SELF'];
		if ( !is_array($conf) ) $conf = array();
		if ( is_readable(DATAFACE_SITE_PATH.'/conf.ini') ){
			$conf = array_merge(parse_ini_file(DATAFACE_SITE_PATH.'/conf.ini', true), $conf);
		}
		
		
		
		if ( !isset( $conf['_tables'] ) ){
			echo 'Error loading config file.  No tables specified.';
			exit;
		}

		
		
		if ( isset( $conf['db'] ) and is_resource($conf['db']) ){
			$this->_db = $conf['db'];
		} else {
			if ( !isset( $conf['_database'] ) ){
				echo 'Error loading config file. No database specified.';
				exit;
			}
			$dbinfo =& $conf['_database'];
			if ( !is_array( $dbinfo ) || !isset($dbinfo['host']) || !isset( $dbinfo['user'] ) || !isset( $dbinfo['password'] ) || !isset( $dbinfo['name'] ) ){
				echo 'Error loading config file.  The database information was not entered correctly.<br>
					 Please enter the database information int its own section of the config file as follows:<br>
					 <pre>
					 [_database]
					 host = localhost
					 user = foo
					 password = bar
					 name = database_name
					 </pre>';
				exit;
			}
			if ( @$dbinfo['persistent'] ){
				$this->_db = mysql_pconnect( $dbinfo['host'], $dbinfo['user'], $dbinfo['password'] );
			} else {
				$this->_db = mysql_connect( $dbinfo['host'], $dbinfo['user'], $dbinfo['password'] );
			}
			if ( !$this->_db ){
				echo 'Error connecting to the database: '.mysql_error();
				exit;
			}
			
			mysql_select_db( $dbinfo['name'] ) or die("Could not select DB: ".mysql_error($this->_db));
		}
		if ( !defined( 'DATAFACE_DB_HANDLE') ) define('DATAFACE_DB_HANDLE', $this->_db);
		
		
		if ( !is_array( $conf['_tables'] ) ){
			echo "<pre>
				Error reading table information from the config file.  Please enter the table information in its own section
				of the ini file as follows:
				[_tables]
				table1 = Table 1 Label
				table2 = Table 2 Label
				</pre>";
			exit;
		}
		
		$this->_tables = $conf['_tables'];
		
		
		
		if ( count($this->_tables) <= 10 ){
			$this->prefs['horizontal_tables_menu'] = 1;
		}
		
		// We will register a _cleanup method to run after code execution is complete.
		register_shutdown_function(array(&$this, '_cleanup'));

		// Set up memcache if it is installed.
		if ( DATAFACE_EXTENSION_LOADED_MEMCACHE ){
			if ( isset($conf['_memcache']) ){
				if ( !isset($conf['_memcache']['host']) ){
					$conf['_memcache']['host'] = 'localhost';
				}
				if ( !isset($conf['_memcache']['port']) ){
					$conf['_memcache']['port'] = 11211;
				}
				$this->memcache =& new Memcache;
				$this->memcache->connect($conf['_memcache']['host'], $conf['_memcache']['port']) or die ("Could not connect to memcache on port 11211");
				
			}
		}
		
		//
		// -------- Set up the CONF array ------------------------
		$this->_conf = $conf;
		
		if ( !isset($this->_conf['_disallowed_tables']) ){
			$this->_conf['_disallowed_tables'] = array();
		}
		
		$this->_conf['_disallowed_tables']['history'] = '/__history$/';
		$this->_conf['_disallowed_tables']['cache'] = '__output_cache';
		$this->_conf['_disallowed_tables']['dataface'] = '/^dataface__/';
		
		
		if ( isset($this->_conf['_modules'])  and count($this->_conf['_modules'])>0 ){
			import('Dataface/ModuleTool.php');
		}

		if ( isset($this->_conf['languages']) ){
			$this->_conf['language_labels'] = $this->_conf['languages'];
			foreach ( array_keys($this->_conf['language_labels']) as $lang_code){
				$this->_conf['languages'][$lang_code] = $lang_code;
			}
		}
		
		if ( @$this->_conf['support_transactions'] ){
			// We will support transactions
			@mysql_query('SET AUTOCOMMIT=0', $this->_db);
			@mysql_query('START TRANSACTION', $this->_db);
		
		}
		if ( !isset($this->_conf['default_ie']) ) $this->_conf['default_ie'] = 'ISO-8859-1';
		if ( !isset($this->_conf['default_oe']) ) $this->_conf['default_oe'] = 'ISO-8859-1';
		if ( isset( $this->_conf['multilingual_content']) || isset($this->_conf['languages']) ){
			$this->_conf['oe'] = 'UTF-8';
			$this->_conf['ie'] = 'UTF-8';
			
			if (function_exists('mb_substr') ){
				// The mbstring extension is loaded
				ini_set('mbstring.internal_encoding', 'UTF-8');
				//ini_set('mbstring.encoding_translation', 'On');
				ini_set('mbstring.func_overload', 7);
				
			}
			
			if ( !isset($this->_conf['default_language']) ){
				if ( count($this->_conf['languages']) > 0 )
					$this->_conf['default_language'] = reset($this->_conf['languages']);
					
				else 
					$this->_conf['default_language'] = 'en';
					
			}
			
		} else {
			$this->_conf['oe'] = $this->_conf['default_oe'];
			$this->_conf['ie'] = $this->_conf['default_ie'];
		}
		
		if ( $this->_conf['oe'] == 'UTF-8' ){
			$res = mysql_query('set character_set_results = \'utf8\'');
			mysql_query("SET NAMES utf8");
		}
		if ( $this->_conf['ie'] == 'UTF-8' ){
			$res = mysql_query('set character_set_client = \'utf8\'');
			//if ( !$res ) trigger_error(mysql_error());
		}
		
		
		if ( isset($this->_conf['use_cache']) and $this->_conf['use_cache'] and !defined('DATAFACE_USE_CACHE') ){
			define('DATAFACE_USE_CACHE', true);
		}
		
		if ( isset($this->_conf['debug']) and $this->_conf['debug'] and !defined('DATAFACE_DEBUG') ){
			define('DATAFACE_DEBUG', true);
		} else if ( !defined('DATAFACE_DEBUG') ){
			define('DATAFACE_DEBUG',false);
		}
		
		if ( !@$this->_conf['config_storage'] ) $this->_conf['config_storage'] = DATAFACE_DEFAULT_CONFIG_STORAGE;
			// Set the storage type for config information.  It can either be stored in ini files or
			// in the database.  Database will give better performance, but INI files may be simpler
			// to manage for simple applications.
		
		if ( !isset($this->_conf['garbage_collector_threshold']) ){
			/**
			 * The garbage collector threshold is the number of seconds that "garbage" can
			 * exist for before it is deleted.  Examples of "garbage" include import tables
			 * (ie: temporary tables created as an intermediate point to importing data).
			 */
			$this->_conf['garbage_collector_threshold'] = 10*60;
		}
		
		if ( !isset($this->_conf['multilingual_content']) ) $this->_conf['multilingual_content'] = false;
			// whether or not the application will use multilingual content.
			// multilingual content enables translated versions of content to be stored in
			// tables using naming conventions.
			// Default to false because this takes a performance hit (sql queries take roughly twice
			// as long because they have to be parsed first.
		
		if ( !isset($this->_conf['cookie_prefix']) ) $this->_conf['cookie_prefix'] = 'dataface__';
		
		if ( !isset($this->_conf['security_level']) ){
			// Default security is strict if security is not specified.  This change is effectivce
			// for Dataface 0.6 .. 0.5.3 and earlier had a loose permissions model by default that 
			// could be tightened using delegate classes.
			$this->_conf['security_level'] = 0; //DATAFACE_STRICT_PERMISSIONS;
		}
		
		
		if ( !isset($this->_conf['default_action']) ){
			// The default action defines the action that should be set if no
			// other action is specified.
			$this->_conf['default_action'] = 'list';
		}
		
		if ( !isset($this->_conf['default_browse_action']) ){
			$this->_conf['default_browse_action'] = 'view';
		}
		
		
		if ( !isset($this->_conf['default_mode'] ) ) $this->_conf['default_mode'] = 'list';
		
		if ( !isset($this->_conf['default_limit']) ){
			$this->_conf['default_limit'] = 30;
		}
		
		if ( !isset($this->_conf['default_table'] ) ){
			// The default table is the table that is used if no other table is specified.
			foreach ($this->_tables as $key=>$value){
				$this->_conf['default_table'] = $key;
				
				break;
			}
		}
		
		if ( !isset($this->_conf['auto_load_results']) ) $this->_conf['auto_load_results'] = false;
		
		if ( !isset( $this->_conf['cache_dir'] ) ){
			if ( ini_get('upload_tmp_dir') ) $this->_conf['cache_dir'] = ini_get('upload_tmp_dir');
			else $this->_conf['cache_dir'] = '/tmp';
		}
		
		if ( !isset( $this->_conf['default_table_role'] ) ){
			
			if ( $this->_conf['security_level'] >= DATAFACE_STRICT_PERMISSIONS ){
				$this->_conf['default_table_role'] = 'NO ACCESS';
			} else {
				$this->_conf['default_table_role'] = 'ADMIN';
			}
			
		}
		
		if ( !isset( $this->_conf['default_field_role'] ) ){
			if ( $this->_conf['security_level'] >= DATAFACE_STRICT_PERMISSIONS ){
				$this->_conf['default_field_role'] = 'NO ACCESS';
			} else {
				$this->_conf['default_field_role'] = 'ADMIN';
				
			}
		}
		
		if ( !isset( $this->_conf['default_relationship_role'] ) ){
			if ( $this->_conf['security_level'] >= DATAFACE_STRICT_PERMISSIONS ){
				$this->_conf['default_relationship_role'] = 'READ ONLY';
			} else {
				$this->_conf['default_relationship_role'] = 'ADMIN';
				
			}
		}
		
		// Set the language.
		// Language is stored in a cookie.  It can be changed by passing the -lang GET var with the value
		// of a language.  e.g. fr, en, cn
		if ( !isset( $this->_conf['default_language'] ) ) $this->_conf['default_language'] = 'en';
		$prefix = $this->_conf['cookie_prefix'];
		//print_r($_COOKIE);
		if ( isset( $_REQUEST['-lang'] ) ){
			$this->_conf['lang'] = $_REQUEST['-lang'];
			if ( @$_COOKIE[$prefix.'lang'] !== $_REQUEST['-lang'] ){
				setcookie($prefix.'lang', $_REQUEST['-lang'], null, '/');
			}
		} else if ( isset( $_COOKIE[$prefix.'lang']) ){
			$this->_conf['lang'] = $_COOKIE[$prefix.'lang'];
		} else {
			import('I18Nv2/I18Nv2.php');
			$negotiator =& I18Nv2::createNegotiator($this->_conf['default_language'], 'UTF-8');
			$this->_conf['lang'] = $negotiator->getLanguageMatch();
			setcookie($prefix.'lang', $this->_conf['lang'], null, '/');
		}
		
		if ( !isset( $this->_conf['languages'] ) ) $this->_conf['languages'] = array('en');
		else if ( !is_array($this->_conf['languages']) ) $this->_conf['languages'] = array($this->_conf['languages']);
		
		
		// Set the mode (edit or view)
		if ( isset($_REQUEST['-usage_mode'] )){
			$this->_conf['usage_mode'] = $_REQUEST['-usage_mode'];
			if (@$_COOKIE[$prefix.'usage_mode'] !== $_REQUEST['-usage_mode']){
				setcookie($prefix.'usage_mode', $_REQUEST['-usage_mode'], null, '/');
			}
		} else if ( isset( $_COOKIE[$prefix.'usage_mode'] ) ){
			$this->_conf['usage_mode'] = $_COOKIE[$prefix.'usage_mode'];
		} else if ( !isset($this->_conf['usage_mode']) ){
			$this->_conf['usage_mode'] = 'view';
		}
		
		define('DATAFACE_USAGE_MODE', $this->_conf['usage_mode']);
		
		if ( @$this->_conf['enable_workflow'] ){
			import('Dataface/WorkflowTool.php');
		}
		
		
		
		
		// ------- Set up the current query ---------------------------------
		
		if ( isset($_REQUEST['__keys__']) and is_array($_REQUEST['__keys__']) ){
			$query = $_REQUEST['__keys__'];
			foreach ( array_keys($_REQUEST) as $key ){
				if ( $key{0} == '-' and !in_array($key, array('-search','-cursor','-skip','-limit'))){
					$query[$key] = $_REQUEST[$key];
				}
			}
		} else {
			$query = $_REQUEST;
		}
		$this->rawQuery = $query;
		
		if ( !isset( $query['-table'] ) ) $query['-table'] = $this->_conf['default_table'];
		$this->_currentTable = $query['-table'];
		
		
		if ( !@$query['-action'] ) {
			$query['-action'] = $this->_conf['default_action'];
			$this->_conf['using_default_action'] = true;
		}
		
		$query['--original_action'] = $query['-action'];
		if ( $query['-action'] == 'browse') {
			if ( isset($query['-relationship']) ){
				$query['-action'] = 'related_records_list';
			} else if ( isset($query['-new']) and $query['-new']) {
				$query['-action'] = 'new';
			} else {
				$query['-action'] = $this->_conf['default_browse_action']; // for backwards compatibility to 0.5.x
			}
		} else if ( $query['-action'] == 'find_list' ){
			$query['-action'] = 'list';
		}
		if ( !isset( $query['-cursor'] ) ) $query['-cursor'] = 0;
		if ( !isset( $query['-skip'] ) ) $query['-skip'] = 0;
		if ( !isset( $query['-limit'] ) ) $query['-limit'] = $this->_conf['default_limit'];
		
		if ( !isset( $query['-mode'] ) ) $query['-mode'] = $this->_conf['default_mode'];
		$this->_query =& $query;
		
		if ( isset( $query['--msg'] ) ) {
			$this->addMessage($query['--msg']);
		}
		
		
		
		
		if ( isset($query['--error']) ) $this->addError(PEAR::raiseError($query['--error']));
		
		// Now allow custom setting of theme
		if ( isset($query['-theme']) ){
			if ( !isset($this->_conf['_themes']) ) $this->_conf['_themes'] = array();
			$this->_conf['_themes'][basename($query['-theme'])] = 'themes/'.basename($query['-theme']);
		}
		
		

	}
	
	/**
	 * Adds some content meant to be inserted in the head of the application.
	 * @param string $content
	 *
	 * @since 1.0
	 *
	 */
	function addHeadContent($content){
		$this->headContent[] = $content;
	}
	
	function startSession($conf=null){
		//echo "In startSession()";
		if ( session_id() == "" ){
			if ( !isset($conf) ){
				if ( isset($this->_conf['_auth']) ) $conf = $this->_conf['_auth'];
				else $conf = array();
			}
			
			// path for cookies
			$cookie_path = "/";
			if ( isset($conf['cookie_path']) ){
				$cookie_path = $conf['cookie_path'];
				if ( substr($cookie_path,0,4) == 'php:' ){
					$cookie_path_expr = substr($cookie_path,4);
					eval('$cookie_path = '.$cookie_path_expr.';');
				}
			}
			
			if ( $cookie_path{strlen($cookie_path)-1} != '/' ) $cookie_path .= '/';
			
			// timeout value for the cookie
			$cookie_timeout = (isset($conf['session_timeout']) ? $conf['session_timeout'] : 24*60*60);
			
			
			// timeout value for the garbage collector
			//   we add 300 seconds, just in case the user's computer clock
			//   was synchronized meanwhile; 600 secs (10 minutes) should be
			//   enough - just to ensure there is session data until the
			//   cookie expires
			$garbage_timeout = $cookie_timeout + 600; // in seconds
			
			// set the PHP session id (PHPSESSID) cookie to a custom value
			session_set_cookie_params($cookie_timeout, $cookie_path);
			
			// set the garbage collector - who will clean the session files -
			//   to our custom timeout
			ini_set('session.gc_maxlifetime', $garbage_timeout);
			
			// we need a distinct directory for the session files,
			//   otherwise another garbage collector with a lower gc_maxlifetime
			//   will clean our files aswell - but in an own directory, we only
			//   clean sessions with our "own" garbage collector (which has a
			//   custom timeout/maxlifetime set each time one of our scripts is
			//   executed)
			strstr(strtoupper(substr(@$_SERVER["OS"], 0, 3)), "WIN") ? 
				$sep = "\\" : $sep = "/";
			$sessdir = session_save_path(); //ini_get('session.save_path');
			$levels = '';
			if (strpos($sessdir, ";") !== FALSE){
				$levels = substr($sessdir, 0, strpos($sessdir, ";")).';';
 				 $sessdir = substr($sessdir, strpos($sessdir, ";")+1);
 		    }
			if ( !$sessdir ) $sessdir = sys_get_temp_dir(); //'/tmp';
			if ( $sessdir and $sessdir{strlen($sessdir)-1} == '/' ) $sessdir = substr($sessdir,0, strlen($sessdir)-1);
			
			if ( @$conf['subdir'] ) $subdir = $conf['subdir'];
			else $subdir = md5(DATAFACE_SITE_PATH);
			if ( !$subdir ) $subdir = 'dataface';
			$sessdir .= "/".$subdir;
			
	
			if (!is_dir($sessdir)) { 
				$res = @mkdir($sessdir, 0777);
				if ( !$res ){
					import('Dataface/Error.php');
					echo "<h2>Configuration Required</h2>
						<p>Dataface was unable to create the directory '$sessdir' 
						to store its session files.</p>
						<h3>Possible reasons for this:</h3>
						<ul>
							<li>The script does not have permission to create the directory.</li>
							<li>The server is operating in safe mode.</li>
						</ul>
						<h3>Possible Solutions for this:</h3>
						<ul>
							<li>Make the ".dirname($sessdir)." writable by the web server.  E.g. chmod 0777 ".dirname($sessdir).".</li>
							<li>Manually create the '$sessdir' directory and make it writable by the web server.</li>
							<li>Change the session save path to a directory to which you have write permissions by adding the following to the
							   beginning of your application's index.php file:
							   <code><pre>session_save_path('/path/to/dir');</pre></code>
							</li>
							<li>If none of these solves the problem, visit the Dataface forum
							 at <a href=\"http://fas.sfu.ca/dataface/forum\">http://fas.sfu.ca/dataface/forum</a> 
							 and ask for help.
							 </li>
					    </ul>
					    <h3>Debugging Information:</h3>
					    <div>
					    ".Dataface_Error::printStackTrace()."</div>";
					exit;
				}
			}
			session_save_path($levels.$sessdir);
			
			session_start();	// start the session
			header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
		} else {
			//echo "Session already started";
		}
		
		if ( isset( $_SESSION['--msg'] ) ){
			$this->addMessage($query['--msg']);
			unset($_SESSION['--msg']);
		}
	
	
	}
	
	function writeSessionData(){
	
		if ( isset($this->locations) ) $_SESSION['locations'] = serialize($this->locations);
	}
	
	function encodeLocation($url){
		if ( !isset($this->locations) and isset($_SESSION['locations']) ) $this->locations = unserialize($_SESSION['locations']);
		else if ( !isset($this->locations) ) $this->locations = array();
		$key = md5($url);
		$this->locations[$key] = $url;
		return $key;
	}
	
	function decodeLocation($key){
		if ( !isset($this->locations) and isset($_SESSION['locations']) ) $this->locations = unserialize($_SESSION['locations']);
		else if ( !isset($this->locations) ) $this->locations = array();
		
		if ( isset($this->locations[$key]) ){
			$url = $this->locations[$key];
			unset($this->locations[$key]);
			return $url;
		
		} else {
			return null;
		}
	
	}


	/**
	 * Handle a request.  This method is the starting point for all Dataface application requests.
	 * It will delegate the request to the appropriate handler.
	 * The order of delegation is as follows:
	 *  0. Uses the ActionTool to check permissions for the action.  If permissions are not granted,
	 *		dispatch the error handler.  If permissions are granted then we continue down the delegation
	 *		chain.
	 *  1. If the current table's delegate class defines a handleRequest() method, then call that.
	 *	2. If the current table's delegate class does not have a handleRequest() method or that method
	 *		returns a PEAR_Error object with code E_DATAFACE_REQUEST_NOT_HANDLED, then check for a handler
	 *		bearing the name of the action in one of the actions directories.  Check the directories 
	 *		in the following order:
	 *		a. <site url>/tables/<table name>/actions
	 *		b. <site url>/actions
	 *		b. <dataface url>/actions
	 *	3. If no handler can be found then use the default handler.  The default handler can be quite 
	 *		powerful as it accepts the '-template' query parameter to use a specific template for display.
	 */
	function handleRequest(){
		
		
		if ( (@$_GET['-action'] != 'getBlob') and isset( $this->_conf['_output_cache'] ) and @$this->_conf['_output_cache']['enabled'] and count($_POST) == 0){
			import('Dataface/OutputCache.php');
			$oc =& new Dataface_OutputCache($this->_conf['_output_cache']);
			$oc->ob_start();
			
		}
		import('Dataface/ActionTool.php');
		import('Dataface/PermissionsTool.php');
		import('Dataface/Table.php');
		
		
		
		$applicationDelegate =& $this->getDelegate();
		if ( isset($applicationDelegate) and method_exists($applicationDelegate, 'beforeHandleRequest') ){
			// Do whatever we need to do before the request is handled.
			$applicationDelegate->beforeHandleRequest();
		}
		
		
		// Set up security filters
		$query =& $this->getQuery();
		$table =& Dataface_Table::loadTable($query['-table']);
		//$table->setSecurityFilter();
		/*
		 * Set up some preferences for the display of the application.
		 * These can be overridden by the getPreferences() method in the
		 * application delegate class.
		 */
		if ( isset($this->_conf['_prefs']) and is_array($this->_conf['_prefs']) ){
			$this->prefs = array_merge($this->prefs,$this->_conf['_prefs']);
		}
		if ( @$this->_conf['hide_nav_menu'] ){
			$this->prefs['show_tables_menu'] = 0;
		}
		
		if ( @$this->_conf['hide_view_tabs'] ){
			$this->prefs['show_table_tabs'] = 0;
		}
		
		if ( @$this->_conf['hide_result_controller'] ){
			$this->prefs['show_result_controller'] = 0;
		}
		
		if ( @$this->_conf['hide_table_result_stats'] ){
			$this->prefs['show_result_stats'] = 0;
		}
		
		if ( @$this->_conf['hide_search'] ){
			$this->prefs['show_search'] = 0;
		}
		
		if ( !isset($this->prefs['disable_ajax_record_details']) ){
			$this->prefs['disable_ajax_record_details'] = 1;
		}
		
		if ( $query['-action'] == 'login_prompt' ) $this->prefs['no_history'] = 1;
		
		
		if ( isset($applicationDelegate) and method_exists($applicationDelegate, 'getPreferences') ){
			$this->prefs = array_merge($this->prefs, $applicationDelegate->getPreferences());
		}
		
		// Check to make sure that this table hasn't been disallowed
		$disallowed = false;
		if ( isset($this->_conf['_disallowed_tables']) ){
			foreach ( $this->_conf['_disallowed_tables'] as $name=>$pattern ){
				if ( $pattern{0} == '/' and preg_match($pattern, $query['-table']) ){
					$disallowed = true;
					break;
				} else if ( $pattern == $query['-table'] ){
					$disallowed = true;
					break;
				}
			}
		}
		
		if ( $disallowed and isset($this->_conf['_allowed_tables']) ){
			foreach ($this->_conf['_allowed_tables'] as $name=>$pattern ){
				if ( $pattern{0} == '/' and preg_match($pattern, $query['-table']) ){
					$disallowed = false;
					break;
				} else if ( $pattern == $query['-table'] ){
					$disallowed = false;
					break;
				}
			}
		}
		
		
		if ( $disallowed ){
			return Dataface_Error::permissionDenied(
				Dataface_LanguageTool::translate(
					/*i18n id*/
					"Permission Denied. This table has been disallowed in the conf.ini file",
					/* default error message */
					"Permission denied because this table has been disallowed in the conf.ini file '"
				)
			);
			
		}
		
		
		$actionTool =& Dataface_ActionTool::getInstance();

		
		//if ( $this->_conf['multilingual_content'] ){
			//import('I18Nv2/I18Nv2.php');
     		//I18Nv2::autoConv();
     	//}
		
		$params = array(
			'table'=>$query['-table'],
			'name'=>$query['-action']);
		
		if ( strpos($query['-action'], 'custom_') === 0 ){
			$action = array(
				'name' => $query['-action'],
				'page' => substr($query['-action'], 7),
				'permission' => 'view',
				'mode' => 'browse',
				'custom' => true
				);
		} else {
			$action = $actionTool->getAction($params);
			if ( is_array($action) and @$action['related'] and @$query['-relationship'] ){
				// This action is to be performed on the currently selected relationship.
				$raction = $table->getRelationshipsAsActions(array(), $query['-relationship']);
				if ( is_array($raction) ){
					$action = array_merge($action,$raction); 
				}
			}
			if ( is_array($action) and isset($action['delegate']) ){
				$params['name'] = $query['-action'] = $action['delegate'];
				$tmp = $actionTool->getActions($params);
				unset($action);
				$action =& $tmp;
				unset($tmp);
			} 
			if ( is_array($action) and isset($action['auth_type']) ){
				$authTool =& $this->getAuthenticationTool();
				$authTool->setAuthType($action['auth_type']);
			}
			
		}
	
	
		if ( (PEAR::isError($action) or !@$action['permission']) and $this->_conf['security_level'] >= DATAFACE_STRICT_PERMISSIONS ){
			// The only reason getAction() will return an error is if the specified action could not be found.
			// If the application is set to use strict permissions and no action was defined in the ini file
			// then this action cannot be performed.  Strict permissions mode requires that permissions be 
			// strictly set or permission will be denied.
			return Dataface_Error::permissionDenied(
				Dataface_LanguageTool::translate(
					/*i18n id*/
					"Permission Denied. No action found in strict permissions mode",
					/* default error message */
					"Permission denied for action '".
						$query['-action'].
					"'.  No entry for this action was found in the actions.ini file.  
					You are currently using strict permissions mode which requires that you define all actions that you want to use in the actions.ini file with appropriate permissions information.", 
					/* i18n parameters */
					array('action'=>$query['-action'])
				)
			);
			
		} 
		
		else if ( PEAR::isError($action) ){
			$action = array('name'=>$query['-action'], 'label'=>$query['-action']);
		}
		
		// Step 1:  See if the delegate class has a handler.
		
		$delegate =& $table->getDelegate();
		$handled = false;
		if ( method_exists($delegate,'handleRequest') ){
			$result = $delegate->handleRequest();
			if ( PEAR::isError($result) and $result->getCode() === DATAFACE_E_REQUEST_NOT_HANDLED ){
				$handled = false;
			} else if ( PEAR::isError($result) ){
				return $result;
			} else {
				$handled = true;
			}
		}
		if ( isset($action['mode']) and $action['mode'] ) $query['-mode'] = $action['mode'];
		
		// Step 2: Look to see if there is a handler defined
		if ( isset($action['custom']) ){
			$locations = array( DATAFACE_PATH.'/actions/custom.php'=>'dataface_actions_custom');
		} else {
			$locations = array();
			
			$locations[ DATAFACE_SITE_PATH.'/tables/'.$query['-table'].'/actions/'.$query['-action'].'.php' ] = 'tables_'.$query['-table'].'_actions_'.$query['-action'];
			$locations[ DATAFACE_SITE_PATH.'/actions/'.$query['-action'].'.php' ] = 'actions_'.$query['-action'];
			
			if ( isset($this->_conf['_modules']) and count($this->_conf['_modules']) > 0 ){
				foreach ($this->_conf['_modules'] as $modname=>$modpath){
					if ( $modpath{0} == '/' )
						$locations[ dirname($modpath).'/actions/'.$query['-action'].'.php' ] = 'actions_'.$query['-action'];
					else {
						$locations[ DATAFACE_PATH.'/'.dirname($modpath).'/actions/'.$query['-action'].'.php' ] = 'actions_'.$query['-action'];
					}
				}
			}
			
			$locations[ DATAFACE_PATH.'/actions/'.$query['-action'].'.php' ] = 'dataface_actions_'.$query['-action'];
			$locations[ DATAFACE_PATH.'/actions/default.php' ] = 'dataface_actions_default';
				
		}
		$doParams = array('action'=>&$action);
			//parameters to be passed to the do method of the handler
			
		
		foreach ($locations as $handlerPath=>$handlerClassName){
			if ( is_readable($handlerPath) ){
				import($handlerPath);
				$handler =& new $handlerClassName;
				$params  = array();
				if ( !PEAR::isError($action) and method_exists($handler, 'getPermissions') ){
					// check the permissions on this action to make sure that we are 'allowed' to perform it
					// this method will return an array of Strings that are names of permissions granted to
					// the current user.
					$permissions =& $handler->getPermissions($params);
				//} else if ( $applicationDelegate !== null and method_exists($applicationDelegate, 'getPermissions') ){
				//	$permissions =& $applicationDelegate->getPermissions($params);
					
			
				
				} else {
					$permissions = $this->getPermissions();
				}
				
				if ( isset($action['permission']) && !(isset($permissions[$action['permission']]) and $permissions[$action['permission']]) ){
					return Dataface_Error::permissionDenied(
						Dataface_LanguageTool::translate(
							"Permission Denied for action.", /*i18n id*/
							/* Default error message */
							"Permission to perform action '".
							$action['name'].
							"' denied.  
							Requires permission '".
							$action['permission'].
							"' but only granted '".
							Dataface_PermissionsTool::namesAsString($permissions)."'.", 
							/* i18n parameters */
							array('action'=>$action, 'permissions_granted'=>Dataface_PermissionsTool::namesAsString($permissions))
						)
					);
				
				}
				
				if ( method_exists($handler, 'handle') ){
					
					
					$result = $handler->handle($doParams);
					if ( PEAR::isError($result) and $result->getCode() === DATAFACE_E_REQUEST_NOT_HANDLED ){
						continue;
					}
					return $result;
				}
				
				
			}
			
		}
		
		trigger_error(df_translate('scripts.Dataface.Application.handleRequest.NO_HANDLER_FOUND',"No handler found for request.  This should never happen because, at the very least, the default handler at dataface/actions/default.php should be called.  Check the permissions on dataface/actions/default.php to make sure that it is readable by the web server.").Dataface_Error::printStackTrace(), E_USER_ERROR);
		
		
		
	
	}
	/**
	 * Returns a reference to the delegate object for this application.
	 * The delegate object can be used to define custom functionality for the application.
	 *
	 * @return conf_ApplicationDelegate
	 */
	function &getDelegate(){
		if ( $this->delegate === -1 ){
			$delegate_path = DATAFACE_SITE_PATH.'/conf/ApplicationDelegate.php';
			if ( is_readable($delegate_path) ){
				import($delegate_path);
				$this->delegate = new conf_ApplicationDelegate();
			} else {
				$this->delegate = null;
			}
		}
		return $this->delegate;
				
	}
	
	/**
	 * Returns a reference to the current query object.  This is very similar to the $_GET
	 * and $_REQUEST globals except this array has been filled in with missing values.
	 *
	 * @return array Reference to current query object.
	 */
	function &getQuery(){
		return $this->_query;
	}
	
	/**
	 * Returns a query parameter.
	 *
	 * @smarty-block query_param A query parameter tag.
	 * Here is some more stuff.
	 */
	function &getQueryParam($key){
		if ( isset( $this->_query['-'.$key] ) ){
			return $this->_query['-'.$key];
		} else {
			$null = null;
			return $null;
		}
	}
	
	function &getResultSet(){
		if ( $this->queryTool === null ){
			import('Dataface/QueryTool.php');
			$this->queryTool =& Dataface_QueryTool::loadResult($this->_query['-table'], $this->db(), $this->_query);
		}
		return $this->queryTool;
	
	}
	
	function &getRecord(){
		$null = null;
		if ( $this->currentRecord === null ){
			$query =& $this->getQuery();
			$q=array();
			if ( isset($_REQUEST['__keys__']) and is_array($_REQUEST['__keys__']) ){
				foreach ($_REQUEST['__keys__'] as $key=>$val) $q[$key] = '='.$val;
				$this->currentRecord =& df_get_record($query['-table'], $q);
			} else if ( isset($_REQUEST['-__keys__']) and is_array($_REQUEST['-__keys__']) ){
				foreach ($_REQUEST['-__keys__'] as $key=>$val) $q[$key] = '='.$val;
				$this->currentRecord =& df_get_record($query['-table'], $q);
			} else if ( isset($_REQUEST['--__keys__']) and is_array($_REQUEST['--__keys__']) ){
				foreach ($_REQUEST['--__keys__'] as $key=>$val) $q[$key] = '='.$val;
				$this->currentRecord =& df_get_record($query['-table'], $q);
			} else if ( isset($_REQUEST['--recordid']) ){
				$this->currentRecord =& df_get_record_by_id($_REQUEST['--recordid']);
			} else if ( isset($_REQUEST['-recordid']) ){
				$this->currentRecord =& df_get_record_by_id($_REQUEST['-recordid']);
			} else {
				$rs =& $this->getResultSet();
				$this->currentRecord =& $rs->loadCurrent();
			}
			if ( $this->currentRecord === null ) $this->currentRecord = -1;
		}
		if ( $this->currentRecord === -1 || !$this->currentRecord ) return $null;
		return $this->currentRecord;
	}
	
	function recordLoaded(){
		return ( $this->currentRecord !== null);
	}
	
	
	/**
	 *  Updates the metadata tables to make sure that they are current.
	 * Meta data tables are tables created by dataface to enrich the database.
	 * For example, if workflow is enabled via the enable_workflow flag in the
	 * conf.ini file, then dataface will maintain a workflow table to correspond
	 * to each table in the database.  This method will make sure that the
	 * workflow table is consistent with base table.
	 */
	 function refreshSchemas($tablename){
		if ( @$this->_conf['metadata_enabled'] ){
			$metadataTool = new Dataface_MetadataTool();
			$metadataTool->updateWorkflowTable($tablename);
		}
	}
	
	function &getAction(){
		import('Dataface/ActionTool.php');
		$actionTool =& Dataface_ActionTool::getInstance();
		return $actionTool->getAction(array('name'=>$this->_query['-action']));
	}
	
	
	
	/**
	 * Parses a request to obtain a related blob object.
	 *
	 * Requests can ask for a related record's blob field.  When this happens
	 * it has to be converted to a normal blob request.
	 *
	 * @param array $request The _REQUEST array.
	 * @return array
	 */
	function _parseRelatedBlobRequest($request){
		import('Dataface/Application/blob.php');
		return Dataface_Application_blob::_parseRelatedBlobRequest($request);
	}
	

	
	
	/**
	 *
	 * Blob requests are ones that only want the content of a blob field in the database.
	 * These requests are special in that they will not generally return a content-type of
	 * text/html.  These are often images.
	 *
	 * @param $request  A reference to the global $_REQUEST variable generally.
	 *
	 */
	function _handleGetBlob($request){
		import('Dataface/Application/blob.php');
		return Dataface_Application_blob::_handleGetBlob($request);
	}
	
	function getSiteTitle(){
		$query =& $this->getQuery();
		if ( isset($this->_conf['title']) ) return $this->parseString($this->_conf['title']);
		else if ( ($record =& $this->getRecord()) && $query['-mode'] == 'browse'  ){
			return $record->getTitle().' - Dataface Application';
		} else return $this->parseString('{$table} - Dataface Application');
	
	}
	
	/**
	 * Returns the config array as loaded from the conf.ini file, except that 
	 * it opens up the opportunity for the delegate class to load values into
	 * the config using its own conf() method.
	 * 
	 * This is useful if an application wants to store config information in
	 * the database and still make it available to the application.
	 */
	function &conf(){	
		static $loaded = false;
		if ( !$loaded ){
			$loaded = true;
			$del =& $this->getDelegate();
			if ( isset($del) and method_exists($del,'conf') ){
				$conf = $del->conf();
				if ( !is_array($conf) ) trigger_error("The Application Delegate class defined a method 'conf' that must return an array, but returns something else.", E_USER_ERROR);
				foreach ( $conf as $key=>$val){
					if ( isset($this->_conf[$key]) ){
						if ( is_array($this->_conf[$key]) and is_array($val) ){
							$this->_conf[$key] = array_merge($this->_conf[$key], $val);
						} else {
							$this->_conf[$key] = $val;
						}
					} else {
						$this->_conf[$key] = $val;
					}
				}
				
			}
			
		}
		return $this->_conf;
		
	}
	
	
	/**
	 * Evaluates a string expression replacing PHP variables with appropriate values
	 * in the current record.
	 * @param string $expression A string containing PHP variables that need to be evaluated.
	 * @param Dataface_Record $context A Dataface_Record, Dataface_RelatedRecord object, or array whose values are treated as local
	 *		  variables when evaluating the expression.
	 *
	 * Example expressions:
	 *		'${site_href}?-table=Profiles&ProfileID==${ProfileID}'
	 *			-- in the above example, ${site_href} would be replaced with the url (including 
	 *				script name) of the site, and ${ProfileID} would be replaced with
	 *				the value of the ProfileID field in the current record.
	 */
	var $_parseStringContext=array();
	function parseString($expression, $context=null){
		// make sure that the expression doesn't try to break the double quotes.
		if ( strpos($expression, '"') !== false ){
			trigger_error(
				df_translate(
					'scripts.Dataface.Application.parseString.ERROR_PARSING_EXPRESSION_DBL_QUOTE',
					"Invalid expression (possible hacking attempt in Dataface_Application::eval().  Expression cannot include double quotes '\"', but recieved '".$expression."'.",
					array('expression'=>$expression)). 
				Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
 
		$site_url = DATAFACE_SITE_URL;
		$site_href = DATAFACE_SITE_HREF;
		$dataface_url = DATAFACE_URL;
		$table = $this->_currentTable;
		$query =& $this->getQuery();
		$app =& $this;
		$resultSet =& $app->getResultSet();
		if ( isset($context['record']) ){

			$record =& $context['record'];
		} else {
			$record =& $app->getRecord();
		}
		$version = phpversion();
		if ( floatval($version) < 5 ){
			// php 4 doesn't seem to allow full parsing of functions inside { } tags.
			// so we have to do it manually.
			$this->_parseStringContext = array(
				'site_url'=>&$site_url,
				'site_href'=>&$site_href,
				'dataface_url'=>&$dataface_url,
				'table'=>&$table,
				'query'=>&$query,
				'app'=>&$app,
				'resultSet'=>&$resultSet,
				'record'=>&$record,
				'version'=>&$version,
				'context'=>&$context);
			$parsed = preg_replace_callback('/(\${0,1})\{([^\}]+)\}/', array(&$this, '_parsePregMatch'), $expression);
				
		} else {

			@eval('$parsed = "'.$expression.'";');
		}
		if ( !isset( $parsed ) ){
			trigger_error(df_translate('scripts.Dataface.Application.parseString.ERROR_PARSING_EXPRESSION',"Error parsing expression '$expression'. ", array('expression'=>$expression)).Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		return $parsed;
	
	}
	
	/**
	 * Returns the permissions that are currently available to the user in the current 
	 * context.  If we are in browse mode then permissions are checked against the 
	 * current record.  Otherwise, permissions are checked against the table.
	 */
	function getPermissions(){
		$query =& $this->getQuery();
		
		//switch ($query['-mode']){
		//	case 'browse':
				$record =& $this->getRecord();
				if ( $record and is_a($record, 'Dataface_Record') ){
					$params = array();
					return Dataface_PermissionsTool::getPermissions($record, $params);
				} else {
					$table =& Dataface_Table::loadTable($query['-table']);
					$params = array();
					return Dataface_PermissionsTool::getPermissions($table, $params);
				}
			//	break;
			//default:
			//
			//	$table =& Dataface_Table::loadTable($query['-table']);
			//	$params = array();
			//	return Dataface_PermissionsTool::getPermissions($table, $params);
		//}
	}
	
	function checkPermission($perm){
		$perms = $this->getPermissions();
		$result = (isset($perms[$perm]) and $perms[$perm]);
		return $result;
	}
	
	/**
	 * Used by preg_replace_callback to replace a match with its PHP parsed equivalent.
	 */
	function _parsePregMatch($matches){
		extract($this->_parseStringContext);
		return @eval('return '.$matches[1].$matches[2].';');
	}
	
	function testCondition($condition, $context=null){

		$site_url = DATAFACE_SITE_URL;
		$site_href = DATAFACE_SITE_HREF;
		$dataface_url = DATAFACE_URL;
		$table = $this->_currentTable;
		$query =& $this->getQuery();
		$app =& $this;
		$resultSet =& $app->getResultSet();
		if ( isset($context['record']) ) $record =& $context['record'];
		else $record =& $app->getRecord();
		
		return @eval('return ('.$condition.');');
	}	
	
	
	function registerUrlFilter( $filter ){
		$this->_filters[] = $filter;
	}
	
	
	
	function filterUrl($url){
		if ( !eregi( '[&\?]-table', $url ) ){
			if ( eregi( '\?', $url ) ){
				$url .= '&-table='.$this->_currentTable;
			} else {
				$url .= '?-table='.$this->_currentTable;
			}
		}
		
		foreach ($this->_url_filters as $filter){
			$url = call_user_func($filter, $url);
		}
		return $url;
	
	}
	
	/**
	 * Returns reference to the singleton instance of this class.
	 *
	 */
	function &getInstance($conf=null){
		static $instance = array();
		//static $blobRequestCount = 0;
		if ( !isset( $instance[0] ) ){
			$instance[0] =& new Dataface_Application($conf);
			//if ( $instance[0]->_query['-action'] == 'getBlob' ){
			//	
			//	if ($blobRequestCount++ == 0 ){
			///		$instance[0]->_handleGetBlob($_REQUEST);
			//		exit;
			//	}
			//}
			if ( !defined('DATAFACE_APPLICATION_LOADED') ){
				define('DATAFACE_APPLICATION_LOADED', true);
			}
		}
		
		return $instance[0];
	}
	
	function init(){
	
	}

	
	/**
	 * Obtains reference to the authentication tool.
	 */
	function &getAuthenticationTool(){
		$null = null;
		if ( !isset($this->authenticationTool) ){
			
			if ( isset($this->_conf['_auth']) ){
				import('Dataface/AuthenticationTool.php');
				$this->authenticationTool =& Dataface_AuthenticationTool::getInstance($this->_conf['_auth']);
			} else {
				return $null;
			}
		}
			
		return $this->authenticationTool;
	}
	
	/**
	 * Displays the Dataface application.
	 */
	function display($main_content_only=false){
		// ---------------- Set the Default Character set for output -----------
		$this->main_content_only = $main_content_only;
		$this->startSession();
		
		// handle authentication
		if ( isset($this->_conf['_auth']) ){
			// The config file _auth section is there so we will be using authentication.
	
			$loginPrompt = false;	// flag to indicate if we should show the login prompt
			$permissionDenied = false;// flag to indicate if we should show permission denied
			$permissionError = ''; //Placeholder for permissions error messages
			$loginError = ''; // Placeholder for login error messages.
			
			$authTool =& $this->getAuthenticationTool();
			
			$auth_result = $authTool->authenticate();
			if ( PEAR::isError($auth_result) and $auth_result->getCode() == DATAFACE_E_LOGIN_FAILURE ){
				// There was a login failure, show the login prompt
				$loginPrompt = true;
				$loginError = $auth_result->getMessage();
			} else if ( $authTool->isLoggedIn() ){
				// The user is logged in ok
				// Handle the request
				$result = $this->handleRequest();
				if ( Dataface_Error::isPermissionDenied($result) ){
					// Permission was denied on the request.  Since the user is already
					// logged in, there is no use giving him the login prompt.  Just give
					// him the permission denied screen.
					$permissionDenied = true;
					$permissionError = $result->getMessage();
				}
			} else if ( isset($this->_conf['_auth']['require_login']) and $this->_conf['_auth']['require_login'] ){
				// The user is not logged in and login is required for this application
				// Show the login prompt
				$loginPrompt = true;

			} else {
				// The user is not logged in, but login is not required for this application.
				// Allow the user to perform the action.

				$result = $this->handleRequest();
				if ( Dataface_Error::isPermissionDenied($result) ){
					// The user did not have permission to perform the action
					// Give the user a login prompt.
					
					$loginPrompt = true;
				}
				
			}
			if ( $loginPrompt ){
				// The user is supposed to see a login prompt to log in.
				// Show the login prompt.
				
				$authTool->showLoginPrompt($loginError);
			} else if ($permissionDenied) {
				// The user is supposed to see the permissionm denied page.
				$query =& $this->getQuery();
				
				if ( $query['--original_action'] == 'browse' and $query['-action'] != 'view' ){
					header('Location: '.$this->url('-action=view'));
					exit;
				}
				$this->addError($result);
				header("HTTP/1.1 403 Permission Denied");
				df_display(array(), 'Dataface_Permission_Denied.html');
			} else if ( PEAR::isError($result) ){
				// Some other error occurred in handling the request.  Just show an
				// ugly stack trace.
				
				trigger_error($result->toString().$result->getDebugInfo(), E_USER_ERROR);
			}
		} else {
			// Authentication is not enabled for this application.
			// Just process the request.
			
			$result = $this->handleRequest();
			if ( Dataface_Error::isPermissionDenied($result) ){
				$query =& $this->getQuery();
				
				if ( $query['--original_action'] == 'browse' and $query['-action'] != 'view' ){
					header('Location: '.$this->url('-action=view'));
					exit;
				}
				$this->addError($result);
				header("HTTP/1.1 403 Permission Denied");
				df_display(array(), 'Dataface_Permission_Denied.html');
			} else if ( PEAR::isError($result) ){
				
				trigger_error($result->toString().$result->getDebugInfo(), E_USER_ERROR);
			}
		}
	
	}
	
	/**
	 * REturns the response array used for compiling response.
	 */
	function &getResponse(){
		static $response = 0;
		if ( !$response ){
			$response = array('--msg'=>'');
		}
		return $response;
	}
	
	/**
	 * PHP files located in the 'pages' directory of the site are considered to be 
	 * custom pages.  Passing the GET parameter -action=custom_<pagename> will cause
	 * the Application controller to display the page <pagename>.php from the pages
	 * directory.  This method just returns an array of full paths to the custom 
	 * pages that are available in the 'pages' directory.
	 */
	function &getCustomPages(){
		if ( !isset( $this->_customPages ) ){
			$this->_customPages = array();
			$path = DATAFACE_SITE_PATH.'/pages/';
			if ( is_dir($path) ){
				if ( $dh = opendir($path) ){
					while ( ( $file = readdir($dh) ) !== false ){
						if ( preg_match('/\.php$/', $file) ){
							list($name) = explode('.', $file);
							//$name = str_replace('_', ' ', $name);
							
							$this->_customPages[$name] = $path.$file;
						}
					}
				}
			}
		}
		return $this->_customPages;
	}
	
	/**
	 * Obtains the full path (read for inclusion) of the custom page with name $name
	 */
	function getCustomPagePath($name){
		$pages =& $this->getCustomPages();
		return $pages[$name];
	}
	
	/**
	 * Obtains the label for a custom page.  The label is the same as the name except
	 * with capitalization of words and replacement of underscores with spaces.
	 */
	function getCustomPageLabel($name){
		$name = str_replace('_',' ', $name);
		return ucwords($name);
	}
	
	/**
	 * Builds a link to somewhere in the application.  This will maintain the existing
	 * query information.
	 * @param mixed $query Either a query string or a query array.
	 * @param boolean $useContext Whether to use the existing context variables or not.
	 */
	function url($query, $useContext=true, $forceContext=false){
		import('Dataface/LinkTool.php');
		return Dataface_LinkTool::buildLInk($query, $useContext, $forceContext);
	
	}
	

	
	function addError($err){
		$this->errors[] = $err;
	}
	
	function numErrors(){ return count($this->errors); }
	function getErrors(){
		return $this->errors;
	}
	function addMessage($msg){
		$this->messages[] = $msg;
	}
	
	function getMessages(){
		if ( trim(@$_SESSION['msg']) ){
			array_push($this->messages, $_SESSION['msg']);
			unset($_SESSION['msg']);
		}
		$msgs = $this->messages;
		$response =& $this->getResponse();
		if ( @$response['--msg'] ){
			array_push($msgs, $response['--msg']);
		}
		//print_r($msgs);
		return $msgs;
	}
	
	function clearMessages(){
		$this->messages = array();
	}
	function numMessages(){
		$count = count($this->messages);
		$response =& $this->getResponse();
		if ( @$response['--msg'] ) $count++;
		return $count;
	}
	
	function addDebugInfo($info){
		$this->debugLog[] = $info;
	}
	
	// Displays Debug Info as HTML
	function displayDebugInfo(){
		echo '<ul class="debug-info"><li>
		'; echo implode('</li><li>', $this->debugLog);
		echo '</li></ul>';
	}
	
	function _cleanup(){
		if ( session_id() != "" ){
			$this->writeSessionData();
		}
		if ( @$this->_conf['support_transactions'] ){
			@mysql_query('COMMIT', $this->_db);
		}
	}
	
	/**
	 * Fires an event to all event listeners.
	 * @param string $name The name of the event. e.g. afterInsert
	 * @param array $params Array of parameters to pass to the event listener.
	 * @returns mixed Result of event.  May be PEAR_Error if the event throws an error.
	 */
	function fireEvent($name, $params=null){
		$listeners = $this->getEventListeners($name);
		foreach ($listeners as $listener){
			$res = call_user_func($listener, $params);
			if ( PEAR::isError($res) ) return $res;
		}
		return true;
	}
	
	/**
	 * Registers an event listener to respond to events of a certain type.
	 * @param string $name The name of the event to register for. e.g. afterInsert
	 * @param mixed $callback A standard PHP callback.  Either a function name or an array of the form array(&$object,'method-name').
	 * @returns void.
	 */
	function registerEventListener($name, $callback){
		if ( !isset($this->eventListeners[$name]) ) $this->eventListeners[$name] = array();
		$this->eventListeners[$name][] = $callback;
	}
	
	function unregisterEventListener($name, $callback){
		if ( isset($this->eventListeners[$name]) ){
			$listeners =& $this->eventListeners[$name];
			foreach ( $listeners as $key=>$listener ){
				if ( $listener == $callback ) unset($listeners[$key]);
			}
		}
	}
	
	/**
	 * Gets a list of the callbacks that are registered for a given event.
	 * @param $name The name of the event for which the callbacks are registered.
	 * @returns array Either an array of callbacks for the event.  Or associative array of array of callbacks for all events with the key on the event name.
	 */
	function getEventListeners($name=null){
		if ( !isset($name) ) return $this->eventListeners;
		else if (isset($this->eventListeners[$name])){
			return $this->eventListeners[$name];
		} else {
			return array();
		}
	}
	

	
	
	
	
	

}
