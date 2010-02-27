<?php
class tables_config {
	
	function getPermissions(&$record){
		// Check to see if the user has admin permissions
		if ( isAdmin() ) return Dataface_PermissionsTool::ALL();
		return Dataface_PermissionsTool::NO_ACCESS();
	}

}
?>
