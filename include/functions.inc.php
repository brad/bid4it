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

}

function getUsername(){

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

	
}

?>
