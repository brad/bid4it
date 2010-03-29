<?php
class actions_watch_list {

	function handle(&$params){
		import('Dataface/RecordGrid.php');
		$reverseAuction = getConf('reverse_auction');
		
		if ( $reverseAuction ){
			$highbidamount = 'min(bid_amount)';
			$highbidderq = 'bid_amount<b.bid_amount';
		} else {
			$highbidamount = 'max(bid_amount)';
			$highbidderq = 'bid_amount>b.bid_amount';
		}
		
		$sql = "select p.product_id, p.product_name, concat('\$',format(high_bid,2)) as high_bid, high_bidder from products p inner join
					(
						select product_id, $highbidamount as high_bid from bids group by product_id
					) as hb
					on p.product_id=hb.product_id
					inner join 
					(
						select product_id, username as high_bidder from bids b where not exists (select * from bids where product_id=b.product_id and $highbidderq)
					) as hbr
					on p.product_id=hbr.product_id
				where '".getUsername()."' in 
					(select username from bids where product_id=p.product_id)
					
				";
			
		$res = mysql_query($sql, df_db());
		if (!$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
		$data = array();
		while ($row = mysql_fetch_assoc($res) ) {
			if ( $row['high_bidder'] != getUsername() ) $row['high_bidder'] = '';
			$data[] = $row;
		}
		if ( count($data) > 0 ){
			$grid = new Dataface_RecordGrid($data);
			$grid->addActionCellCallback(array(&$this,'actionCell'));
			$grid_out = $grid->toHtml();
		} else {
			$grid_out = "<p>You have not bid on an item.</p>";
		}
		df_display(array('grid'=>$grid_out) , 'watch_list.html');
	}
	
	function actionCell($row){
		return "<a href=\"".DATAFACE_SITE_HREF."?-action=view&-table=products&product_id=".$row['product_id']."\">Details</a> &nbsp;";
	}
}

?>
