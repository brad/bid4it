<?php
class tables_bids {

	var $cache = array();

	function beforeSave(&$record){
		$reverseAuction = getConf('reverse_auction');
		
		if ( !isAdmin() || @$_REQUEST['--force-validate'] ){
			$product =& $record->val('product_object');

		}
	}
	
	function username__default(){
	}
	
	function bid_status__default(){
	}
	
	function field__product_object(&$record){
	}	

}
?>
