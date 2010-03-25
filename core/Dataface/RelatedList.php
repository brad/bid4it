<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */

/*
 * 	Handles creation and display of a result list from an SQL database.
 */
 
 import( 'Dataface/Table.php');
 import( 'Dataface/QueryBuilder.php');
 import( 'Dataface/LinkTool.php');
 
 
 class Dataface_RelatedList {
 	
 	var $_tablename;
 	var $_relationship_name;
 	var $_relationship;
 	var $_db;
 	var $_table;
 	var $_record;
 	var $_start;
 	var $_limit;
 	var $_where;
 	
 	function Dataface_RelatedList( &$record, $relname,  $db=''){
 		if ( !is_a($record, 'Dataface_Record') ){
 			trigger_error("In Dataface_RelatedList constructor, the first argument is expected to be an object of type 'Dataface_Record' but received '".get_class($record)."'.\n<br>". Dataface_Error::printStackTrace());
 		}
 		$this->_record =& $record;
 		$this->_tablename = $this->_record->_table->tablename;
 		$this->_db = $db;
 		$this->_relationship_name = $relname;
 		
 		
 		$this->_table =& $this->_record->_table;
 		$this->_relationship =& $this->_table->getRelationship($relname);
 		
 		$this->_start = isset($_REQUEST['-related:start']) ? $_REQUEST['-related:start'] : 0;
 		$this->_limit = isset($_REQUEST['-related:limit']) ? $_REQUEST['-related:limit'] : 30;
 		
 		$app =& Dataface_Application::getInstance();
 		$query =& $app->getQuery();
 		if ( isset($query['-related:search']) ){
			$rwhere = array();
			foreach ($this->_relationship->fields() as $rfield){
				//list($garbage,$rfield) = explode('.', $rfield);
				$rwhere[] = '`'.str_replace('.','`.`',$rfield).'` LIKE \'%'.addslashes($query['-related:search']).'%\'';
			}
			$rwhere = implode(' OR ', $rwhere);
		} else {
			$rwhere = 0;
		}
		$this->_where = $rwhere;
 		
 		
 	}
 	
 	function _forwardButtonHtml(){
 		$numRecords = $this->_record->numRelatedRecords( $this->_relationship_name, $this->_where );
 		if ( $this->_start + $this->_limit >=  $numRecords ) return '';
 		$query = array('-related:start'=> $this->_start+$this->_limit, '-related:limit'=>$this->_limit);
 		$link = Dataface_LinkTool::buildLink($query);
 		$out = '<a href="'.$link.'" title="Next '.$this->_limit.' Results"><img src="'.DATAFACE_URL.'/images/go-next.png" alt="Next" /></a>';
 		if ( ($this->_start+(2*$this->_limit)) <  $numRecords ){
 			$query['-related:start'] = $numRecords - ( ($numRecords - $this->_start) % $this->_limit) -1;
 			$link = Dataface_LinkTool::buildLink($query);
 			$out .= '<a href="'.$link.'" title="Last"><img src="'.DATAFACE_URL.'/images/go-last.png" alt="Last" /></a>';
 		}
 		return $out;
 	}
 	
 	function _backButtonHtml(){
 		if ( $this->_start <= 0 ) return '';
 		$query = array('-related:start'=> max( 0, $this->_start-$this->_limit), '-related:limit'=>$this->_limit);
 		$link = Dataface_LinkTool::buildLink($query);
 		$out = '<a href="'.$link.'" title="Previous '.$this->_limit.' Results"><img src="'.DATAFACE_URL.'/images/go-previous.png" alt="Previous" /></a>';
 		
 		if ( ($this->_start-$this->_limit) > 0 ){
 			$query['-related:start'] = 0;
 			$out =  '<a href="'.Dataface_LinkTool::buildLink($query).'" title="First"><img src="'.DATAFACE_URL.'/images/go-first.png" alt="First" /></a>'.$out;
 		}
 		
 		return $out;
 	
 	}
 	
 	function renderCell(&$record, $fieldname){
 		$del =& $record->_table->getDelegate();
 		if ( isset($del) and method_exists($del, $fieldname.'__renderCell') ){
 			$method = $fieldname.'__renderCell';
 			return $del->$method($record);
 			//return call_user_func(array(&$del, $fieldname.'__renderCell'), $record); 
 		}
 		return null;
 	}
 		
 	
 	
 	function toHtml(){
 		
 		$app =& Dataface_Application::getInstance();
 		$query =& $app->getQuery();
 		if ( isset( $query['-related:sort']) ){
 			$sortcols = explode(',', trim($query['-related:sort']));
 			$sort_columns = array();
 			foreach ($sortcols as $sortcol){
 				$sortcol = trim($sortcol);
 				if (strlen($sortcol) === 0 ) continue;
 				$sortcol = explode(' ', $sortcol);
 				if ( count($sortcol) > 1 ){
 					$sort_columns[$sortcol[0]] = strtolower($sortcol[1]);
 				} else {
 					$sort_columns[$sortcol[0]] = 'asc';
 				}
 			}
 			unset($sortcols);	// this was just a temp array so we get rid of it here
 		} else {
 			$sort_columns = array();
 		}
 		$sort_columns_arr = array();
 		foreach ( $sort_columns as $colkey=>$colorder) {
 			$sort_columns_arr[] =  '`'.$colkey.'`'. $colorder;
 		}
 		if ( count($sort_columns_arr) > 0 ){
 			$sort_columns_str = implode(', ',$sort_columns_arr);
 		} else {
 			$sort_columns_str = 0;
 		}
 		//echo $sort_columns_str;exit;
 		
 		
		
 		unset($query);
 		
 		
 		$skinTool =& Dataface_SkinTool::getInstance();
 		$resultController =& $skinTool->getResultController();
 		$s =& $this->_table;
 		$r =& $this->_relationship->_schema;
 		$fkeys = $this->_relationship->getForeignKeyValues();
 		$default_order_column = $this->_relationship->getOrderColumn();
 		//echo "Def order col = $default_order_column";
 		ob_start();
 		df_display(array('redirectUrl'=>$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']), 'Dataface_MoveUpForm.html');
 		$moveUpForm = ob_get_contents();
 		ob_end_clean();
 		
 		
		

 		
 		$records =& $this->_record->getRelatedRecords($this->_relationship_name, true, $this->_start, $this->_limit, $this->_where);
 		
 		if ( PEAR::isError($records) ){
 			$records->addUserInfo("Error retrieving records from relationship ".$this->_relationship_name." on line ".__LINE__." of file ".__FILE__);
 			return $records;
 		}
 		
 		ob_start();
 		
 		//echo "<br/><b>Now Showing</b> ".($this->_start+1)." to ".(min($this->_start + $this->_limit, $this->_record->numRelatedRecords($this->_relationship_name)));
 		$perms = $this->_record->getPermissions(array('relationship'=>$this->_relationship_name));
 		if ( Dataface_PermissionsTool::edit($this->_record) or @$perms['add new related record'] or @$perms['add existing related record'] ){
			$query = array('-action'=>'new_related_record');
			$link = Dataface_LinkTool::buildLink($query);
			
			$domainTable = $this->_relationship->getDomainTable();
			$importTablename = $domainTable;
			if ( !PEAR::isError($domainTable) ){
				//This relationship is many-to-many so we can add existing records to it.
				$query2 = array('-action'=>'existing_related_record');
				$link2 = Dataface_LinkTool::buildLink($query2);
				
				$destTables = $this->_relationship->getDestinationTables();
				$importTablename = $destTables[0]->tablename;
			}
			if ( !PEAR::isError($importTablename) ){
				$importTable =& Dataface_Table::loadTable($importTablename);
				$query3 = array('-action'=>'import');
				$link3 = Dataface_LinkTool::buildLink($query3);
			} 
		
			echo "<div id=\"relatedActionsWrapper\" class=\"contentActions\"><ul id=\"relatedActions\">";
			if ( $this->_relationship->supportsAddNew() and @$perms['add new related record']){
				echo "<li id=\"addNew\"><a id=\"add_new_related_record\" href=\"$link\">".
					df_translate(
						'scripts.Dataface.RelatedList.toHtml.LABEL_ADD_NEW_RELATED_RECORD',
						"Add New ".ucfirst($this->_relationship_name)." Record",
						array('relationship'=>ucfirst($this->_relationship_name))
						)
					."</a></li>";
			}
			if ( $this->_relationship->supportsAddExisting() and isset($query2) and @$perms['add existing related record']){
				  echo "<li id=\"addExisting\"><a id=\"add_existing_related_record\" href=\"$link2\">".
				  df_translate(
				  	'scripts.Dataface.RelatedList.toHtml.LABEL_ADD_EXISTING_RELATED_RECORD',
				  	"Add Existing ".ucfirst($this->_relationship_name)." Record",
				  	array('relationship'=>ucfirst($this->_relationship_name))
				  	)
				  ."</a></li>";
			}
			if ( isset($query3) and  count($importTable->getImportFilters()) > 0 ){
				echo "<li id=\"import\"><a id=\"import_related_records\" href=\"$link3\">".
					df_translate(
						'scripts.Dataface.RelatedList.toHtml.LABEL_IMPORT_RELATED_RECORDS',
						"Import ".ucfirst($this->_relationship_name)." Records",
						array('relationship'=>ucfirst($this->_relationship_name))
						)
					."</a></li>";
			}
			echo "</ul></div>";
			
		}
		$out = ob_get_contents();
		ob_end_clean();		
	
		ob_start();
		$imgIcon = DATAFACE_URL.'/images/search_icon.gif';
		$searchSrc = DATAFACE_URL.'/js/Dataface/RelatedList/search.js';
		$relname = $this->_relationship_name;
		echo <<<END
		<div class="result-tools" style="float:left">
			<script language="javascript" type="text/javascript" src="$searchSrc"></script>
			<a href="#" onclick="Dataface.RelatedList.showSearch('$relname', document.getElementById('related_find_wrapper')); return false;" title="Filter these results"><img src="$imgIcon" alt="Filter" /></a>
			
		</div>
END;
		
		
		echo '<div class="result-stats">';
		$num_related_records = $this->_record->numRelatedRecords($this->_relationship_name, $this->_where);
		$now_showing_start = $this->_start+1;
		$now_showing_finish = min($this->_start + $this->_limit, $this->_record->numRelatedRecords($this->_relationship_name, $this->_where));
		
		echo df_translate(
			'scripts.Dataface.RelatedList.toHtml.MESSAGE_FOUND',
			"<b>Found</b> ".$num_related_records." Records in relationship <i>".$this->_relationship_name."</i>",
			array('num'=>$num_related_records, 'relationship'=>$this->_relationship_name)
			)
			."<br/>".
			df_translate(
				'scripts.Dataface.RelatedList.toHtml.MESSAGE_NOW_SHOWING',
				"<b>Now Showing</b> ".($now_showing_start)." to ".($now_showing_finish),
				array('start'=>$now_showing_start,'finish'=>$now_showing_finish)
				)
			."</div>
			<div class=\"limit-field\">
			";
		echo $resultController->limitField('related:');
		echo "</div>
			<div class=\"prev-link\">".$this->_backButtonHtml()."</div>
			<div class=\"next-link\">".$this->_forwardButtonHtml()."</div>
		";

		import('Dataface/ActionTool.php');
		$at =& Dataface_ActionTool::getInstance();
		$actions = $at->getActions(array(
			'category'=>'related_list_actions'
			)
		);
		echo <<<END
		<div class="result-list-actions">
		<ul class="icon-only" id="result-list-actions">
END;
		foreach ($actions as $action){
			if ( @$action['onclick'] ) $onclick = 'onclick="'.htmlspecialchars($action['onclick']).'"';
			else $onclick = '';
			
			echo <<<END
			  <li id="result-list-actions-{$action['id']}" class="plain">
			
			<a id="result-list-actions-{$action['id']}-link"href="{$action['url']}" $onclick
			   accesskey="e" title="{$action['description']}">
			   <img id="result-list-actions-{$action['id']}-icon"src="{$action['icon']}" alt="{$action['label']}"/>                   
				<span class="action-label">{$action['label']}</span>
			</a>
		  </li>
END;
		
		}
		
		
		
		echo <<<END
		</ul>
		
		</div>
END;
		

		
		$relatedResultController = ob_get_contents();
		ob_end_clean();
		
		ob_start();
		//echo '<div style="clear: both"/>';
		echo '<div class="resultlist-controller">';

		echo $relatedResultController;
		echo "</div>";
		
		if ( $this->_relationship->_schema['list']['type'] == 'treetable' ){
			import('Dataface/TreeTable.php');
			$treetable =& new Dataface_TreeTable($this->_record, $this->_relationship->getName());
			echo $treetable->toHtml();
		} else {
			echo $moveUpForm;
			if ( $this->_where ){
				
				$filterQuery =& $app->getQuery();
				echo '<div>Showing matches for query <em>&quot;'.htmlspecialchars($filterQuery['-related:search']).'&quot;</em>
				<a href="'.$app->url('-related:search=').'" title="Remove this filter to show all records in this relationship">
					<img src="'.DATAFACE_URL.'/images/delete.gif" alt="Remove filter" />
				</a>
				</div>';
			}
			echo '<div style="display:none" id="related_find_wrapper"></div>';
			if ( count($records) > 0 ){
				echo '
					<table class="listing" id="relatedList">
					<thead>
					<tr><th><input type="checkbox" onchange="toggleSelectedRows(this,\'relatedList\');"></th>
					';
				$cols = array_keys(current($records));
				
				
				
				$col_tables = array();
				$table_keys = array();
				
				$usedColumns = array();
				foreach ($cols as $key ){
					if ( $key == $default_order_column ) continue;
					if ( is_int($key) ) continue;
					if ( isset($sort_columns[$key]) ){
						$class = 'sorted-column-'.$sort_columns[$key];
						$query = array();
						$qs_columns = $sort_columns;
						unset($qs_columns[$key]);
						$sort_query = $key.' '.($sort_columns[$key] == 'desc' ? 'asc' : 'desc');
						foreach ( $qs_columns as $qcolkey=> $qcolvalue){
							$sort_query .= ', '.$qcolkey.' '.$qcolvalue;
						}
					} else {
						$class = 'unsorted-column';
						$sort_query = $key.' asc';
						foreach ( $sort_columns as $scolkey=>$scolvalue){
							$sort_query .= ', '.$scolkey.' '.$scolvalue;
						}
						
					}
					$sq = array('-related:sort'=>$sort_query);
					$link = Dataface_LinkTool::buildLink($sq);
					
					$fullpath = $this->_relationship_name.'.'.$key;
					
					$field =& $s->getField($fullpath);
					if ( isset( $this->_relationship->_schema['visibility'][$key]) and $this->_relationship->_schema['visibility'][$key] == 'hidden' ) continue;
					if ( $field['visibility']['list'] != 'visible') continue;
					if ( $s->isBlob($fullpath) or $s->isPassword($fullpath) ) continue;
					if ( PEAR::isError($field) ){
						$field->addUserInfo("Error getting field info for field $key in RelatedList::toHtml() on line ".__LINE__." of file ".__FILE__);
						return $field;
					}
					$usedColumns[] = $key;
					echo '<th><a href="'.$link.'">'.$field['widget']['label']."</a></th>\n";
					if ( !isset($col_tables[$key]) ) $col_tables[$key] = $field['tablename'];
					if (!isset($table_keys[$col_tables[$key]]) ){
						$table_table =& Dataface_Table::loadTable($field['tablename']);
						$table_keys[$col_tables[$key]] = array_keys($table_table->keys());
						unset($table_table);
					}
					unset($field);
					
					
				}
				echo "</tr>
					</thead>
					<tbody id=\"relatedList-body\">
					";
				
				$limit = min( $this->_limit, $this->_record->numRelatedRecords($this->_relationship_name, $this->_where)-$this->_start);
				$relatedTable = $this->_relationship->getDomainTable();
				if ( PEAR::isError($relatedTable) ){
					$relatedTable = reset($r['selected_tables']);
				}
				$relatedTable = Dataface_Table::loadTable($relatedTable);
				
				$relatedKeys = array_keys($relatedTable->keys());
				foreach (array_keys($relatedKeys) as $i){
					$relatedKeys[$i] = $this->_relationship_name.".".$relatedKeys[$i];
				}
				
				$fullpaths = array();
				$fields_index=array();
				foreach( $usedColumns as $key ){
					$fullpaths[$key] = $this->_relationship_name.'.'.$key;
					$fields_index[$key] =& $s->getField($fullpaths[$key]);
					
					
				}
				
				$evenRow = false;
				
				
				for ( $i=$this->_start; $i<($this->_start+$limit); $i++){
					$rowClass = $evenRow ? 'even' : 'odd';
					$evenRow = !$evenRow;
					
					if ( $default_order_column and @$perms['reorder_related_records'] ){
						$style = 'cursor:move';
						// A variable that will be used below in javascript to decide
						// whether to make the table sortable or not
						$sortable_js = 'true';
					} else {
						$style = '';
						$sortable_js = 'false';
					}
					

					unset($rrec);
					$rrec = $this->_record->getRelatedRecord($this->_relationship_name, $i,$this->_where, $sort_columns_str);//new Dataface_RelatedRecord($this->_record, $this->_relationship_name, $this->_record->getValues($fullpaths, $i, 0, $sort_columns_str));
					$rrecid = $rrec->getId();
					
					echo "<tr class=\"listing $rowClass\" style=\"$style\" id=\"row_$rrecid\">";
					echo '
					<td class="'.$rowClass.' viewableColumn" nowrap>
						<input class="rowSelectorCheckbox" id="rowSelectorCheckbox:'.$rrecid.'" type="checkbox">
					';

					
					echo '
					</td>';
					
	
					$link_queries=array();
					foreach ($usedColumns as $key){
						if ( is_int($key) ) continue;
						$fullpath = $fullpaths[$key];
						unset($field);
						$field =& $fields_index[$key];//$s->getField($fullpath);
						
						if ( isset($link_queries[$col_tables[$key]]) ){
							$query = $link_queries[$col_tables[$key]];
							$failed = false;
						} else {
							
							$query = array( "-action"=>"browse", "-relationship"=>null, "-cursor"=>0, "-table"=>$col_tables[$key]) ;
							$failed = false;
								// flag to indicate if we failed to generate appropriate link
							
							foreach ( $table_keys[$col_tables[$key]] as $table_key ){
								$query[$table_key] = "=".$this->_record->getValueAsString($this->_relationship_name.'.'.$table_key, $i, $this->_where, $sort_columns_str);
								if ( $query[$table_key] == '=' ){
									if ( isset( $fkeys[$col_tables[$key]][$table_key]) ){
										$query[$table_key] = $this->_record->parseString($fkeys[$col_tables[$key]][$table_key]);
									} else {
										$failed = true;
									}
								}
							}
							$link_queries[$col_tables[$key]] = $query;
						}
						
						if ( $failed ){
							$link = "#";
						} else {
							
							$link = Dataface_LinkTool::buildLink($query, false);
						}
						//$val = '';
						$val = $this->_record->preview($fullpath, $i,255, $this->_where, $sort_columns_str);
						$title = "";
						
						if ( $key == $default_order_column ){
							unset($field);
							continue;

						} else {
							if ($val != 'NO ACCESS'){
								$accessClass = 'viewableColumn';
							} else {
								
								$accessClass = '';
							}
							$srcRecord =& $rrec->toRecord($field['tablename']);
							$renderVal = $this->renderCell($srcRecord, $field['Field']);
							if ( isset($renderVal) ) $val = $renderVal;
							else if ( !@$field['noLinkFromListView'] ) $val = "<a href=\"$link\" title=\"". htmlspecialchars($title)."\">".$val."</a>";
							echo "<td class=\"$rowClass $accessClass\">$val</td>\n";
							unset($srcRecord);
						}
						
					}
					echo "</tr>\n";
				}
				
				echo "</tbody>
					</table>";
				
				echo  '<form id="result_list_selected_items_form" method="post">';
				$app =& Dataface_Application::getInstance();
				$q =& $app->getQuery();
				foreach ( $q as $key=>$val){
					if ( strlen($key)>1 and $key{0} == '-' and $key{1} == '-' ){
						continue;
					}
					echo '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($val).'">';
				}
				echo '<input type="hidden" name="--selected-ids" id="--selected-ids">';
				echo '<input type="hidden" name="-from" id="-from" value="'.$query['-action'].'">';
				echo '</form>';
				
				import('Dataface/ActionTool.php');
				$at =& Dataface_ActionTool::getInstance();
				$actions =& $at->getActions(array('category'=>'selected_related_result_actions'));
				if ( count($actions) > 0){
					echo '<div id="selected-actions">'.df_translate('scripts.GLOBAL.LABEL_WITH_SELECTED','With Selected').': <ul class="selectedActionsMenu" id="result_list-selectedActionsMenu">';
					foreach ($actions as $action){
						echo <<<END
						<li id="action-{$action['id']}"><a href="{$action['url']}" title="{$action['description']}">{$action['label']}</a></li>
END;
					}
				
				
					echo '</ul></div>';
				}
	
				echo '<div class="resultlist-controller">';
				echo $relatedResultController;
				echo '</div>';
					
	
					
				// This bit of javascript goes through all of the columns and removes all columns that 
				// don't have any accessible information for this query.  (i.e. any columns for which
				// each row's value is 'NO ACCESS' is removed
				$prototype_url = DATAFACE_URL.'/js/scriptaculous/lib/prototype.js';
				$scriptaculous_url =DATAFACE_URL.'/js/scriptaculous/src/scriptaculous.js';
				$thisRecordID = $this->_record->getId();
				echo <<<END
				<script language="javascript" src="$prototype_url"></script>
				<script language="javascript" src="$scriptaculous_url"></script>
				<script language="javascript"><!--
				function removeUnauthorizedColumns(){
					var relatedList = document.getElementById('relatedList');
					var trs = relatedList.getElementsByTagName('tr');
					var viewableColumns = [];
					var numCols = 0;
					for (var i=0; i<trs.length; i++){
						var tr = trs[i];
						var tds = tr.getElementsByTagName('td');
						for (var j=0; j<tds.length; j++){
							var td = tds[j];
							if ( td.className.indexOf('viewableColumn') >= 0 ){
								viewableColumns[j] = true;
							}
							numCols = j;
						}
					}
					for (var j=viewableColumns.length; j<=numCols; j++){
						viewableColumns[j] = false;
					}
					
					
					for (var i=0; i<trs.length; i++){
						var tds = trs[i].getElementsByTagName('td');
						if ( tds.length <= 0 ){
							var tds = trs[i].getElementsByTagName('th');
						}
						
						for (var j=0; j<viewableColumns.length; j++){
							if ( !viewableColumns[j] ){
								tds[j].style.display = 'none';
							}
						}
						
					}
				}
				removeUnauthorizedColumns();
				
				
				if ( $sortable_js ){
					Sortable.create("relatedList-body",
							{
								dropOnEmpty:true,
								constraint:false, 
								//handle:'move-handle',
								tag:'tr',
								onUpdate: function(container){
									
									var params = Sortable.serialize('relatedList-body');
									params += '&'+window.location.search.substring(1);
									
									params += '&-action=reorder_related_records';//&--recordid='+escape('$thisRecordID');
									
									new Ajax.Request(
										DATAFACE_SITE_HREF, {
											method: 'post', 
											parameters: params, 
											onSuccess: function(transport){
											    
												//document.getElementById('details-controller').innerHTML = transport.responseText;
											},
											onFailure:function(){
												alert('Failed to sort records.');
											}
										}
									);
									
								}
								//only:'movable'
							});
						//Sortable.create("dataface-sections-main",
						//{dropOnEmpty:true,constraint:false, handle:'movable-handle',tag:'div',only:'movable', onUpdate:updateSections});
				}	
				
				//--></script>
				
END;
				
			
			} else {
			
				echo "<p>".df_translate('scripts.GLOBAL.NO_RECORDS_MATCHED_REQUEST','No records matched your request.')."</p>";
			}

			
				
		}
		$out .= ob_get_contents();
		ob_end_clean();
		
 		
 		return $out;
 	}
 	
 	
 
 }
 
