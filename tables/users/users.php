<?php
class tables_users{
	function getPermissions(&$record){
		if (isAdmin() or ($record and ($record->strval('username') == getUsername()))) {
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
		if ( isAdmin() ){
		return Dataface_PermissionsTool::ALL();
		} else {
		return Dataface_PermissionsTool::READ_ONLY();
		}
	}
	
	function block__after_view_tab_content(){
		if (isAdmin()){
			$app =& Dataface_Application::getInstance();
			$record =& $app->getRecord();
			df_display(array('user'=>&$record), 'after_user_profile.html');
		}
	}
	
	function field__fullname(&$record){
		return $record->val('firstname').' '.$record->val('lastname');
	}
	
	function role__default(){
		return 'USER';
	}
	
	function beforeSave(&$record){
		if ( $record->valueChanged('username') ){
			$res = mysql_query("select count(*) from `users` where `username`='".addslashes($record->strval('username'))."'", df_db());
			if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
			list($num) = mysql_fetch_row($res);
			if ( $num > 0 ) return PEAR::raiseError("Username has been taken. Please choose another one.",DATAFACE_E_NOTICE);
		}	
	}
}
?>
