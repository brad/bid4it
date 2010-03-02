<?php
class actions_bid {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		if ( !isset($_POST['--bid-amount']) ) {
			$err = PEAR::raiseError("No bid amount specified", DATAFACE_E_NOTICE);
			header('Location: '.$app->url('-action=view').'&--msg='.urlencode($err->getMessage()));
			exit;
		}
		$amount = floatval(preg_replace('/[^\.0-9]/', '',$_POST['--bid-amount']));
		
		if ( !isset($_POST['product_id']) ) return PEAR::raiseError("No product id specified.", DATAFACE_E_NOTICE);
		$product_id = intval($_POST['product_id']);
		
		$product =& df_get_record('products', array('product_id'=>$product_id));		
		if ( !$product){
			return PEAR::raiseError("No product could be found by that id.", DATAFACE_E_NOTICE);
		}
		
		$res = makeBid($product, $amount);
		if ( PEAR::isError($res) ) $msg = $res->getMessage();
		else $msg = "Your bid was successfully submitted.";
		$url = $product->getURL().'&--msg='.urlencode($msg);
		header("Location: ".$url);
		exit;
	}

}

?>
