<?php
class tables_bids {

	var $cache = array();

	function beforeSave(&$record){
		$reverseAuction = getConf('reverse_auction');
		
		if ( !isAdmin() || @$_REQUEST['--force-validate'] ){
			$product =& $record->val('product_object');
			$closing_time = $product->val('cooked_closing_time_seconds');
			if ( $closing_time < time() ){
				// Closed bidding
				$msg = "The bidding for this item finished at ".date('Y-m-d H:i:s', $closing_time).".";
				$msg = df_translate('MESSAGE_BIDDING_CLOSED', $msg, array('closing_time'=>date('Y-m-d H:i:s', $closing_time))); 
				return PEAR::raiseError($msg, DATAFACE_E_NOTICE);
			}

			$opening_time = $product->val('opening_time_seconds');
			if ( $opening_time > time() ){
				// Bidding has not started
				$msg = "You cannot bid on this item until: ".date('Y-m-d H:i:s', $opening_time).".";
				$msg = df_translate('MESSAGE_BIDDING_NOT_OPEN_YET', $msg, array('opening_time'=>date('Y-m-d H:i:s', $opening_time)));
				return PEAR::raiseError($msg, DATAFACE_E_NOTICE);
			}
		}
	}
	
	function username__default(){
		$user =& getUser();
		if ($user) return $user->val('username');
		return null;
	}
	
	function bid_status__default(){
		$app =& Dataface_Application::getInstance();
		return $app->_conf['df_auction']['default_bid_status'];
	}
	
	function field__product_object(&$record){
		return df_get_record('products', array('product_id'=>$record->val('product_id')));
	}	

}
?>
