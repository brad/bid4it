<?php

class dataface_actions_calendar {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		// Navigation array
		$nav = array(
			'prev'=>array('label'=>null, 'url'=>null),
			'next'=>array('label'=>null, 'url'=>null),
			'current'=>array('label'=>null)
			);

		// Import Ontology
		import('Dataface/Ontology.php');
		
		Dataface_Ontology::registerType('Event', 'Dataface/Ontology/Event.php', 'Dataface_Ontology_Event');
		$ontology =& Dataface_Ontology::newOntology('Event', $query['-table']);
		
		$dateAtt = $ontology->getFieldname('date');
		if ( PEAR::isError($dateAtt) ) die($dateAtt->getMessage());
		if ( !isset($query[$dateAtt]) or !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\.\.[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $query[$dateAtt]) ){
			$query[$dateAtt] = date('Y-m-01').'..'.date('Y-m-31');
			
		}
		
		list($startDate) = explode('..',$query[$dateAtt]);
		$startTime = strtotime($startDate);
		$prevMonth = (intval(date('m', $startTime)) -1 );
		$nextMonth = (intval(date('m', $startTime)) +1 );
		
		$prevTime = mktime(0,0,0,$prevMonth,1,date('Y', $startTime));
		$nextTime = mktime(0,0,0,$nextMonth,1,date('Y', $startTime));
		
		$nav['prev']['label'] = date('F Y', $prevTime);
		$nav['prev']['url'] = $app->url('-action=calendar&'.$dateAtt.'='.urlencode(date('Y-m-01',$prevTime).'..'.date('Y-m-31', $prevTime)), true, true);
		
		$nav['next']['label'] = date('F Y', $nextTime);
		$nav['next']['url'] = $app->url('-action=calendar&'.$dateAtt.'='.urlencode(date('Y-m-01',$nextTime).'..'.date('Y-m-31', $nextTime)), true, true);
		
		$nav['current']['label'] = date('F Y', $startTime);
		
		$query['-limit'] = 500;
		
		$records =& df_get_records_array($query['-table'], $query);
		
		$events = array();
		foreach ( $records as $record){
			$event = $ontology->newIndividual($record);
			$events[] = array('title'=>$record->getTitle(), 'description'=>$record->getDescription(), 'date'=>strtotime($event->strval('date'))*1000, 'startTime'=>strtotime($event->strval('date'))*1000, 'record_id'=>$record->getId());
			
			unset($event);
			unset($record);
		}
		
		// Import JSON
		import('Services/JSON.php');
		$json =& new Services_JSON();
		$event_data = 'var events = '.$json->encode($events);
		
		// Import results
		import('Dataface/ResultList.php');
		$rs = new Dataface_ResultList($query['-table']);
		
		// Display event data - current time, start time.
		df_display(array('event_data'=>$event_data,'nav'=>&$nav, 'currentTime'=>$startTime, 'filters'=>$rs->getResultFilters()), 'Dataface_Calendar.html');
	}

}
