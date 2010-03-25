<?php
function sendAdminEmail($subject, $msg){
		
	if ( !getConf('admin_email') ){
		error_log("Cannot send email because there is no email specified in conf.ini");
		return false;
	}
	$mail_headers = 'From: '.getConf('notification_from_address') . "\r\n" .
					'Reply-To: '.getConf('notification_from_address') . "\r\n" ;	
	return mail(getConf('admin_email'), $app->_conf['title'].' - '.$subject, $msg, $mail_headers);
		
}

function sendEmail($to,$subject,$msg){
	$mail_headers = 'From: '.getConf('notification_from_address') . "\r\n" .
					'Reply-To: '.getConf('notification_from_address') . "\r\n" ;	
	return mail($to, $app->_conf['title'].' - '.$subject, $msg, $mail_headers);
}

function getAuctionWinnerMessage($closeRec){
	$product =& df_get_record('products', array('product_id'=>$closeRec->val('product_id')));
	$product_url = $product->getURL('-action=view');
	$product_title = $product->getTitle();
	$bid_amount = '$'.number_format($closeRec->val('bid_amount'),2);
	$winner_instructions = getConf('winner_instructions');
	
	return <<<END
Congratulations! You have won the '$product_title' bid4it! item.  Your winning bid amount was $bid_amount .
Please visit $product_url for more information.

In order to collect your item, please follow these instructions:

$winner_instructions

END;

}

function &getUser(){
	static $user = 0;
	if ( $user === 0 ){
		$auth =& Dataface_AuthenticationTool::getInstance();
		$user =& $auth->getLoggedInUser();
	}
	return $user;
}

function getUsername(){
	static $username = 0;
	if ( $username === 0 ){
		$auth =& Dataface_AuthenticationTool::getInstance();
		$username = $auth->getLoggedInUsername();
	}
	return $username;
}

function isAdmin(){
	$user =& getUser();
	return ($user and $user->val('role') == 'ADMIN');
}

function isLoggedIn(){
	if ( getUser() ) return true;
	return false;
}

function isRegistered(){
	$user =& getUser();
	if ( $user->val('role') ) return true;
	return false;
}

function registerUser(){
	if ( isRegistered() ) return;
	$user =& getUser();
	$user->setValue('role','USER');
	$user->save();
}

// Returns information about a user
// array("uid"=>"jnankivell", "givenName"=>"Josef", "sn"=>"Nankivell", "cn"=>"Josef Nankivell", "mail"=>"josefnankivell@gmail.com")
// uid : Unix id
// givenName : First Name
// sn : Surname
// cn : Full name (e.g. Josef Nankivell)
// mail : Email address (e.g. josefnankivell@gmail.com)
// telephoneNumber : Phone number
// title : Title (e.g. Mr,Miss,Mrs,Ms)
// ou : Organisational unit (e.g. Finance)

function getLDAPUserInfo($userid){
	static $cache = 1;
	if ( !is_array($cache) ){
		$cache = array();
	}
	
	if ( isset($cache[$userid]) ){
		return $cache[$userid];
	}
	
	$app =& Dataface_Application::getInstance();
	$vars =& $app->_conf['_auth'];
	if ( !@$vars['ldap_host']  || !@$vars['ldap_base'] || !function_exists('ldap_connect')) return null;
	if ( !$vars['ldap_port'] ) $vars['ldap_port'] = 1389;
	list($ldap_host, $ldap_base, $ldap_port) = array($vars['ldap_host'], $vars['ldap_base'], $vars['ldap_port']);
	
	//global $ldap_host, $ldap_base, $ldap_port;
	$query = "uid=$userid, $ldap_base";
	$ldap = ldap_connect($ldap_host, $ldap_port);
	if ( $ldap ){
		$r = ldap_bind($ldap);
		$sr = ldap_search($ldap, $query, "objectclass=*");
		if ( ldap_count_entries($ldap, $sr) > 0 ){
			$val = ldap_first_entry($ldap, $sr);
			$atts = ldap_get_attributes($ldap, $val);
			foreach ($atts as $key=>$value){
				if ( is_array($value) ){
					$atts[$key] = $value[0];
				}
			}
			$cache[$userid] = $atts;
			return $atts;
		}
	} else {
		return null;
	}
}

// Emails high bidder informing that they have been outbid.
function notifyHighBidder($product){
	$app =& Dataface_Application::getInstance();
	
	$username = $product->val('prev_high_bidder');
	$product_name = $product->val('product_name');
	$high_bid = $product->display('high_bid_amount');
	$url = $product->getURL('-action=view');
	
	$mail_headers = 'From: '.getConf('notification_from_address') . "\r\n" .
    'Reply-To: '.getConf('notification_from_address') . "\r\n" ;
	
	if ( getConf('send_outbid_notifications_to_admin') ){
		// Send a notification to admin
		$newusername = $product->val('high_bidder');
		$msg =<<<END
The user '$newusername' has outbid '$username' for the item '$product_name' with a new high bid of $high_bid .
Visit $url to see this item.
END;
	
		if ( !getConf('admin_email') ){
			error_log("Cannot send outbid notification to the admin because no admin_email was specified in conf.ini.");
			return false;
		}
		
		mail(getConf('admin_email'), $app->_conf['title'].' outbid notification', $msg, $mail_headers);
	}
	
	if ( !isset($username) ) return false;
	$user =& df_get_record('users', array('username'=>'='.$username));
		
	if ( !isset($user) ){
		// Cannot find user
		return false;
	}
	
	if ( getConf('send_email_notifications') and $user->val('prefs_receive_outbid_notifications') ){
		$mail = $user->val('email');
		if ( !$mail ){
			return false;
		}
		
		$msg =<<<END
You have been outbid on the item '$product_name' with a bid amount of $high_bid .  To view the item info or bid again on this item, please visit $url .
END;
		$res = mail($mail, $app->_conf['title'].': You have been outbid on an auction item.', $msg, $mail_headers);
		
		if ( !$res ){
			error_log("Failed to send outbid notification to $mail for item ".$product->val('product_name'));
		}
	}
}

// Timezone
function getConf($name){
	static $conf = 0;
	if ( !is_array($conf) ){
		$res = mysql_query("select `timezone` from `config` limit 1", df_db());
		list($timezone) = mysql_fetch_row($res);
		if ( $timezone ){
			putenv('TZ='.$timezone);
		}
		@mysql_free_result($res);
		$temp = df_get_record('config',array());
		if ( isset($temp) ) $conf = $temp->strvals();
		else $conf = array();
	}
	if ( isset($conf[$name]) ) return $conf[$name];
	
	$app =& Dataface_Application::getInstance();
	return @$app->_conf['df_auction'][$name];
}

/**
 * Places a bid.  Return PEAR_Error objects if there are errors.  Including:
 * User is not logged in, bidding has not been opened, bidding is closed, bid is too low.
 */
function makeBid(&$product, $amount){
	$app =& Dataface_Application::getInstance();
	if ( !isLoggedIn() ){
		return PEAR::raiseError("Sorry, you must be logged in to make a bid.");
	}
	
	if ( !isRegistered() ) {
		// Register the user if not registered
		$res = registerUser();
		if ( PEAR::isError($res) ) return $res;
	}
	
	$bid =& new Dataface_Record('bids', array());
	$bid->setValues(array(
		'username'=>getUsername(),
		'bid_amount'=>$amount,
		'product_id'=>$product->val("product_id"),
		'bid_status'=>getConf('default_bid_status')
		)
	);
	
	$res = $bid->save();
	if ( PEAR::isError($res) ) return $res;
	notifyHighBidder($product);
	return $res;
}

// Close the auction
function closeAuction(&$product){
	if ( $product->val('cooked_closing_time_seconds') > time() ){
		return PEAR::raiseError("The auction for item '".$product->getTitle()."' cannot be closed early.");
	}
	
	$app =& Dataface_Application::getInstance();
	$username = $product->val('high_bidder');
	$amount = $product->val('high_bid_amount');
	$closeRec = df_get_record('closed', array('product_id'=>$product->val('product_id')));
	
	if ( !$closeRec ){
		$closeRec = new Dataface_Record('closed', array());
		$closeRec->setValues(array('product_id'=>$product->val('product_id'), 'winner'=>$username, 'bid_amount'=>$amount));	
	}
	
	if (!isset($username) ){
		// Nobody wins due to no username set for the high bidder.
		// Send email to the admin about this product.
		if ( !$closeRec->val('admin_email_sent') ){
			sendAdminEmail('Auction closed without any bids','The auction for item "'.$product->getTitle().'" was closed without any bids being placed.');
			$closeRec->setValue('admin_email_sent',1);
			$closeRec->save();
		}
		return PEAR::raiseError("Nobody bid on the item '".$product->getTitle()."'");
	}

	// Recoord email was sent, so it is sent once only.
	$user =& df_get_record('users', array('username'=>'='.$username));
	if ( !$user ){
		$user = new Dataface_Record('users', array());
		$user->setValue('username',$username);
		$user->save();
	}
	if ( !isset($user) ){
		// Winner listed, but not found in the users table
		if ( !$closeRec->val('admin_email_sent') ){
			if ( sendAdminEmail('Action Required: Contact Auction Winner', 'The auction for product "'.$product->getTitle()."' was won by the user '$username', but no information about this user could be found in the users table.  Please contact this user and let him or her know that he/she has won the auction.")){
				$closeRec->setValue('admin_email_sent', 1);
				$closeRec->save();
			} else {
				return PEAR::raiseError("Failed to send email to admin");
			}
		}
		return PEAR::raiseError("The user '$username' who won the auction for product '".$product->getTitle()."' could not be found.");
		
	}

	// Inform admin
	$mail = $user->val('email');
	if ( !$mail ){
		if ( !$closeRec->val('admin_email_sent') ){
			if ( sendAdminEmail('Attention: Contact Auction Winner', 'The auction for item "'.$product->getTitle()."' was won by the user '$username', however, no email address for this user was found. Contact this user and inform them that they have won the item.")){
				$closeRec->setValue('admin_email_sent',1);
				$closeRec->save();
			} else {
				return PEAR::raiseError("Failed to send email to admin.");
			}
		}
		return PEAR::raiseError("No email address found for the user '$username' who won the auction for product '".$product->getTitle()."'");	
	}
	
	if ( !$closeRec->val('email_sent') ){
		if ( sendEmail($mail, 'Attention: You have won the item', getAuctionWinnerMessage($closeRec)) ){
			$closeRec->setValue('email_sent',1);
		} else {
			return PEAR::raiseError("Failed to send item win confirmation to the winner.");
		}
	}
	
	if ( !$closeRec->val('admin_email_sent') ){
		if ( sendAdminEmail('Note: Auction closed & winner notified', "The auction for the item '".$product->getTitle()."' has been won by the user '$username'. \n\nThe user has been sent an email notification and has been given instructions for claiming the item.  For more information about the item, see ".$product->getURL('-action=view').".  For user details about the highest bidder, see ".$user->getURL('-action=view'))){
			$closeRec->setValue('admin_email_sent', 1);
		} else {
			$closeRec->save();
			return PEAR::raiseError("Failed to send email confirmation to admin about the winner. However, the email was successfully sent to the user.");	
		}
	}
	$closeRec->save();
	return true;	
}

function closeAuctions(){
	$sql = "select p.product_id from products p left join closed c on p.product_id=c.product_id where (c.product_id IS NULL or c.email_sent=0 or c.admin_email_sent=0) and p.closing_time < NOW()";
	
	$res = mysql_query($sql, df_db());
	if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
	$results = array();
	while ($row = mysql_fetch_row($res) ){
		list($product_id) = $row;
		$product = df_get_record('products', array('product_id'=>$product_id));
		$results[$product_id] = closeAuction($product);
	}
	return $results;

}

function getTimezones(){
	static $lang = -1;
	if ( $lang == -1 ){
		$lang = array();
		if ( function_exists('timezone_abbreviations_list') ){
			foreach (timezone_identifiers_list() as $tzname ){
				if ( !trim($tzname) ) continue;
				$tz = new DateTimeZone($tzname);
				$dt = new DateTime("now", $tz);
				$lang[$tzname] = $tzname. ' ('.date('h:i a', time()+$tz->getOffset($dt)-date('Z')).')';
		}
		} else {
			$lang[''] = 'Timezones require PHP 5.1 or higher';
		}
	}
	return $lang;
}

?>
