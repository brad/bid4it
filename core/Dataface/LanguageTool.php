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
class Dataface_LanguageTool {
	var $dictionary;
	var $app;
	function Dataface_LanguageTool($conf=null){
		$this->_loadLangINIFile();
		$this->app =& Dataface_Application::getInstance();
		
	}
	
	function &getInstance(){
		static $instance = 0;
		if ( !$instance ){
			$instance = new Dataface_LanguageTool();
		}
		return $instance;
	}
	
	function _loadLangINIFile(/*$path*/){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		import('Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$dictionary = $configTool->loadConfig('lang', null);
		if ( isset($query['-table']) ) {
			$tableDictionary = $configTool->loadConfig('lang', $query['-table']);
			if (is_array($tableDictionary) ){
				$dictionary = array_merge($dictionary, $configTool->loadConfig('lang',$query['-table']));
			}
		}
		$this->dictionary =& $dictionary;

		
	}
	
	function translate($__translation_id, $__defaultText=null, $params=array()){
		if ( isset($this) and is_a($this, 'Dataface_LanguageTool') ) $tool =& $this;
		else $tool =& Dataface_LanguageTool::getInstance();
		if ( isset($tool->dictionary[$__translation_id]) ) {
			// make sure that there are no conflicting variable names as we are about to extract the params 
			// array into local scope.
			if ( isset($params['__translation_id']) ) unset($params['__translation_id']);
			if ( isset($params['tool']) ) unset($params['tool']);
			if (isset($params['__defaultText']) ) unset($params['__defaultText']);
			if ( isset($params['params'])) unset($params['params']);
			
			extract($params);
			@eval('$parsed = "'.$tool->dictionary[$__translation_id].'";');
			if ( !isset($parsed) ){
				return  $__defaultText;
			}
			return $parsed;
		}
		return $__defaultText;
	}
	
	/**
	 * Returns the HTML for a language selector.  This can be a list of flags, or
	 * names of languages, or a select list of names of languages.
	 *
	 * @param $params An associative array of parameters for this method.
	 *		Keys:
	 *			name : The name of the select widget or id of the ul (if unordered list)
	 *			var  : The GET variable that will be set by selecting one of these languages.
	 *			selected : The code of the language that is considered to be currently selected.
	 *			autosubmit : Whether the select list should auto submit
	 *			type	   : 'select' or 'ul'
	 *			lang	   : language code override
	 *			use_flags  : default true.
	 */
	function getLanguageSelectorHTML($params=array()){
		if ( !isset($params['use_flags']) ) $params['use_flags'] = true;
		import('I18Nv2/Language.php');
		$langcode = ( isset($params['lang']) ? $params['lang'] : $this->app->_conf['lang']);
		$languageCodes = new I18Nv2_Language($langcode);
		$currentLanguage = $languageCodes->getName( $this->app->_conf['lang']);
		$name = (isset($params['name']) ? $params['name'] : 'language');
		$options = array();
		$var = (isset($params['var']) ? $params['var'] : '-lang');
		$selected = (isset($params['selected']) ? $params['selected'] : $this->app->_conf['lang']);
		$selectedValue = $languageCodes->getName($selected);
		$autosubmit = isset($params['autosubmit']) and $params['autosubmit'];
		$type = ( isset($params['type']) ? $params['type'] : 'select');
		
		if ( isset($params['table']) ){
			$table =& Dataface_Table::loadTable($params['table']);
			$languages = array_keys($table->getTranslations());
		} else {
			$languages = $this->app->_conf['languages'];
		}
		if ( !is_array($languages) ) return '';
		
		if ( $autosubmit) {
			$onchange = 'javascript:window.location=this.options[this.selectedIndex].value;';
			foreach ( $languages as $lang ){
				if ( !isset($params['lang']) and $this->app->_conf['language_labels'][$lang] != $lang ){
					$langname = $this->app->_conf['language_labels'][$lang];
				} else {
					$langname = $languageCodes->getName($lang);
				}
				$options[$this->app->url($var.'='.$lang)] = $langname;
			}
		} else {
			$onchange = '';
			foreach ($languages as $lang ){
				if ( !isset($params['lang']) and $this->app->_conf['language_labels'][$lang] != $lang ){
					$langname = $this->app->_conf['language_labels'][$lang];
				} else {
					$langname = $languageCodes->getName($lang);
				}
				$options[$lang] = $langname;
			}
		}
		//echo 'here'; print_r($options);
		if (count($options) <= 1) return '';
		ob_start();
		if ( $type == 'select' ){
		
			echo '<select name="'.$name.'" '.($onchange ? 'onchange="'.$onchange.'"' : '').'>
			';
			foreach ($options as $code => $value ){
				echo '<option value="'.$code.'"'. ( ($selectedValue == $value) ? ' selected' : '').'>'.$value.'</option>
				';
			}
			echo '</select>';
		} else {
			echo '<ul id="'.$name.'" class="language-selection-list">
			';
			foreach ( $languages as $code  ){
				if ( !isset($params['lang']) and $this->app->_conf['language_labels'][$code] != $code ){
					$languageName = $this->app->_conf['language_labels'][$code];
				} else {
					$languageName = $languageCodes->getName($code);
				}
				//$languageName = $languageCodes->getName($code);
				echo '<li class="language-selection-item '.( ($code == $this->app->_conf['lang']) ? ' selected-language' : '').'">
				<a href="'.$this->app->url($var.'='.$code).'">';
				if ( $params['use_flags'] ){
					echo '<img src="'.DATAFACE_URL.'/images/flags/'.$code.'_small.gif" alt="'.$languageName.'" />';
				} else {
					echo $languageName;
				}
				echo '</a></li>';
			}
			echo "</ul>";
		}
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
		
	
	}
	
	
}
