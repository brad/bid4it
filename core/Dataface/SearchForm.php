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
 * 	An extension of HTML_QuickForm to auto-generate a form for a particular table
 * 	in an SQL database.
 */
 
require_once 'HTML/QuickForm.php';
require_once 'Dataface/Table.php';
require_once 'Dataface/Vocabulary.php';
require_once 'Dataface/QueryBuilder.php';
require_once 'Dataface/ResultController.php';
require_once 'Dataface/ResultList.php';
require_once 'Dataface/QueryTool.php';


// Register our special types
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['htmlarea'] = array('HTML/QuickForm/htmlarea.php', 'HTML_QuickForm_htmlarea');

       
class Dataface_SearchForm extends HTML_QuickForm {

	var $tablename;
	
	var $db;
	
	var $_iniFile;
	
	var $_query;
	
	var $_exactMatches = false;
	
	var $_table;
	
	var $_resultSet;

	
	
	/**
	 * Some columns may require some special loading mechanisms.  This is an 
	 * associative array of columns => callbacks to load the column.
	 */
	var $_fields = array();
	
	var $_isBuilt = false;
	
	function Dataface_SearchForm($tablename, $db='',  $query='', $fields=null){
		$widgetTypes = array();
		$this->tablename = $tablename;
		$this->db = $db;
		$this->_query = is_array($query) ? $query : array();
		
		if ( !isset( $this->_query['-cursor'] ) ){
			$this->_query['-cursor'] = 0;
		}
		
		$this->_resultSet =& Dataface_QueryTool::loadResult($tablename, $db, $this->_query);
		
		
		parent::HTML_QuickForm($tablename, 'post');
		
		// Get column information directly from the database
		
		
		$this->tablename = ereg_replace(' ', '', $this->tablename);
		$this->_table =& Dataface_Table::loadTable($this->tablename, $this->db);
		
		$this->_fields = array();
		if ( !isset($fields) ){
			$fields = array_keys($this->_table->fields(false,true));
			
			foreach ($this->_table->relationships() as $relationship){
				$rfields = $relationship->fields(true);
				$fkeys = $relationship->getForeignKeyValues();
				$removedKeys = array();
				foreach($fkeys as $fkeyTable => $fkey){
					foreach (array_keys($fkey) as $fkeyKey){
						$removedKeys[] = $fkeyTable.'.'.$fkeyKey;
					}
				}

				$rfields = array_diff($rfields, $removedKeys);

				foreach ($rfields as $rfield){
					list($rtable,$rfield) = explode('.',$rfield);
					$fields[] = $relationship->getName().'.'.$rfield;
				}
				unset($rfields);
				unset($relationship);
				
			}
		}

		$this->_fields = array();
		foreach ($fields as $fieldname){
			$this->_fields[$fieldname] =& $this->_table->getField($fieldname);
		}
		
		
		
		

		
		
		
		

	}
	
	
	
	
	
	
	
	
	function _build(){
		if ( $this->_isBuilt ){
			return;
		}
		$this->_isBuilt = true;
		
		$renderer =& $this->defaultRenderer();
		foreach ($_REQUEST as $qkey=>$qval){
			if ( strlen($qkey)>1 and $qkey{0} == '-' and strpos($qkey, '-findq:') !== 0){
				$this->addElement('hidden', $qkey);
				$this->setDefaults( array($qkey=>$qval));
			}
		}
		
		$this->addElement('hidden', '--find-submit');
		$this->setConstants( array('--find-submit'=>1));
		
		$relatedSections=array(); // keeps track of which relationship sections have been started
		
		foreach ( $this->_fields as $name => $field ){
			$table =& $this->_table;
			if ( $this->_table->isPassword($name) ) continue;
			if ( @$field['visibility']['find'] == 'hidden') continue;
			// add the field to the form
			$widget = $field['widget'];
			if ( isset($widget['find']) ){
				$widget = $widget['find'];
			}
			$vocabulary = $field['vocabulary'];
			
			if ( $widget['type'] == 'meta' ) continue;
			
			$inputName = $field['name'];
			
			if ( strpos($name,'.') !== false ){
				unset($table);

				$table =& Dataface_Table::loadTable($field['tablename']);
				list($relationshipName,$name) = explode('.', $name);
				$inputName = $relationshipName.'/'.$name;
				
				if ( !isset($relatedSections[$relationshipName]) ){
					$this->addElement('submit', '--submit', 'Submit');
					$this->addElement('header',$relationshipName,$relationshipName);
					$relatedSections[$relationshipName] = true;
				}
			}
			
			if ( isset( $vocabulary) && $vocabulary ){
				//$vocab =& Dataface_Vocabulary::getVocabulary($vocabulary);
				//$options = $vocab->options();
				$options = $table->getValuelist($field['vocabulary']);
				if ( is_array($options) ){
					$opts = array(''=>"Please Select...");
					foreach ($options as $key=>$value){
						$opts[$key] = $value;
					}
					$options = $opts;
				} 
			}
			
			
			if ( isset($field['vocabulary']) and $field['vocabulary'] ){
				$options = $table->getValuelist($field['vocabulary']);
				$boxes = array();
				
				$el =& $this->addElement('select', '-findq:'.$inputName, $widget['label'], $options, array('size'=>'5','multiple'=>1));
				$widgetTypes[$inputName] = 'select';
				$el->setFieldDef($field);
				if ( isset($field['repeat']) and $field['repeat']){
					
					$this->addElement('radio', '-find-op:'.$inputName, '','Match all selected', 'AND');
				}
				
				$this->addElement('radio', '-find-op:'.$inputName,'','Match any selected', 'OR');
				
				$this->addElement('radio', '-find-op:'.$inputName,'','Do not match selected', 'None');
			

			} else {
				
				$el =& $this->addElement('text', '-findq:'.$inputName, $widget['label'], array('class'=>$widget['class'], 'id'=>$inputName) );
				$widgetTypes[$inputName] = 'text';
				$el->setFieldDef($field);
			}
	
		}
		

		$this->addElement('submit','--submit','Find');
		$this->addElement('hidden', '-action');
		$this->addElement('hidden', '-edit');
		$this->addElement('hidden', '-table');
		
		$defaults = array();
		foreach ($this->_query as $key=>$value){
			if ( $key{0} != '-' ){
				if ( @$widgetTypes[$key] == 'select'){
					$parts = explode(' OR ', $value);
					$value = array();
					foreach ($parts as $part ){
						while ( $part and in_array($part{0}, array('=','<','>','!') ) ) {
							$part = substr($part,1);
							//$value = array($value);
						}
						$value[] = $part;
					}
				}
				
				$defaults['-findq:'.$key] = $value;
			} else {
				$defaults[$key] = $value;
			}
		}
		
		$this->setDefaults( $defaults);
		$this->setConstants(array('-action'=>'find', '-edit'=>1, '-table'=>$this->tablename));
		
		
		
	}
	
	function display(){
		$this->_build();
		
		echo '
		  <div id="search-instructions" style="float: right; width: 250px; color: #444444; font-size: 80%; background-color: white; border: 1px solid #ccc; padding: 1em;">
			<h5>Search Instructions</h5>
			<dl>
				<dt>
					Simple Searches
				</dt>
				<dd>
					Enter the values into the appropriate fields and click the "Find" button to find records the contain the specified patterns.
					<br/> e.g., If you enter &quot;Dog&quot; in one of the fields, it will match records that contain the phrase &quot;Dog&quot;.  This includes &quot;madog&quot; &quot;doggy;&quot; etc...
				</dd>
				<dt>Booean searches</dt>
				<dd>If you enter criteria into more than one field, they will match only those records that match BOTH criteria.</dd>
				<dt>Exact Matches</dt>
				
				<dd>Prepend an equals sign (quot;=quot;) to any criteria to force exact matching.<br/>
				e.g., Searching for &quot; =dog &quot; in a field  will match records where that field contains &quot;dog&quot; (and not &quot;doggy&quot;)
				</dd>
				<dt>Less Than / Greater Than Searches</dt>
				<dd>Prepending a less than (&quot;&lt;&quot;) or greater than (&quot;&gt;&quot;) sign to a  will match records where the field has a value LESS THAN (respectively GREATER THAN) the specified value.
				<br/>e.g., Entering &quot;&gt;200&quot; in the Price field will match records with price greater than 200. </dd>
				<dt>Range Searches</dt>
				<dd>To match records containing values in a range, use &quot;&lt;LowerRange&gt; .. &lt;UpperRange&gt;&quot; where &lt;LowerRange&gt; is the lower bound on matches and &lt;UpperRange&gt; is the upper bound on matches.
				<br/>e.g., To find records where Price is between 200 and 500 enter &quot;200..500&quot; in the Price field.</dd>
			</dl>
		  </div>
			';
		//parent::display();
		import('Dataface/FormTool.php');
		$ft =& Dataface_FormTool::getInstance();
		$ft->display($this, 'Dataface_FindForm.html');
	}
	
	
	/**
	 * Converts the -find* GET parameters into something that is usable by the application and forwards to the appropriate page.
	 */
	function performFind($values){
		$app =& Dataface_Application::getInstance();
		$query = $app->getQuery();

		//exit;
		
		if ( isset( $values['-find:result_action']) ){
			$qstr = '-action='.$values['-find:result_action'];
		} else {
			$qstr = '-action=list';
		}
		if ( isset($values['-skip']) ) $values['-skip'] = 0;
		if ( isset($values['-cursor']) ) $values['-cursor'] = 0;
		// Checkbox groups with nothing selected may not be submitted with the form, 
		// even though their accompanying 'None' radio button may be selected.  If none
		// is selected, then we need to add a value
		foreach ($values as $key=>$value){
			if ( strpos($key, '-find-op:') === 0 ){
				$key = substr($key, 9);
				if ( !isset($values['-findq:'.$key]) or !is_array($values['-findq:'.$key]) ){
					$values['-findq:'.$key] = array('');
				}
			}
		}
		foreach ($values as $key=>$value){
			if ( strpos($key, '-findq:') === 0 ){
				$key = substr($key, 7);
				$field = $this->_table->getField(str_replace('/','.',$key));
				if ( PEAR::isError($field) ){
					echo "Failed to get field $key: ".$field->getMessage();
				}
				if ( is_array($value) and count($value) > 0){
					$op = ( (isset( $values['-find-op:'.$key] ) ) ? $values['-find-op:'.$key] : 'AND');
					if (!isset($field['repeat']) or !$field['repeat']) $op = 'OR';
					if ( isset($values['-find-op:'.$key]) and  $values['-find-op:'.$key] == 'None' ){
						$qstr .= '&'.urlencode($key).'='.urlencode('=');
					} else {
						$qstr .= '&'.urlencode($key).'='.urlencode('='.implode( ' '.$op.' =', $value));
					} 
				} else if ( !empty($value) ){
				
					$qstr .= '&'.urlencode($key).'='.urlencode($value);
				}
				unset($field);
			} else if ( $key{0} == '-' and $key{1} != '-' and $key != '-action' and key != '-search' and strpos($key, '-find') !== 0 ){
				$qstr .= '&'.urlencode($key).'='.urlencode($value);
			}
			
		} 
		
		$url = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?'.$qstr;
		header('Location: '.$url);
		exit;
	}
	
	function process(){
		if ( isset( $this->_query['--find-submit']) ){
			return parent::process( array(&$this, 'performFind'));
		} else {
			return null;
		}
	}
	
	function getKeys(){
		$keys = array();
		foreach ($this->_fields as $key=>$value){
			if ( strtolower($value['Key']) == strtolower('PRI') ){
				$keys[$key] =& $this->_fields[$key];
			}
		}
		return $keys;
	}
	
	function deserialize($field){
		return Dataface_Table::_deserialize($field);
		
					
	}
	
	function serialize($field){
	
		return Dataface_Table::_serialize($field);
		
		
		
	}
}
