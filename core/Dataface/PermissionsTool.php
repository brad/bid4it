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
class Dataface_PermissionsTool {

	
	var $_cache = array();
	/**
	 * An associative array of role permissions available.
	 * [Role Name] -> array([Permission Name] -> [Allowed (0 or 1)])
	 */
	var $rolePermissions = array();
	
	/**
	 * Associative array of the loaded permissions. [Permission name] -> [Permission Label].
	 */
	var $permissions = array();
	
	var $context = null;
	
	
	function Dataface_PermissionsTool($conf = null){
	
		if ( $conf === null ){
			import('Dataface/ConfigTool.php');
			$configTool =& Dataface_ConfigTool::getInstance();
			$conf = $configTool->loadConfig('permissions');
		
		}
		
		$this->addPermissions($conf);
		//print_r($this->permissions);
	}
	
	function &getContext(){ return $this->context; }
	function setContext($context){
		if ( isset($this->context) ) unset($this->context);
		$this->context =& $context;
	}
	
	function clearContext(){
		$this->context = null;
	}
	
	function &PUBLIC_CONTEXT(){
		static $pcontext = 0;
		if ( !is_object($pcontext) ){
			$pcontext = new Dataface_PermissionsTool_PublicSecurityContext();
		}
		return $pcontext;
	}
	
	/**
	 * Adds permissions as loaded from a configuration file.  Key/Value pairs
	 * are interpreted as being permission Name/Label pairs and key/Array(key/value)
	 * are interpreted as being a role defintion.
	 */
	function addPermissions($conf){
		$this->_cache = array();
		foreach ( array_keys($conf) as $key ){
			// iterate through the config options
			if ( is_array($conf[$key]) ){
				// If the configuration option is an array then it must be a role (i.e. a section of the config file).
				if ( strpos($key, 'extends') !== false ){
				
					// deal with the case where the role 'extends' other roles: 
					// of the form [ROLE extends PARENT1, PARENT2]
					list($role, $parents) = explode('extends', $key);
					$oldkey = $key;
					$key = trim($role);
						// Place the role bach as the key for this iteration.
					$parents = array_map('trim', explode(',', $parents));
						// separate the comma-delimited list of parent roles
					$conf[$key] =& $conf[$oldkey];
					unset($conf[$oldkey]);
						
				} else {
					$parents = array();
				}
				if ( !isset($this->rolePermissions[$key]) ) $this->rolePermissions[$key] = array();
				foreach ($parents as $parent){
					if ( !isset($this->rolePermissions[$parent]) ){
						trigger_error(
							Dataface_LanguageTool::translate(
								'Role not defined but specified as parent',
								'The role "'.$parent.'" was specified as a parent role for the role "'.$key.'".  Check that the role exists in the permissions.ini file.  Note that parent roles must be defined before child roles in the permissions.ini file.',
								array('parent'=>$parent, 'child'=>$key)
							),
							E_USER_ERROR
						);
					}
					$this->rolePermissions[$key] = array_merge($this->rolePermissions[$key], $this->rolePermissions[$parent]);
				}
				$this->rolePermissions[$key] = array_merge(
					$this->rolePermissions[$key],
					$conf[$key]
				);
				$this->rolePermissions[$key] = array_map('intval', $this->rolePermissions[$key]);
				
			} else {
				$this->permissions[$key] = $conf[$key];
			}
		}
	}
	
	/**
	 * Gets singleton instance of permissions tool.
	 * This  is to be called statically.
	 */
	function &getInstance(){
		
		static $instance = null;
		if ( $instance === null ){
			$instance = new Dataface_PermissionsTool();
		}
		return $instance;
	}
	
	/**
	 * Gets the permissions of an object.
	 * @param $obj A Dataface_Table, Dataface_Record, or Dataface_Relationship record we wish to check.
	 * @param #2 Optional field name whose permission we wish to check.
	 */
	function getPermissions(&$obj, $params=array()){
		$me =& Dataface_PermissionsTool::getInstance();
		if ( isset($me->context) ){
			return $me->context->getPermissions($obj, $params);
		}
		if (
			is_a($obj, 'Dataface_Table') or 
			is_a($obj, 'Dataface_Record') or
			is_a($obj, 'Dataface_RelatedRecord') or
			is_a($obj, 'Dataface_Relationship') ){
			//echo "Getting permissions: "; print_r($params);
			return $obj->getPermissions($params);
		}
		trigger_error(
			df_translate(
				'scripts.Dataface.PermissionsTool.getPermissions.ERROR_PARAMETER_1',
				'In Dataface_PermissionsTool, expected first argument to be Dataface_Table, Dataface_Record, or Dataface_Relationship, but received '.get_class($obj)."\n<br>",
				array('class'=>get_class($obj))
				)
			.Dataface_Error::printStackTrace(),E_USER_ERROR);
	}
	
	/**
	 * Checks to see if a particular permission is granted in an object or permissions array.
	 * @param $permissionName The name of the permission to check (one of {'view','edit','delete'})
	 * @param $perms The object or permissions array to check.  It this is an object it must be of type one of {Dataface_Table, Dataface_Record, or Dataface_Relationship}.
	 * @param $params Optional field name in the case that param #2 is a table or record.
	 */
	function checkPermission($permissionName, $perms, $params=array()){
		$me =& Dataface_PermissionsTool::getInstance();
			
		
		if ( is_array($perms) ){
			return  (isset( $perms[$permissionName]) and $perms[$permissionName]);
		}
		
		if ( PEAR::isError($perms) ){
			trigger_error($perms->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		if ( !is_object($perms) ){
			return  array();
			//trigger_error("In Dataface_PermissionsTool::view() cannot get permissions for scalar element.\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		// If we are this far, then $perms must be an object.. so we must get the object's 
		// permissions array and recall this method on it.
		return $me->checkPermission($permissionName, $me->getPermissions($perms, $params) );
	}
	
	/**
	 * Checks to see if an object or permissions array has view permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	function view(&$perms, $params=array()){
		$me =& Dataface_PermissionsTool::getInstance();
		return $me->checkPermission('view', $perms, $params);
		
	}
	
	/**
	 * Checks to see if an object or permissions array has edit permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.	
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	function edit(&$perms, $params=array()){
		$me =& Dataface_PermissionsTool::getInstance();
		return $me->checkPermission('edit', $perms, $params);
		
	}
	
	/**
	 * Checks to see if an object or permissions array has delete permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 * @param $perms Either an object (Table or Record) or a permissions array.
	 * @param #2 Optional name of a field we wish to check (only if $perms is a Table or Record).
	 */
	function delete(&$perms, $params=array()){
		$me =& Dataface_PermissionsTool::getInstance();
		
		return $me->checkPermission('delete',$perms,$params);
	}
	
	function MASK(){
		$me =& Dataface_PermissionsTool::getinstance();
		if ( isset($me->_cache['mask'] ) ) return $me->_cache['mask'];
		else {
			
			//$perms = array_flip($me->permissions);
			//$perms = array_map(array(&$me, '_zero'), $me->permissions);
			$perms = $me->permissions;
			foreach (array_keys($perms) as $key){
				$perms[$key] = 0;
			}
			$me->_cache['mask'] = $perms;
			return $perms;
		}
		
	}
	
	function _zero(){
		return 0;
	}
	
	function _one(){
		return 1;
	}
	
	/**
	 * Reference to static NO ACCESS permissions array.
	 */
	function NO_ACCESS(){
		static $no_access = 0;
		if ( $no_access === 0 ){
			$no_access = Dataface_PermissionsTool::MASK();
		}
		return $no_access;
	}
	
	/**
	 * Reference to permissions array that have only view permissions.
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	function READ_ONLY(){
		$me =& Dataface_PermissionsTool::getInstance();
		if ( isset($me->_cache['read_only']) ) return $me->_cache['read_only'];

		
		$read_only = /*array_merge($me->MASK(),*/ $me->getRolePermissions('READ ONLY')/*)*/;
		$read_only = array_map('intval', $read_only);
		$me->_cache['read_only'] = $read_only;
		
		return $read_only;
	}
	
	/**
	 * Reference to permissions array that has all permissions (view, edit, and delete).
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	function ALL(){
		$me =& Dataface_PermissionsTool::getInstance();
		if ( isset($me->_cache['all']) ) return $me->_cache['all'];
		$perms = array();
		foreach ( array_keys($me->permissions) as $key){
			$perms[$key] = 1;
		}
		$me->_cache['all'] = $perms;
		return $perms;
	}
	
	/**
	 * Reference to permissions array that has read and edit access (but not delete).
	 * !! NOTE THAT THIS METHOD IS DEPRECATED AS OF VERSION 0.6 .  PLEASE USE
	 * !! getRolePermissions()	instead.
	 */
	function &READ_EDIT(){
		$me =& Dataface_PermissionsTool::getInstance();
		if ( isset($me->_cache['read_edit']) ) return $me->_cache['read_edit'];
		$read_and_edit = /*array_merge($me->MASK(),*/ $me->getRolePermissions('EDIT')/*)*/;
		$read_and_edit = array_map('intval', $read_and_edit);
		$me->_cache['read_edit'] = $read_and_edit;
		return $read_and_edit;
	}
	
	
	/**
	 * Returns the permissions that are assigned to a certain role.  This allows a set of permissions
	 * to be grouped together and returned by getPermissions() methods.  A role is essentially just
	 * a list of permissions that are associated with the name of the role.  Roles can be defined in the
	 * permissions.ini files which are located in any table configuration folder, the application folder,
	 * or the dataface folder.  Try to place the roles in the appropriate folder based on what it is 
	 * most closely related to.  For example, if the role is specifically related to one table then place
	 * it in the permissions.ini file for that table, but if it is more general you can place it in the
	 * permissions.ini file for the application.  This will allow for better modularization and re-use
	 * of useful table definitions between applications.  The goal here is to allow you to distribute
	 * your tables to others so that they can be added easily to other applications.  If everything 
	 * relating to the table is located in one folder then this becomes much easier.
	 * @param $roleName The name of the role.
	 *
	 * @returns An array of permissions (the keys are the permission names, and the values are the permission
	 * labels.
	 */
	function &getRolePermissions($roleName){
		$me =& Dataface_PermissionsTool::getInstance();
		if ( !isset($me->rolePermissions[$roleName]) ){
			// it looks like the role has not been defined
			trigger_error(
				Dataface_LanguageTool::translate(
					'Role not found',
					'The role "'.$roleName.'" is not a registered role.'. Dataface_Error::printStackTrace(),
					array('role'=>$roleName)
				), E_USER_ERROR
			);
		}
		
		return $me->rolePermissions[$roleName];
		
	
	}
	
	
	/**
	 * Returns a list of names of granted permissions in a given permissions array.
	 */
	function namesAsArray($permissions){
		if ( !is_array($permissions) ) echo Dataface_Error::printStackTrace();
		$names = array();
		foreach ( $permissions as $key=>$value){
			if ( $value ){
				$names[] = $key;
			}
		}
		
		return $names;
	}
	
	
	/**
	 * Returns comma-delimited list of names of granted permissions in a given permissions
	 * array.
	 */
	function namesAsString($permissions){
		return implode(',', Dataface_PermissionsTool::namesAsArray($permissions));
	}
	
	function cachePermissions(&$record, $params, $perms){
		if (!isset($record) ){
			if ( isset($params['table']) ){
				$record_id = $params['table'];
			} else {
				$record_id='__null__';
			}
		}
		else $record_id = $record->getId();
		
		if ( count($params) > 0 ){
			$qstr = array();
			foreach ( $params as $key=>$value ){
				if ( is_object($value) or is_array($value) ) return null;
				$qstr[] = urlencode($key).'='.urlencode($value);
			}
			$qstr = implode('&', $qstr);
		} else {
			$qstr = '0';
		}
		
		$this->_cache['__permissions'][$record_id][$qstr] = $perms;
		
	}
	
	function getCachedPermissions(&$record, $params){
		if (!isset($record) ){
			if ( isset($params['table']) ){
				$record_id = $params['table'];
			} else {
				$record_id='__null__';
			}
		}
		else $record_id = $record->getId();
		
		if ( count($params) > 0 ){
			$qstr = array();
			foreach ( $params as $key=>$value ){
				if ( is_object($value) or is_array($value) ) return null;
				$qstr[] = urlencode($key).'='.urlencode($value);
			}
			$qstr = implode('&', $qstr);
		} else {
			$qstr = '0';
		}
		
		if (isset($this->_cache['__permissions'][$record_id][$qstr]) ){
			return $this->_cache['__permissions'][$record_id][$qstr];
		} else {
			return null;
		}
	}
	

	
	

}


class Dataface_PermissionsTool_PublicSecurityContext {
	function getPermissions(&$record){
		return Dataface_PermissionsTool::ALL();
	}
}

