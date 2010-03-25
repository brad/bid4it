<?php
 
/**
 * Description: Factoring blob handling out of Dataface_Application.
 * Only accessed via Dataface_Application.
 */
 
class Dataface_Application_blob {

	
	function _parseRelatedBlobRequest($request){
		if ( !is_a($this, 'Dataface_Application') ){
			trigger_error('Dataface_Application_blob methods can only be accessed via Dataface_Application.'.Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		import('dataface-public-api.php');
		if ( !isset( $request['-field'] ) ) die("Could not complete request.  No field name specified.");
		if ( !isset( $request['-table'] ) ) die("Could not complete request.  No table specified.");
		
		$record =& df_get_record($request['-table'], $request);
		if ( strpos($request['-field'], '.') === false ){
			die("ParseRelatedBlobRequest only works for -field parameters refering to related fields.");
		}
		list($relationship, $relativeField) = explode('.', $request['-field']);
		if ( @$request['-where'] ) $where = stripslashes($request['-where']);
		else $where = 0;
		$rrecords =& $record->getRelatedRecordObjects($relationship, 0, 1, $where);
		if (count($rrecords) == 0 ){
			die("No records found");
		}
		$rrecord =& $rrecords[0];
		
		$relationshipRef =& $rrecord->_relationship;
		$domainTable =& $relationshipRef->getDomainTable();
		if ( !$domainTable || PEAR::isError($domainTable) ){
			unset($domainTable);
			$destinationTables = $relationshipRef->destinationTables();
			$domainTable = reset($destinationTables);
		}
		$out = array('-table'=>$domainTable, '-field'=>$relativeField);
		
		$domainTableRef =& Dataface_Table::loadTable($domainTable);
		foreach ( array_keys($domainTableRef->keys()) as $key){
			$out[$key] = $rrecord->strval($key);
		}
		
		return $out;
		
	
	}
	
	
	/**
	 *
	 * Blob requests are ones that only want the content of a blob field in the database - Images.
	 *
	 * @param $request  A reference to the global $_REQUEST variable generally.
	 *
	 */
	function _handleGetBlob($request){
		if ( !is_a($this, 'Dataface_Application') ){
			trigger_error('Dataface_Application_blob methods can only be accessed via Dataface_Application.'.Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		import( 'Dataface/Table.php');
		import('Dataface/QueryTool.php');
		
		if ( strpos(@$request['-field'], '.') !== false ){
			$request = $this->_parseRelatedBlobRequest($request);
		}
			
		if ( !isset( $request['-field'] ) ) die("Could not complete request.  No field name specified.");
		if ( !isset( $request['-table'] ) ) die("Could not complete request.  No table specified.");
		$fieldname = $request['-field'];
		$tablename = $request['-table'];
		
		$table =& Dataface_Table::loadTable($tablename);
		$keys = array_keys($table->keys());
		
		
		$lastTableUpdate = $table->getUpdateTime();
		$lastTableUpdate = strtotime($lastTableUpdate);
		
		if ( $table->isContainer($fieldname) ){
			$field =& $table->getField($fieldname);
			$savepath = $field['savepath'];
			$app =& Dataface_Application::getInstance();
			$query =& $app->getQuery();
			$rec =& df_get_record($table->tablename, $query);
			if ( !$rec ) trigger_error("No record found to match the request.", E_USER_ERROR);
			foreach (array_keys($_REQUEST) as $rkey){ 
				unset($_REQUEST[$rkey]); 
				unset($_GET[$rkey]);
			}
			$_GET['phpThumbDebug']=7;
			$_REQUEST['src'] = $_GET['src'] = substr($field['savepath'], strlen(DATAFACE_SITE_PATH)).'/'.$rec->val($fieldname);
			$_REQUEST['w'] = $_GET['w'] = ( isset($query['--width']) ? $query['--width'] : (isset($field['width']) ? $field['width'] : null));
			$_REQUEST['h'] = $_GET['h'] = ( isset($query['--height']) ? $query['--height'] : (isset($field['height']) ? $field['height'] : null));
			include 'phpThumb/phpThumb.php';
			exit;
		}
		if ( !$table->isBlob($fieldname) ) die("blob.php can only be used to load BLOB or Binary columns.  The requested field '$fieldname' is not a blob");
		$field =& $table->getField($fieldname);

		if ( isset($request['-index']) ) $index = $request['-index'];
		else $index = 0;
		
		$cachePath = $this->_conf['cache_dir'].'/'.$this->_conf['_database']['name'].'-'.$tablename.'-'.$fieldname.'-'.$index.'?';
		foreach ($keys as $key){
			$cachePath .= urlencode($key).'='.urlencode($_REQUEST[$key]).'&';
		}
		
		$queryTool =& Dataface_QueryTool::loadResult($tablename, null, $request);

		// No mimetype was recorded.
		
		$files = glob($cachePath.'-*');
		$found = false;
			
		if ( is_array($files) ){
			foreach ($files as $file){
				$matches = array();
				if ( preg_match('/.*-([^\-]+)$/', $file, $matches) ){
					$time = $matches[1];
					if ( intval($time)>$lastTableUpdate){
						$found = $file;
						break;
					} else {
						@unlink($file);
					}
				}
			}
		}
		
		if ( $found !== false ){
			$contents = file_get_contents($found);
		} else {
			$columns = array($fieldname);
			
			if ( isset($field['mimetype']) and $field['mimetype']){
				$columns[] = $field['mimetype'];
			}
			if ( isset($field['filename']) and $field['filename']){
				$columns[] = $field['filename'];
			}
			$record =& $queryTool->loadCurrent($columns, true, true);
			$record->loadBlobs = true;
			$contents = $record->getValue($fieldname, $index);
			$found = $cachePath.'-'.time();
			if ( $fh = fopen($found, "w") ){
				fwrite($fh, $contents);
				fclose($fh);
			} else {
				$found = false;
			}
		}
	
		if ( !isset( $record ) ){
			$columns = array();
			if ( isset($field['mimetype']) and $field['mimetype']){
				$columns[] = $field['mimetype'];
			}
			if ( isset($field['filename']) and $field['filename']){
				$columns[] = $field['filename'];
			}

			$record =& $queryTool->loadCurrent($columns);
		}
		
		if ( isset($field['mimetype']) and $field['mimetype']){
			$mimetype = $record->getValue($field['mimetype'], $index);
		}
		if ( isset($field['filename']) and $field['filename']){
			$filename = $record->getValue($field['filename'], $index);
		}
		//$mimetype = $record->getValue($field['mimetype'], $index);
			//echo $mimetype; exit;
		 
			
		if ( (!isset($mimetype) or !$mimetype) and $found !== false ){
			
			if(!extension_loaded('fileinfo')) {
				@dl('fileinfo.' . PHP_SHLIB_SUFFIX);
			}
			if(extension_loaded('fileinfo')) {
				$res = finfo_open(FILEINFO_MIME); /* return mime type ala mimetype extension */
				$mimetype = finfo_file($found);
			} else if (function_exists('mime_content_type')) {
				
			
				$mimetype = mime_content_type($found);
				
			} else {
				trigger_error("Could not find mimetype for field '$fieldname'".Dataface_Error::printStackTrace(), E_USER_ERROR);
			}
		}
		
		if ( !isset($filename) ){
			$filename = $request['-table'].'_'.$request['-field'].'_'.date('Y_m_d_H_i_s');
		}
		//echo "here"; 	
		//echo "here: $mimetype"; 
		//echo $contents;
		//echo $mimetype; exit;
		header('Content-type: '.$mimetype);
		header('Content-disposition: attachment; filename="'.$filename.'"');
		echo $contents;
		exit;
		
			
		
	}

}
