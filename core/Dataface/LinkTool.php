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
import( 'Dataface/Application.php');
import( 'Dataface/Table.php');


/**
 * Generates links that will maintain parameters from previous requests.
 *
 */
class Dataface_LinkTool {

	function &getMask(){
		static $mask = -1;

		if ( $mask == -1 ){
			$app =& Dataface_Application::getInstance();
			$query =& $app->getQuery();
			$table =& Dataface_Table::loadTable($query['-table']);
			$mask = $_GET;
			//echo "GET: "; print_r($_GET);
			foreach ( $query as $key=>$value){
				//if ( strpos($key,'--')!== 0 ){
				if ( isset($table->_fields[$key]) or ($key{0} == '-' and $key != '-new')){
					//echo "Key $key";
					$mask[$key] = $value;
					
				}
			}
			//print_r($mask);
		}
	
		return $mask;
	}

	/**
	 * Builds a link based on the given query.
	 * @param Associative array of the query.  (also accepts a query string e.g.: 'Name=Steve&LastName=Hannah').
	 * @param useContext If true, this query will use the existing REQUEST parameters as a base.
	 */
	function buildLink($query, $useContext=true, $forceContext=false){
		$app =& Dataface_Application::getInstance();
		$appQuery =& $app->getQuery();
		
		if ( is_string($query) ){
			$terms = explode('&', $query);
			$query = array();
			foreach ( $terms as $term){
				$key = urldecode(substr($term, 0, strpos($term,'=')));
				$value = urldecode(substr($term, strpos($term,'=')+1));
				if ( strlen($value) == 0 ){
					$query[$key] = null;
				} else {
					$query[$key] = $value;
				}
			}
		
		}
		
		if ( !isset($query['-table']) ) $query['-table'] = $appQuery['-table'];
		
		if ( !$forceContext and $useContext ){
			// We check if the query parameters have changed.  If they have, then it doesn't
			// make a whole lot of sense to maintain context.
			foreach ( $query as $key=>$val) {
				if ( !$key ) continue;
				if ( $key{0} != '-' and $query[$key] != @$appQuery[$key] ){
					$useContext = false;
					break;
				}
			}
		}
		
		if ( $useContext){
			$request = Dataface_LinkTool::getMask();
			
			if ( isset( $query['-relationship'] ) ){
				if ( $query['-relationship'] != @$appQuery['-relationship'] ){
					foreach ( $request as $qkey=>$qval ){
						if ( strstr($qkey, '-related:') == $qkey ) unset($request[$qkey]);
					}
				}
			}
			
			if ( isset($request['-sort']) and $request['-table'] != $appQuery['-table'] ){
				unset($request['-sort']);
			}
			
			//print_r($query);
			$query = array_merge($request, $query);
		}
		
		if ( !isset($query['-search']) ) $query['-search'] = null;
		if ( isset( $_REQUEST['-search'] ) and strlen($_REQUEST['-search'])>0 and $query['-search'] !== null  ){
			$query['-search'] = $_REQUEST['-search'];
		}
		
		foreach ($query as $key=>$value) {
			if ( $value === null || strpos($key, '--') === 0 ){
				unset($query[$key]);
			}
		}
		
		$str = '';
		foreach ($query as $key=>$value) {
			
			if ( is_array($value) ){
				
				foreach ( $value as $vkey=>$vval ){
					$str .= urlencode($key.'['.$vkey.']').'='.urlencode($vval).'&';
				}
			}
			else {
				$str .= urlencode($key).'='.urlencode($value).'&';
			}
   		}
   		$str = substr($str,0, strlen($str)-1);
   
   		
   		$url = DATAFACE_SITE_HREF;
   		if ( strpos('?', $url) !== false ){
   			$url .= '&'.$str;
   		} else {
   			$url .= '?'.$str;
   		}

   		$url = $app->filterUrl($url);
   		return $_SERVER['HOST_URI'].$url;
   	}
   	
   	
   	function &getInstance(){
   		static $instance = 0;
   		if ( !$instance ){
   			$instance = new Dataface_LinkTool();
   		}
   		return $instance;
   	}

}
