<?php
class tables_products {

	function afterDelete(&$record){
		// Delete the image when the product is removed
	}

	function field__high_bid(&$record){
		// Highest bid amount
		$reverseAuction = getConf('reverse_auction');
		if ($reverseAuction) $sort = 'bid_amount asc';
		else $sort = 'bid_amount desc';		
		$bids = df_get_records_array('bids', 
			array('product_id'=>$record->val('product_id'),
				'bid_status'=>'APPROVED',
				'-sort'=>$sort,
				'-limit'=>1
			)
		);

		if ( count($bids) > 0 ) return $bids[0];
		return null;
	}
	
	function field__high_bidder(&$record){
		// User with the highest bid
		$bid = $record->val('high_bid');
		if ($bid){
			return $bid->val('username');
		}
		return null;
	}
	
	function field__prev_high_bid(&$record){
		// Bid amount that just been outbid
		$reverseAuction = getConf('reverse_auction');
		if ( $reverseAuction ) $sort = 'bid_amount asc';
		else $sort = 'bid_amount desc';
			
		$bids = df_get_records_array('bids', 
			array(
				'product_id' => $record -> val('product_id'),
				'bid_status' => 'APPROVED',
				'-sort' => $sort,
				'-skip' => 1,
				'-limit' => 1
			)
		);
		if ( count($bids) > 0 ) return $bids[0];
		return null;
	}
	
	function field__prev_high_bid_amount(&$record){
		// Amount of the last highest bid
		$bid = $record->val('prev_high_bid');
		if ( !isset($bid) ) return 0;
		return $bid->val('bid_amount');
	}
	
	function field__prev_high_bidder(&$record){
		// Previous highest bidder
		$bid = $record->val('prev_high_bid');
		if ( $bid ){
			return $bid->val('username');
		}
		return null;
	}
	
	function field__high_bid_amount(&$record){
		// Amount of highest bid
		$bid = $record->val('high_bid');		
		if (!isset($bid)) return 0;
		return $bid->val('bid_amount');
	}
	
	function high_bid_amount__display(&$record){
		// Shows the amount of the higest bid

	}

	function field__isOpen(&$record){
		// Can the product still be bid on
		return ($record->val('opening_time_seconds') < time() and time() < $record->val('cooked_closing_time_seconds'));
	}	
	
	function field__cooked_closing_time_seconds(&$record){
		// How long until closed
		$closing_time = $record->strval('closing_time');
		if ( !$closing_time ){
			$app =& Dataface_Application::getInstance();
			$closing_time = $app->_conf['df_auction']['closing_time'];
		}
		
		$closing_time_seconds = strtotime($closing_time);
		$high_bid = $record->val('high_bid');
		if ( $high_bid ){
			$bid_time = $high_bid->strval('time_of_bid');
			$closing_time_seconds = max($closing_time_seconds, strtotime($bid_time));
		}
		return $closing_time_seconds;
	}
	
	function field__opening_time_seconds(&$record){
		// Opening time of the product
		return strtotime($record->strval('opening_time'));
	}
	
	function field__cooked_minimum_bid(&$record){
		// Minimum next bid amount
		if ( $record->val('bid_increment')){
			$increment = floatval($record->val('bid_increment'));
		} else {
			$increment = floatval(getConf('bid_increment'));
		}
		if ( getConf('reverse_auction') ){
			$increment = $increment * (-1);
		}
		return max($record->val('minimum_bid'), $record->val('high_bid_amount')+$increment);		
	}
	
	function seller_username__default(){
		// Returns the username of the seller
		return getUsername();
	}
	
	function minimum_bid__default(){
		// Default minimum bid
		return getConf('default_minimum_bid');
	}
	
	function opening_time__default(){
		// Default opening time
		$time = getConf('default_opening_time');
		if ( !$time ) $time = date('Y-m-d H:i:s');
		return $time;
	}

	function closing_time__default(){
		// Default closing time
		$time = getConf('default_closing_time');
		if ( !$time ) $time = date('Y-m-d H:i:s', time()+(60*60*24*7*2)); // 2 weeks from now
		return $time;	
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
