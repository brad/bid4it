<?php
class JSON {

	function notice($msg){
		return JSON::json(array('notice'=>$msg, 'success'=>0, 'msg'=>$msg));
	}
	
	function warning($msg){
		return JSON::json(array('warning'=>$msg, 'success'=>0, 'msg'=>$msg));
	}
	
	function error($msg){
		return JSON::json(array('error'=>$msg, 'success'=>0, 'msg'=>$msg));
	}
	
	function json($arr){
		import('Services/JSON.php');
		$json = new Services_JSON();
		return $json->encode($arr);
		/*
		if ( is_array($arr) ){
			$out = array();
			foreach ( $arr as $key=>$val){
				$out[] = "'".addslashes($key)."': ".JSON::json($val);
			}
			return "{".implode(', ', $out)."}";
		} else if ( is_int($arr) || is_float($arr) ){
			return $arr;
		} else if ( is_bool($arr) ){
			return ( $arr?'1':'0');
		} else {
			return "'".addslashes($arr)."'";
		}
		*/
	}
}
