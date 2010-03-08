<?php
/* Wrapper to use the DynArch jscalendar widget. */

require_once 'HTML/QuickForm/text.php';
require_once 'jscalendar/calendar.php';

$GLOBALS['HTML_QuickForm_calendar'] = array(
	'jscalendar_BasePath' 		=> ( isset($GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath']) ? $GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath'] : './lib/jscalendar'));


/**
 * HTML Class for a calendar widget.
 * @author       Josef Nankivell
 * @version      0.1.0
 * @access       public
 */
class HTML_QuickForm_calendar extends HTML_QuickForm_text {
	
	var $_basePath = '.';
	var $calendar;
	function HTML_QuickForm_calendar($elementName=null, $elementLabel=null, $attributes=null, $properties=null)
    {

        parent::HTML_QuickForm_input($elementName, $elementLabel, $attributes);
        $this->_type = 'calendar';
        
        // English is the default language
        if ( !@$properties['lang'] ) $properties['lang'] = 'en';
        
        // Default theme (without .css file extension)
        if ( !@$properties['theme'] ) $properties['theme'] = 'calendar-win2k-2';
        
        // User stripped javascript files for faster speed
        if ( !isset($properties['stripped']) ) $properties['stripped'] = false;
        
        // First day of the week (Monday = 1)
        if (!isset($properties['firstDay'])) $properties['firstDay'] = 1;
        
        // Show the time
        if ( !isset($properties['showsTime']) ) $properties['showsTime'] = true;
        
        // "showOthers" for jscalendar
        if ( !isset($properties['showOthers']) ) $properties['showOthers'] = true;
        
        // Date format
        if ( !isset( $properties['ifFormat']) ) $properties['ifFormat'] = '%Y-%m-%d %I:%M %P';
        
        // Time format
        if ( !isset( $properties['timeFormat']) ) $properties['timeFormat'] = '12';
        
        
        foreach (array_keys($properties) as $key){
        	$this->setProperty($key, $properties[$key]);
        }
        
        $this->calendar = new DHTML_Calendar($GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath'], 
        						$this->getProperty('lang'), 
        						$this->getProperty('theme'), 
        						$this->getProperty('stripped')
        						);
    } //end constructor
    
    
    
    /**
     * Returns textarea in HTML
     * @access    public
     * @return    string
     */
    function toHtml()
    {
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
        
        	if ( !defined('HTML_QuickForm_calendar_js_loaded') ){
        		// If the js files have not loaded, load them.
        		define('HTML_QuickForm_calendar_js_loaded', true);
        		$this->calendar->load_files();
        	}
        	
        	$properties = $this->getProperties();
        	$attributes = $this->getAttributes();
        	ob_start();
        	$this->calendar->make_input_field(
		    // options
		    $properties,
		    // field attributes
		    $attributes);
		    $out = ob_get_contents();
		    ob_end_clean();
           
        	return $out;
        }
        	
        
    } //end
    
    
    function getFrozenHtml(){
    	return $this->getValue();
    }
    
    
	
	

}
