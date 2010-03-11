<?php

// Hanldes the user's profile.

class dataface_actions_my_profile {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$auth =& Dataface_AuthenticationTool::getInstance();
		
		if ( $auth->isLoggedIn() ){
			// forward to the user's profile
			$user =& $auth->getLoggedInUser();
			header('Location: '.$user->getURL());
			exit;
		} else {
			header('Location: '.$app->url('-action=login_prompt').'&--msg='.urlencode('You must be logged in to do this.'));
		}
	}
}
