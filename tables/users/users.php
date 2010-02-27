<?php
class tables_users{
	function getPermissions(&$record){
		if ( isAdmin() or ( $record and ($record->strval('username') == getUsername()))) {
			$perms = Dataface_PermissionsTool::ALL();
		} else {
			$perms = Dataface_PermissionsTool::READ_ONLY();
		}

		$perms['new'] = 1;
		return $perms;
	}
	
	function username__permissions(&$record){
		$perms = $this->role__permissions($record);
		$perms['new'] = 1;
		return $perms;
	}
	
	function role__permissions(&$record){

	}
	
	function block__after_view_tab_content(){

	}
	
	function field__fullname(&$record){

	}
	
	function role__default(){

	}
	
	function beforeSave(&$record){

	}
}
?>
