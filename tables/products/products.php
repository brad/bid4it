<?php
class tables_products {

	function afterDelete(&$record){
		// Delete the image when the product is removed
	}


	function field__high_bid(&$record){
		// Highest bid amount
	}
	
	
	function field__high_bidder(&$record){
		// User with the highest bid
	}
	
	
	function field__prev_high_bid(&$record){
		// Bid amount that just been outbid
	}
	
	function field__prev_high_bid_amount(&$record){
		// Amount of the last highest bid
	}
	
	function field__prev_high_bidder(&$record){
		// Previous highest bidder
	}
	
	function field__high_bid_amount(&$record){
		// Amount of highest bid
	}
	
	function high_bid_amount__display(&$record){
		// Shows the amount of the higest bid
	}

	function field__isOpen(&$record){
		// Can the product still be bid on
	}	
	
	function field__cooked_closing_time_seconds(&$record){
		// How long until closed
	}
	
	function field__opening_time_seconds(&$record){
		// Opening time of the product
	}
	
	function field__cooked_minimum_bid(&$record){
		// Minimum next bid amount	
	}
	
	function seller_username__default(){
		// Returns the username of the seller
		return getUsername();
	}
	
	function minimum_bid__default(){
		// Default minimum bid
	}
	
	function opening_time__default(){
		// Default opening time
		$time = getConf('default_opening_time');
		if ( !$time ) $time = date('Y-m-d H:i:s');
		return $time;
	}

	function closing_time__default(){
		// Default closing time		
	}
	

	function current_high_bid__display(&$record){
		// Display higest current bidder
		return '$'.number_format($record->val('current_high_bid'),2);		
	}
	
	function minimum_bid__display(&$record){
		// Display the minimum bid required
		return '$'.number_format($record->val('minimum_bid'),2);
	}

//	function block__view_tab_content(){
//	}
	
//	function block__result_list(){
//	}
	
}
?>
