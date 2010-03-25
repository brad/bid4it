<?php
class Dataface_GlanceList {

	var $records;
	
	function Dataface_GlanceList(&$records){
	
		$this->records =& $records;
		foreach ( array_keys($this->records) as $key){
			if ( is_a($this->records[$key], 'Dataface_RelatedRecord') ){
				$this->records[$key] = $this->records[$key]->toRecord();
			}
		}
	
	}
	
	function toHtml(){
	
		ob_start();
		df_display(array('records'=>&$this->records, 'list'=>&$this), 'Dataface_GlanceList.html');
		$out  = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	function oneLineDescription(&$record){
		$del =& $record->_table->getDelegate();
		if ( isset($del) and method_exists($del, 'oneLineDescription') ){
			return $del->oneLineDescription($record);
		}
		
		$app =& Dataface_Application::getInstance();
		$adel =& $app->getDelegate();
		if ( isset($adel) and method_exists($adel, 'oneLineDescription') ){
			return $adel->oneLineDescription($record);
		}
		
		$out = '<span class="Dataface_GlanceList-oneLineDescription">
			<span class="Dataface_GlanceList-oneLineDescription-title"><a href="'.$record->getURL('-action=view').'" title="View this record">'.$record->getTitle().'</a></span> ';
		if ( $creator = $record->getCreator()  ){
			$show = true;
			if ( isset($app->prefs['hide_posted_by']) and $app->prefs['hide_posted_by'] ) $show = false;
			if ( isset($record->_table->_atts['__prefs__']['hide_posted_by']) and $record->_table->_atts['__prefs__']['hide_posted_by'] ) $show = false;
			if ( $show ){
				$out .=
				'<span class="Dataface_GlanceList-oneLineDescription-posted-by">Posted by '.$creator.'.</span> ';
			}
		}
		
		if ( $modified = $record->getLastModified() ){
			$show = true;
			if ( isset($app->prefs['hide_updated']) and $app->prefs['hide_updated'] ) $show = false;
			if ( isset($record->_table->_atts['__prefs__']['hide_updated']) and $record->_table->_atts['__prefs__']['hide_updated'] ) $show = false;
			if ( $show ){
				$out .= '<span class="Dataface_GlanceList-oneLineDescription-updated">Updated '.df_offset(date('Y-m-d H:i:s', $modified)).'</span>';
			}
		}
		$out .= '
			</span>';
		return $out;
		
		
	}
}

