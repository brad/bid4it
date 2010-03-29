<?php
class actions_reports {
	function handle(&$params){

		$sql = "select p.product_id,p.product_name,w.winner,w.bid_amount,if(w.email_sent,'Yes','No') as email_sent,if(w.admin_email_sent,'Yes','No') as admin_email_sent,convert_tz(w.close_date,'SYSTEM','".df_utc_offset()."') as close_date
			from products p left join closed w on p.product_id=w.product_id";
	
		$res = mysql_query($sql, df_db());
		import('Dataface/RecordGrid.php');
		$data = array();
		while ($row = mysql_fetch_assoc($res) ) {
			if ( @$row['winner']){
				$user = new Dataface_Record('users', array('username'=>$row['winner']));
				$row['email'] = $user->val('email');
				$row['full name'] = $user->val('fullname');
				unset($user);
			}
			if ( @$row['bid_amount'] ){
				$row['bid_amount'] = '$'.number_format($row['bid_amount'],2);
			}
			$data[] = $row;
		}
		$grid = new Dataface_RecordGrid($data);
		df_display(array('grid'=>$grid->toHtml()), 'reports.html');
	}
}
?>
