<?php

/**
 * Action searches through the related records of a particular record and returns all matching records.
 */

class dataface_actions_single_record_search {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		$query =& $app->getQuery();
		if ( !isset($query['--subsearch']) ) $query['--subsearch'] = '';
		
		$results = array();
		
		foreach ( $record->_table->relationships() as $rname=>$r){
			$fields = $r->fields(true);
			$qstr = array();
			foreach ( $fields as $field ){
				//list($tname, $field) = explode('.', $field);
				$qstr[] = '`'.str_replace('`','',$field)."` LIKE '%".addslashes($query['--subsearch'])."%'";
			}
			$qstr = implode(' OR ', $qstr);
			$results[$rname] = $record->getRelatedRecordObjects($rname, 0, 10, $qstr);
			unset($r);
			unset($fields);
		}
		
		if ( @$query['--format'] == 'RSS2.0' ){
			$this->handleRSS($results);
		} else {
			df_display(array('results'=>&$results, 'queryString'=>$query['--subsearch']), 'Dataface_single_record_search.html');
		}
	}
	
	function handleRSS($results){
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		$query =& $app->getQuery();
		import('feedcreator.class.php');
		import('Dataface/FeedTool.php');
		$ft =& new Dataface_FeedTool();
		$rss = new UniversalFeedCreator(); 
		$rss->encoding = $app->_conf['oe'];
		//$rss->useCached(); // use cached version if age < 1 hour
		$rss->title = $record->getTitle().'[ Search for "'.$query['--subsearch'].'"]';
		$rss->description = '';
		
		$rss->link = htmlentities(df_absolute_url($app->url('').'&--subsearch='.urlencode($query['--subsearch'])));
		$rss->syndicationURL = $rss->link;
		
		foreach ($results as $result){
			foreach ($result as $rec){
				$rss->addItem($ft->createFeedItem($rec->toRecord()));
			}
		}
		
		if ( !$query['--subsearch'] ){
			$rss->addItem($ft->createFeedItem($record));
		}
		
		header("Content-Type: application/xml; charset=".$app->_conf['oe']);
		echo $rss->createFeed('RSS2.0');
		exit;
	}

}
