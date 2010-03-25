<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
/*
 * 	Handles the display of an SQL Table.
 */
 
import( 'Dataface/Table.php');
import( 'Dataface/Application.php');

import( 'Dataface/ResultController.php');
import( 'Dataface/QueryTool.php');
//require_once 'Smarty/smarty.class.php';
import( 'Dataface/SkinTool.php');
import( 'Dataface/Globals.php');


import( 'Dataface/LinkTool.php');

/**
 * The Dataface_TableView class is a mis-named controller that manages the flow of 
 * control in a dataface application.  Even though Dataface_Application is the top-level
 * object, all pages are in the context of a table - for which TableView provides the
 * view.
 *
 * This class contains methods named after each action that can be handled.
 * For example, requests for -action=browse are handled by the _browse() function.
 */
class Dataface_TableView {

	/**
	 * Reference to the current table.
	 */
	var $_table;
	/**
	 * The name of the current table.
	 */
	var $_tablename;
	/**
	 * The name of the current action.  e.g., browse, list, etc..
	 */
	var $_currentAction;
	
	/**
	 * The query parameters for this action stored in an array.
	 * Typical query array would look like:
	 * array(
	 *  '-action'=>'browse',
	 *	'-start'=>30,
	 *	'-limit'=>10,
	 *	'LastName'=>'Hannah' // A find parameter
	 * );
	 */
	var $_query;
	
	/**
	 * Stores a reference to the Dataface_SkinTool object.
	 */
	var $_skinTool;
	
	/**
	 * Database handle.
	 */
	var $_db;
	
	/**
	 * The base url to the dataface application.
	 */
	var $_baseUrl;
	
	/**
	 * Useful array to pass parameters from one stage of the response to another.
	 * For example, an init() method (before any output is written to the screen
	 * errors may be encountered.  These errors can be passed to the print methods
	 * by setting the $this->_vars['error'] variable.
	 */
	var $_vars;
	
	/**
	 * Reference to the QueryTool object for the current set of results.
	 */
	var $_resultSet;
	
	/**
	 * Blocks for displaying special content on the top, bottom, left, or right
	 * of the screen.
	 */
	var $_blocks=array('top'=>'', 'bottom'=>'', 'left'=>'','right'=>'');
	
	/**
	 * Parsed parameters from the query.
	 */
	var $_params=array();
	
	/**
	 * An array of the history of the most recent pages visited in the application.
	 */
	var $_history=array();
	
	/**
	 * The address of the previous page in the application.  Can be used to simulate the
	 * browser back button.  The only reason this was introduced was because FireFox for
	 * mac's back button doesn't work when FCKEditor is displayed on a page.
	 */
	var $back;
	
	/**
	 * Constructor.
	 *
	 * @param $tablename The name of the table that is to be displayed.
	 * @param $db Database handle.
	 * @param $currentAction The name of the current action being performed.  e.g., 'browse', 'list'
	 * @param $query Array of query parameters.
	 * @param $params Extra parameters.
	 */
	function Dataface_TableView( $tablename, $db ='', $currentAction='', $query=array(), $params = array() ){
		$this->_tablename = $tablename;
		$this->_table =& Dataface_Table::loadTable($this->_tablename, $db);
		$this->_currentAction = $currentAction;
		
		if ( !isset($query['-limit']) ){
			$query['-limit'] = 30;
		}
		$this->_params = $params;
		$this->_query = $query;
		$this->_db = $db;
		$this->_skinTool =& Dataface_SkinTool::getInstance();
		$this->_baseUrl = DATAFACE_SITE_URL.'/index.php';
		$this->_vars = array();
		$this->_resultSet =& Dataface_QueryTool::loadResult($this->_tablename, $this->_db, $this->_query);
		if ( !$this->_currentAction ){
			if ( isset( $query['-action'] ) ){
				$this->_currentAction = $query['-action'];
			} else if ( $this->_resultSet->found() > 1 ) {
				$this->_currentAction = 'list';
			} else {
				$this->_currentAction = 'browse';
			}
		}
		
		// perform initialization for current action
		if ( method_exists($this, "_".$this->_currentAction."_init") ){
			call_user_func( array( &$this, "_".$this->_currentAction."_init") );
		}
		
		
		if ( session_id()){
			if ( isset( $_SESSION['Dataface_Application_back'] ) ){
				$this->back = unserialize($_SESSION['Dataface_Application_back']) ;
			} else {
				$this->back = '';
			}
			$current = array(
						'table'=>$this->_table->tablename,
						
						'link'=>$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']
						
					);
			$_SESSION['Dataface_Application_back'] = serialize($current);
			
		
			if ( isset( $_SESSION['Dataface_Application_history'] ) ){
				
				$this->_history = unserialize($_SESSION['Dataface_Application_history']);
				
			} else {
				$this->_history = array();
			
			}
			
			
			
			
			
			if ( $this->_currentAction == 'browse' and !isset( $this->_params['new'])){
				$record =& $this->_resultSet->loadCurrent();
				if ( $record ){
					$current = array(
						'table'=>$this->_table->tablename,
						'recordTitle'=>$record->getTitle(),
						'link'=>$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']
						
					);
					array_push($this->_history, $current);
				}
			}
		
			if ( count($this->_history) > 10 ){
				array_shift($this->_history);
			}
			$titles = array();
			foreach (array_keys($this->_history) as $key){
				if ( isset( $titles[$this->_history[$key]['recordTitle']] ) ){
					unset( $this->_history[$key] );
				} else {
					$titles[$this->_history[$key]['recordTitle']] = true;
				}
			}
			//$this->_history = array_unique($this->_history);
			
			$_SESSION['Dataface_Application_history'] = serialize($this->_history);
		}
			
			
		
	}
	
	
	/**
	 * Creates action tab html.
	 */
	
	
	/**
	 * Generates the tabs for the relationships of the current record.
	 */
	function _relationships_menu(){
		$s =& $this->_table;
		$relationships =& $s->relationships();
		$relationship_names = array_keys($relationships);
		
		//$actions = array("browse"=>"Browse","list"=>"List","find"=>"Find");
		$actions = array("main"=>"Main");
		$access_keys = array("main"=>"m");
		foreach ($relationship_names as $relationship_name){
			$actions[$relationship_name] = $relationship_name;
			$access_keys[$relationship_name] = '';
		}
		
		//$access_keys = array("browse"=>"b", "list"=>"t", "find"=>"f");
		$tabs = array();
		$links = array();
		
		foreach ($actions as $key=>$value){
			$tab = array();
			$tab['name'] = $key;
			$tab['label'] = $value;
			$tab['accessKey'] = $access_keys[$key];
		
			if ( (isset( $this->_params['relationship']) and $key == $this->_params['relationship']) or 
				(!isset( $this->_params['relationship']) and $key == "main") ){
				$tab['link'] = "#";
			} else if ( $key == "main" ){
				$query = array_merge( $this->_query, array("-action"=>"browse", "-relationship"=>null, "--msg"=>null) );
				$tab['link'] = Dataface_ResultController::_buildLink($query);
			} else {
				$query = array_merge( $this->_query, array( "-action"=>"browse", "-relationship"=>$key, "--msg"=>null) );
				$tab['link'] = Dataface_ResultController::_buildLink($query);
			}
			$tabs[] = $tab;
		}
		
		$context = array();
		$context['tabs'] = $tabs;
		
		$context['selectedTab'] = isset( $this->_params['relationship'] ) ? $this->_params['relationship'] : "main";
		//$this->_smarty->assign($context);
		ob_start();
		//$this->_smarty->display('Dataface_TableView_tabs.html');
		$this->_skinTool->display($context, 'Dataface_TableView_tabs.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	/**
	 * Generates the action menu (e.g., Show all, New, Delete, Delete Found)
	 */
	
	
	
	/**
	 * Generates menus for relationships.  e.g., Add related record, etc...
	 */
	function _relationshipMenus(){
		$menus = array();
		$query = array_merge( $this->_query, array('-action'=>'browse', '-new'=>1) );
		$menus[] = array(
			'label'=>'Add New Related Record',
			'description'=>'Create a new record',
			'url'=>Dataface_ResultController::_buildLink($query),
			'icon'=>DATAFACE_URL."/images/document_icon.gif"
			);
		
		$menus[] = array(
			'label'=>'Add Existing Related Record',
			'description'=>'Show All Records in table',
			'url'=>Dataface_LinkTool::buildLink(array('-action'=>'list', '-table'=>$this->_tablename), false),
			'icon'=>DATAFACE_URL."/images/document_icon.gif"
		);
		
		
	
	
		$context = array();
		$context['menus'] =& $menus;
		//$this->_smarty->assign($context);
		ob_start();
		//$this->_smarty->display('Dataface_TableView_menus.html');
		$this->_skinTool->display($context, 'Dataface_TableView_menus.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	
	
	}
	
	
	
	
	
	/**
	 * Creates the controller.  The controller consists of the forward and back buttons and
	 * the jump menu.
	 *
	 */
	function _controller(){
	
		$controller =& new Dataface_ResultController($this->_tablename, $this->_db, $this->_baseUrl, $this->_query);
		return $controller->toHtml();
		
	}
	
	/**
	 *
	 * Initialization for the browse action.  This method is called before any html is output
	 * to the browser.  It handles form creation and validation.
	 *
	 */
	function _browse_init(){
		import( 'Dataface/QuickForm.php');
		
		
		/*
		 *
		 * If we are not creating a new record, then we'll record this as the last
		 * valid page visited.  This will be useful for forwarding to the last page
		 * visited when the form is validated.
		 *
		 */
		if ( !isset( $this->_params['new']) ){
			setcookie('dataface_lastpage', $_SERVER['QUERY_STRING']);
		}
		
		/*
		 *
		 * Default functionality ('-relationship' flag is not set) is to show or validate
		 * the quickform.  If the -new flag is specified, it overrides the -relationship flag.
		 *
		 */
		if ( !isset( $this->_params['relationship'] ) or isset( $this->_params['new']) ){
			
			$new = (isset($this->_params['new']) and $this->_params['new']);
			
			/*
			 *
			 * Create the quickform for the current record.
			 *
			 */
			$form =& new Dataface_QuickForm($this->_tablename, $this->_db,  $this->_query, '',$new);
			
			if ( $this->_resultSet->found()>0 or $new){
				/*
				 * There is either a result to edit, or we are creating a new record.
				 *
				 */
				 
				$res = $form->_build();
				if ( PEAR::isError($res) ){
					trigger_error($res->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
				
				}
				
				/*
				 *
				 * We need to add the current GET parameter flags (the GET vars starting with '-') so
				 * that the controller knows to pass control to this method again upon form submission.
				 *
				 */
				foreach ( $this->_query as $key=>$value){
					if ( strpos($key,'-')===0 ){
						$form->addElement('hidden', $key);
						$form->setDefaults( array( $key=>$value) );
						
					}
				}
				
				/*
				 * Store the current query string (the portion after the '?') in the form, so we 
				 * can retrieve it after and redirect back to our original location.
				 */
				$form->addElement('hidden', '-query');
				$form->setDefaults( array( '-action'=>$this->_currentAction,'-query'=>$_SERVER['QUERY_STRING']) );
				
				
				/*
				 * 
				 * We have to deal with 3 cases.
				 * 	1) The form has not been submitted.
				 *	2) The form was submitted but didn't validate (ie: it had some bad input)
				 * 	3) The form was submitted and was validated.
				 *
				 * We deal with Case 3 first...
				 *
				 */
			
				if ( $form->validate() ){
					/*
					 *
					 * The form was submitted and it validated ok.  We now process it (ie: save its contents).
					 *
					 */
					$result = $form->process( array( &$form, 'save') );
					$success = true;
					$response =& Dataface_Application::getResponse();
					
					if ( !$result ){
						trigger_error("Error occurred in save: ".mysql_error( $this->_db).Dataface_Error::printStackTrace(), E_USER_ERROR);
						exit;
					} else if ( PEAR::isError($result) && !Dataface_Error::isNotice($result) ){
						//echo "Error..";
						if ( Dataface_Error::isDuplicateEntry($result) ){
							//echo "dup entry"; exit;
							$query = array('-action'=>'error');
							$response =& Dataface_Application::getResponse();
							$msg = @$response['--msg'];
							$msg = urlencode(trim("Failed to save record because another record with the same keys already exists.\n".$msg));
							$link = Dataface_LinkTool::buildLink($query, false).'&--msg='.$msg;
						    header('Location: '.$link);
						    exit;
						} else {
							//echo "not dup entry"; exit;
							trigger_error($result->toString(). Dataface_Error::printStackTrace(), E_USER_ERROR);
						 	exit;
						}
					} else if ( Dataface_Error::isNotice($result) ){
					
						$response['--msg'] = @$response['--msg'] ."\n".$result->getMessage();
						$success = false;
					}
					
					
					
					
					if ( $new ){
						/*
						 *
						 * If the form created a new record, then it makes more sense to redirect to this newly
						 * created record than to the old record.  We used the 'keys' of the new record to generate
						 * a redirect link.
						 *
						 */
						$query = $form->_record->getValues(array_keys($form->_record->_table->keys()));
						
						$msg = @$response['--msg'];
						$msg = urlencode(trim(($success ? "Record successfully saved.\n" : '').$msg));
						$link = Dataface_LinkTool::buildLink($query, false).'&--msg='.$msg; 
						
					} else {
						/*
						 *
						 * The original query string will have the -new flag set.  We need to remove this 
						 * flag so that we don't redirect the user to create another new record.
						 *
						 */
						$vals = $form->exportValues();
						$vals['-query'] = eregi_replace('[&\?]-new=[^&]+', '', $vals['-query']);
						$msg =@$response['--msg'];
						$msg = urlencode(trim(($success ? "Record successfully saved.\n" : '').$msg));
						$link = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?'.$vals['-query'].'&--msg='.$msg;
					}
					
					/*
					 *
					 * Redirect the user to the appropriate record.
					 *
					 */
					header("Location: $link");
					exit;
				} 
					 
			}
			$this->_vars['form'] =& $form;
		}
		
	}
		
	
	/**
	 * Displays the main body in browse mode (now known as details mode).
	 */
	function _browse(){
		if ( !isset( $this->_params['relationship'] ) or isset( $this->_params['new']) ){
			$form =& $this->_vars['form'];
			
			ob_start();
			$form->display();
			$out = ob_get_contents();
			ob_end_clean();
			
			if ( count($form->_errors) > 0 ){
				$out = "<div class=\"error\">Some errors occurred during form validation.  Please check your input and try to submit again.</div>".$out;
			}
		} else {
			$out = $this->_relatedlist();
		}
		$record =& $this->_resultSet->loadCurrent();
		if ( $record ) {
			$title = '<h1>'.$record->getTitle().'</h1>';
		} else {
			$title = '';
		}
		if ( !isset( $this->_params['new']) ){
			$out = $title.$this->_relationships_menu().'
					<div class="documentContent" id="region-content" style="border: 1px solid gray">
					'.
					$out.
					'</div>';
		}
		return $out;
		
	}
	
	/**
	 * Displays an error.
	 */
	function _error(){
		$st =& Dataface_SkinTool::getInstance();
		$context = array();
		ob_start();
		
		$st->display($context, 'Dataface_TableView_Error.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
		
		
		
	}
	
	/**
	 * Displays a list of related records.
	 */
	function _relatedlist(){
		import( 'Dataface/RelatedList.php');
		$record =& $this->_resultSet->loadCurrent();
		if ( !isset( $this->_params['relationship']) ){
			return "<div class=\"error\">Error.  No relationship was specified.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
		}
		$list =& new Dataface_RelatedList($record, $this->_params['relationship']);
		$res = $list->toHtml();
		if ( PEAR::isError($res) ){
			return "<div class=\"error\">".htmlspecialchars($res->toString())."</div>\n";
		}
		
		$relationship_menu = $this->_relationships_menu();
		
		return $res;
	}
	
	/**
	 * Handles initialization and control for the new related record form.
	 */
	function _new_related_record_init(){
		import( 'Dataface/ShortRelatedRecordForm.php');
		if ( !isset( $this->_params['relationship']) ){
			$this->_vars['error'] =  "<div class=\"error\">Error.  No relationship was specified.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
			return;
		}
		

		$record = null;	// we let the Form automatically handle loading of record.
		$form =& new Dataface_ShortRelatedRecordForm($record, $this->_params['relationship']);
		$form->_build();
		$this->_vars['form'] =& $form;
		if ( !Dataface_PermissionsTool::edit($form->_record)){
			$this->_vars['error'] =  "<div class=\"error\">Error.  Permission Denied.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
			return;
		}
	
		
		if ( $form->validate() ){
			$vals = $form->exportValues();
			
			$res = $form->process(array(&$form, 'save'), true);
			$response =& Dataface_Application::getResponse();
			
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				$this->_vars['error'] = "<div class=\"error\">Error.  ".$res->toString()."<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
				return;
			} else if ( Dataface_Error::isNotice($res) ){
				$success = false;
				$response['--msg'] = @$response['--msg'] . "\n".$res->getMessage();
			} else {
				$success = true;
			}
			$query = array('-action'=>'browse');
			$msg = urlencode(trim(($success ? "Record successfully added to ".$this->_params['relationship']." relationship.\n"  : '').@$response['--msg']));

			
			foreach ($vals['__keys__'] as $key=>$value){
				$query[$key] = "=".$value;
			}
			$link = Dataface_LinkTool::buildLink($query);
			header("Location: $link"."&--msg=".$msg);
		 	exit;
		}
		
	
	}
	
	/**
	 * Displays new related record form.
	 */
	function _new_related_record(){
		if ( isset($this->_vars['error']) and strlen($this->_vars['error']) > 0 ){
			return $this->_vars['error'];
		}
		$form =& $this->_vars['form'];
		ob_start();
		echo "<h2>Add Related Record</h2>";
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	}
	
	/**
	 * Handles initialization and control for the existing related record form.
	 */
	function _existing_related_record_init(){
		import( 'Dataface/ExistingRelatedRecordForm.php');
		if ( !isset( $this->_params['relationship'] ) ){
			$this->_vars['error'] = "<div class=\"error\">Error. no relationship was specified.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
			return;
		}
		$record = null;	// let the form handle getting the current record.
		
		
		$form =& new Dataface_ExistingRelatedRecordForm($record, $this->_params['relationship']);
		$form->_build();
		$this->_vars['form'] =& $form;
		
		if ( !Dataface_PermissionsTool::edit($form->_record) ) {
			$this->_vars['error'] =  "<div class=\"error\">Error.  Permission Denied.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
			return;
		}
		
		if ( $form->validate() ){
			$res = $form->process(array(&$form, 'save'), true);
			$response =& Dataface_Application::getResponse();
			
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				$this->_vars['error'] = "<div class=\"error\">Error.  ".$res->toString()."<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
				return;
			} else if ( Dataface_Error::isNotice($res) ){
				$response['--msg'] = @$response['--msg'] . "\n".$res->getMessage();
				$success = false;
			} else {
				$success = true;
			}
			
			$query = array('-action'=>'browse');
			$msg = urlencode(trim(($success ? "The record has been successfully added to the ".$this->_params['relationship']." relationship.\n" :'').@$response['--msg']));
			
			$vals = $form->exportValues();
			foreach ($vals['__keys__'] as $key=>$value){
				$query[$key] = "=".$value;
			}
			$link = Dataface_LinkTool::buildLink($query);
			header("Location: $link"."&--msg=".$msg);
			exit;
		}
		
		
	
	
	}
	
	/**
	 * Displays existing related record form.
	 */
	function _existing_related_record(){
		if ( isset($this->_vars['error']) and strlen($this->_vars['error']) > 0 ){
			return $this->_vars['error'];
		}
		$form =& $this->_vars['form'];
		ob_start();
		echo "<h2>Add Existing Related Record</h2>";
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	}
	
	/**
	 * Handles initialization for the remove related record form.
	 */
	function _remove_related_record_init(){
		import( 'Dataface/RemoveRelatedRecordForm.php');
		$record = null; //& new Dataface_Record($this->_tablename, $_REQUEST['--__keys__']);
			// let the form handle the loading of the record
		
		
		//print_r($_REQUEST);
		//exit;
		$form =& new Dataface_RemoveRelatedRecordForm($record, $this->_params['relationship'], $_REQUEST['--remkeys']);
		if ( !Dataface_PermissionsTool::edit($form->_record) ) {
			$this->_vars['error'] =  "<div class=\"error\">Error.  Permission Denied.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
			return;
		}
		if ( $form->validate() ){
		
			$res = $form->process(array(&$form, 'delete'), true);
			$response =& Dataface_Application::getResponse();
			
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				$this->_vars['error'] = "<div class=\"error\">Error.  ".$res->toString()."<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
				return;
			} else if ( Dataface_Error::isNotice($res) ){
				$response['--msg'] = @$response['--msg'] ."\n".$res->getMessage();
			
			}
			
			
			
			if ( isset( $res['warnings'] ) and count($res['warnings']) > 0 ){
				$msg = implode('\n', array_merge($res['warnings'],$res['confirmations']));
			} 
			else {
				$msg = "Records successfully removed from relationship";
			}
			$msg = urlencode(trim($msg."\n".@$response['--msg']));
			
			header("Location: ".$_SERVER['HOST_URI'].$_SERVER['PHP_SELF'].'?'.$_COOKIE['dataface_lastpage'].'&--msg='.$msg);
			exit;
		
		}
		$form->_build();
		$this->_vars['form'] =& $form;
	
	}
	
	/**
	 * Displays the remove related record form.
	 */
	function _remove_related_record(){
		if ( isset($this->_vars['error']) and strlen($this->_vars['error']) > 0 ){
			return $this->_vars['error'];
		}
		$form =& $this->_vars['form'];
		ob_start();
		echo "<h2>Remove Related Record</h2>";
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	}
	
	/**
	 * Handles initialization and control for the delete form.
	 */
	function _delete_init(){
		import( 'Dataface/DeleteForm.php');
		$record =& new Dataface_Record($this->_tablename, @$_REQUEST['--__keys__']);
		if ( !Dataface_PermissionsTool::delete($record) ) {
			$this->_vars['error'] =  "<div class=\"error\">Error.  Permission Denied.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
			return;
		}
		
		$form =& new Dataface_DeleteForm($this->_tablename, $this->_db, $this->_query);
		
		$form->_build();
		$form->addElement('hidden','-table');
		$form->setDefaults(array('-table'=>$this->_tablename));
		$this->_vars['form'] =& $form;
		
		if ( $form->validate() ){
			$res = $form->process( array(&$form, 'delete'), true);
			$response = Dataface_Application::getResponse();
			
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				
				$msg = $res->getMessage();
				$msg .= "\n". $res->getUserInfo();
			} else if ( Dataface_Error::isNotice($res) ){
				$response['--msg'] = @$response['--msg'] ."\n".$res->getMessage();
			
			} else  {
				$msg = 'Records successfully deleted.';
			}
			$msg = urlencode(trim($msg."\n".$response['--msg']));
			
			header('Location: '.$_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?-table='.$this->_tablename.'&--msg='.$msg);
			exit;
			
		}
	}
	
	/**
	 * Displays the delete form.
	 */
	function _delete(){
		if ( isset($this->_vars['error']) and strlen($this->_vars['error']) > 0 ){
			return $this->_vars['error'];
		}
		$form =& $this->_vars['form'];
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
		
	}
	
	/**
	 * Handles initialization and control for the import records form.
	 */
	function _import_init(){
		import( 'Dataface/ImportForm.php');
		
		$form =& new Dataface_ImportForm($this->_tablename);
		$record =& $form->_record;
		if ( !Dataface_PermissionsTool::edit($record) ){
			$this->_vars['error'] =  "<div class=\"error\">Error.  Permission Denied.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
			return;
		}
		$form->_build();
		$this->_vars['form'] =& $form;
		
		
		
		if ( $form->validate() ){
			//echo "validated";
			$querystr = $form->exportValue('-query');
			
			if ( intval($form->_step) === 1 ){
				
				if ( preg_match('/-step=1/',$querystr) ){
					$querystr = preg_replace('/-step=1/', '-step=2', $querystr);
				} else {
					$querystr .= '&-step=2';
				}
				$importTablename = $form->process(array(&$form, 'import'));
				//echo "Table: $importTablename";
				//exit;
				//$link = 'Location: '.$_SERVER['PHP_SELF'].'?'.$querystr.'&--importTablename='.$importTablename;
				//echo $link;
				//exit;
				header('Location: '.$_SERVER['PHP_SELF'].'?'.$querystr.'&--importTablename='.$importTablename);
				exit;
			} else {
				$records = $form->process(array(&$form, 'import'));
				$keys  = $form->exportValue('__keys__');
				$keys['-action'] = 'browse';
				$keys['-step'] = null;
				$keys['-query'] = null;
			
				$link = Dataface_LinkTool::buildLink($keys);
				
				$response =& Dataface_Application::getResponse();
				$msg = urlencode(trim("Records imported successfully.\n".@$response['--msg']));
				
				header('Location: '.$link.'&--msg='.$msg);
				exit;
				
			
			} 
				
				
		
		
		}
		//echo "Not validated";
	
	
	}
	
	/**
	 * Displays the import records form.
	 */
	function _import(){
		if ( isset($this->_vars['error']) and strlen($this->_vars['error']) > 0 ){
			return $this->_vars['error'];
		}
		ob_start();
		$this->_vars['form']->display();
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	/**
	 * Displays list of records for list mode.
	 */
	function _list(){
		import( 'Dataface/ResultList.php');
		$list =& new Dataface_ResultList( $this->_tablename, $this->_db, /*$columns=*/array(), $this->_query);
		return $list->toHtml();
	
	}
	
	
	/**
	 * Displays find form.
	 */
	function _find(){
		import( 'Dataface/SearchForm.php');
		$form =& new Dataface_SearchForm($this->_tablename, $this->_db, $this->_query);
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	}
	
	/**
	 * Displays custom action.
	 */
	function _custom($page){
		
		$app =& Dataface_Application::getInstance();
		$pages =& $app->getCustomPages();
		if (!isset( $pages[$page] ) ){
			trigger_error( "Request for custom page '$page' failed because page does not exist in pages directory.". Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		ob_start();
		include $pages[$page];
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
		
		
	}
	
	/**
	 * Unknown.. does nothing.
	 */
	function _compass(){
		return '';
	}
	
	/**
	 * Displays the body for the current action.
	 */
	function _body(){
		if ( strstr($this->_currentAction, 'custom_') == $this->_currentAction ){
			if (method_exists( $this, "_custom") ){
				return $this->_custom(substr($this->_currentAction, 7, strlen($this->_currentAction)-7));
			} else {
				trigger_error("No Custom method exists to handle request for action '".$this->_currentAction."'".Dataface_Error::printStackTrace(), E_USER_ERROR);
			}
		}
		
		if ( method_exists( $this, "_".$this->_currentAction) ){
			return call_user_func(array( &$this, "_".$this->_currentAction) );
		} else {
			return '';
		}
	}
	
	
	/**
	 * Display a custom page.
	 */
	function _custom_page(){
	
	
	
	
	}
	
	/**
	 * Displays the logo.
	 */
	function logo(){
		ob_start();
		$context = array();
		$this->_skinTool->display($context, 'Dataface_Logo.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	/**
	 * Displays the fine print.
	 */
	function finePrint(){
		ob_start();
		$context = array();
		$this->_skinTool->display($context, 'Dataface_Fineprint.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	}
	
	/**
	 * Prints the table html.
	 */
	function toHtml(){
		$record =& $this->_resultSet->loadCurrent();
		$context = array();
		$context['controller'] = $this->_controller();
		$context['body'] = $this->_body();
		$context['compass'] = $this->_compass();
		$context['result'] =& $this->_resultSet->_data;
		$context['blocks'] =& $this->_blocks;
		$context['action'] = $this->_currentAction;
		$context['table'] = $this->_table->tablename;
		$context['history'] = $this->_history;
		$context['back'] = $this->back;
		if ( $record && $this->_query['-action'] == 'browse'){
			$context['title'] = $record->getTitle();
		} else {
			$context['title'] = $this->_table->tablename;
		}
		$context['logo'] = $this->logo();
		$context['fineprint'] = $this->finePrint();
		
		
		$context['message'] = '';
		if ( isset( $_GET['--msg'] ) ){
			$context['message'] = $_GET['--msg'];
		}
		
		$context['applicationMenu'] = $this->applicationMenu();
		
		
		
		
		//$this->_smarty->assign($context);
		ob_start();
		//$this->_smarty->display('Dataface_TableView.html');
		$this->_skinTool->display($context, 'Dataface_TableView.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	}
	
	/**
	 * Sets the html content that should be displayed in the head of the page.
	 */
	function setHeaderContent($content){
		$this->_blocks['top'] = $content;
	}
	
	/**
	 * Sets the html content that should be displayed as the footer.
	 */
	function setFooterContent($content){
		$this->_blocks['bottom'] = $content;
	}
	
	/**
	 * Sets the html content that should be displayed in the left column.
	 */
	function setLeftContent($content){
		$this->_blocks['left'] = $content;
	}
	
	/**
	 * Sets the html content that should be displayed in the right column.
	 */
	function setRightContent($content){
		$this->_blocks['right'] = $content;
	}
	
	/**
	 * Returns HTML for an application menu.  This menu contains actions specific to the application (ie: not generic
	 * to all dataface apps.
	 */
	function applicationMenu(){
	
		$context = array();
		ob_start();
		$this->_skinTool->display($context, 'Dataface_Application_Menu.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	}
	
	

 	
}
