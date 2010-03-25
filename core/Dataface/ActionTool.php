<?php

import('Dataface/LanguageTool.php');
 
/**
 * A tool to manage actions within the application.
 */
class Dataface_ActionTool {

	//var $_actionsConfig;
	var $actions=array();
	
	function Dataface_ActionTool($conf=null){
		if ( $conf === null ){
			$this->_loadActionsINIFile(/*DATAFACE_PATH."/actions.ini"*/);
			//$this->_loadActionsINIFile(DATAFACE_SITE_PATH."/actions.ini");
		} else {
			$this->actions =& $conf;
		}
	
	}
	
	
	
	function _loadActionsINIFile(/*$path*/){
		
		import('Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$actions =& $configTool->loadConfig('actions', null);
		foreach ( array_keys($actions) as $key){
			$action =& $actions[$key];
			$action['name'] = $key;
			if ( !isset($action['id']) ) $action['id'] = $action['name'];
			if ( !isset($action['label']) ) $action['label'] = str_replace('_',' ',ucfirst($action['name']));
			if ( !isset($action['accessKey'])) $action['accessKey'] = substr($action['name'],0,1);
			//if ( !isset($action['label_i18n']) ) $action['label_i18n'] = 'action:'.$action['name'].' label';
			//if ( !isset($action['description_i18n'])) $action['description_i18n'] = 'action:'.$action['name'].' description';
			
			if ( isset($action['description']) ){
				$action['description'] = df_translate('actions.'.$action['name'].'.description', $action['description']);
			}
			if ( isset($action['label']) ){
				$action['label'] = df_translate('actions.'.$action['name'].'.label',$action['label']);
			}
			
			$this->actions[$key] =& $action;
			unset($action);
		}
		unset($temp);
		$this->actions =& $actions;
		
	}
	
	function _loadTableActions($tablename){
		import('Dataface/Table.php');
		// Some actions are loaded from the table's actions.ini file and must be loaded before we return the actions.

		$table =& Dataface_Table::loadTable($tablename);
		if ( !$table->_actionsLoaded ){
			$params = array();
			$table->getActions($params);
		}
	}
	
	/**
	 * Returns a specified action without evaluating the permissions or condition fields.
	 * @param $params Associative array:
	 *			Options:  name => The name of the action to retrieve
	 *					  table => The name of the table on which the action is defined.
	 *  @returns Action associative array.
	 */
	function &getAction($params, $action=null){
		$app =& Dataface_Application::getInstance();
		if ( !isset($action) ){
			if ( isset($params['table']) and $params['table'] ) $this->_loadTableActions($params['table']);
			if ( !isset($params['name']) or !$params['name'] ) trigger_error("ActionTool::getAction() requires 'name' parameter to be specified.", E_USER_ERROR);
			if ( !isset( $this->actions[$params['name']] ) ) {
				$err =  PEAR::raiseError(
					Dataface_LanguageTool::translate(
						"No action found", /* i18n id */
						"No action found named '".$params['name']."'", /*default error message*/
						array('name'=>$params['name']) 	/* i18n parameters */
					)
				);
				return $err;
			}
			
			$action = $this->actions[$params['name']];
		}
		
			
		if ( isset($action['selected_condition']) ) {
			$action['selected'] = $app->testCondition($action['selected_condition'], $params);
		}
		
		
		//if ( isset($action['visible']) and !$action['visible']) continue;
			// Filter based on a condition
		foreach (array_keys($action) as $attribute){
			// Some entries may have variables that need to be evaluated.  We use Dataface_Application::eval()
			// to evaluate these entries. The eval method will replace variables such as $site_url, $site_href
			// $dataface_url with the appropriate real values.  Also if $params['record'] contains a 
			// Record object or a related record object its values are treated as php variables that can be 
			// replaced.  For example if a Profile record has fields 'ProfileID' and 'ProfileName' with
			// ProfileID=10 and ProfileName = 'John Smith', then:
			// $app->parseString('ID is ${ProfileID} and Name is ${ProfileName}') === 'ID is 10 and Name is John Smith'
			if ( eregi('condition',$attribute) ) continue;
			//if ( isset($action[$attribute.'_condition']) and !$app->testCondition($action[$attribute.'_condition'], $params) ){
			//	$action[$attribute] = null;
			//} else {
				$action[$attribute] = $app->parseString($action[$attribute], $params);
			//}
		}
		return $action;
		
	}
	
	/**
	 * Returns an array of all actions as specified by $params.
	 * $params must be an array.  It may contain the following options:
	 *		record => A reference to a record for which the actions apply (This may be a related record)
	 *		table => The name of a table on which the actions apply.
	 *		relationship => The name of a relationship on which the action is applied. (requires that table also be set - or may use dotted name)
	 *						to include the table name and the relationship name in one string.
	 *		category => The name of the category of actions to be retrieved.
	 */
	function getActions($params=array(), $actions=null){
		if ( !is_array($params) ){
			trigger_error("In Dataface_ActionTool::getActions(), expected parameter to be an array but received a scalar: ".$params.".".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$app =& Dataface_Application::getInstance();
		
		$out = array();
		
		$tablename = null;
		if ( isset($params['table']) ) $tablename = $params['table'];
		if ( isset($params['record']) and is_a($params['record'], 'Dataface_Record') ) $tablename = $params['record']->_table->tablename;
		else if ( isset($params['record']) and is_a($params['record'], 'Dataface_RelatedRecord')) $tablename = $params['record']->_record->_table->tablename;
		
		if ( isset( $params['record'] ) && is_a($params['record'], 'Dataface_Record') ){
				// we have received a record as a parameter... we can infer the table information
			$params['table'] = $params['record']->_table->tablename;
		}  else if ( isset($params['record']) && is_a($params['record'], 'Dataface_RelatedRecord') ){
			// we have recieved a related record object... we can infer both the table and relationship information.
			$temp =& $params['record']->getParent();
			$params['table'] = $temp->_table->tablename;
			unset($temp);
			
			$params['relationship'] = $params['record']->_relationshipName;
		}
		
		if ( @$params['relationship']){
			if ( strpos($params['relationship'], '.') !== false ){
				// if the relationship is specified in the form 'Tablename.RElationshipname' parse it.
				list($params['table'],$params['relationship']) = explode('.', $params['relationship']);
			}
		}
		
		if ( $tablename !== null ){
			// Some actions are loaded from the table's actions.ini file and must be loaded before we return the actions.
			$table =& Dataface_Table::loadTable($tablename);
			if ( !$table->_actionsLoaded ){
				$tparams = array();
				$table->getActions($tparams, true);
			}
			unset($table);
		}
		
		
		if ( $actions === null ) $actions = $this->actions;
		foreach ( array_keys($actions) as $key ){
			if ( isset($action) ) unset($action);
			$action =& $actions[$key];
			
			if ( @$params['name'] and @$params['name'] !== @$action['name']) continue;
			if ( @$params['id'] and @$params['id'] !== @$action['id']) continue;
			
			if ( isset($params['category'])  and $params['category'] !== @$action['category']) continue;
				// make sure that the category matches
			
			if ( @$params['table'] /*&& @$action['table']*/ && !(@$action['table'] == @$params['table'] or @in_array(@$params['table'], @$action['table']) )) continue;
				// Filter actions by table
				
			if ( @$params['relationship'] && @$action['relationship'] && @$action['relationship'] != @$params['relationship']) continue;
				// Filter actions by relationship.
				
			if ( @$action['condition'] and !$app->testCondition($action['condition'], $params) ) {
				continue;
			}
			if ( isset($params['record']) ){
				if ( isset($action['permission']) and !$params['record']->checkPermission($action['permission']) ){
					continue;
				}
			} else {
				if ( isset( $action['permission'] ) and !$app->checkPermission($action['permission'])){
					continue;
				}
			}
			
			if ( @$action['selected_condition'] ) $action['selected'] = $app->testCondition($action['selected_condition'], $params);
			
			if ( isset($action['visible']) and !$action['visible']) continue;
				// Filter based on a condition
			foreach (array_keys($action) as $attribute){
				// Some entries may have variables that need to be evaluated.  We use Dataface_Application::eval()
				// to evaluate these entries. The eval method will replace variables such as $site_url, $site_href
				// $dataface_url with the appropriate real values.  Also if $params['record'] contains a 
				// Record object or a related record object its values are treated as php variables that can be 
				// replaced.  For example if a Profile record has fields 'ProfileID' and 'ProfileName' with
				// ProfileID=10 and ProfileName = 'John Smith', then:
				// $app->parseString('ID is ${ProfileID} and Name is ${ProfileName}') === 'ID is 10 and Name is John Smith'
				//if ( strpos($attribute, 'condition') !== false) continue;
				if ( eregi('condition',$attribute) ) continue;
				
				//if ( isset($action[$attribute.'_condition']) and !$app->testCondition($action[$attribute.'_condition'], $params) ){

					//$action[$attribute] = null;
				//} else {
					$action[$attribute] = $app->parseString($action[$attribute], $params);
				//}
			}
			
			$out[$key] =& $action;
			
			unset($action);
		}
		
		uasort($out, array(&$this, '_compareActions'));
		return $out;
	}
	
	/**
	 * Comparison function used for sorting actions.
	 */
	function _compareActions($a,$b){
		if ( @$a['order'] < @$b['order'] ) return -1;
		else return 1;
	}
	
	/**
	 * Adds an action to the action tool.
	 * @param $name The name of the action.
	 * @param $action An array representing the action.
	 */
	function addAction($name, $action){
		$this->actions[$name] =& $action;
	}
	
	/**
	 * Removes the action with the specified name.
	 */
	function removeAction($name){
		unset( $this->actions[$name] );
	}
	
	/**
	 * Returns a reference to the singleton ActionTool instance.
	 * @param $conf Optional configuration array with action definitions.
	 */
	function &getInstance($conf=null){
		static $instance = 0;
		if ( !$instance ){
			$instance = new Dataface_ActionTool($conf);
		}
		return $instance;
	}
	

}
