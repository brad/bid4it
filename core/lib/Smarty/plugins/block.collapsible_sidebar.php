<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {textformat}{/textformat} block plugin
 *
 * Type:     block function<br>
 * Name:     textformat<br>
 * Purpose:  format text a certain way with preset styles
 *           or custom wrap/indent settings<br>
 * @link http://smarty.php.net/manual/en/language.function.textformat.php {textformat}
 *       (Smarty online manual)
 * @param array
 * <pre>
 * Params:   style: string (email)
 *           indent: integer (0)
 *           wrap: integer (80)
 *           wrap_char string ("\n")
 *           indent_char: string (" ")
 *           wrap_boundary: boolean (true)
 * </pre>
 * @param string contents of the block
 * @param Smarty clever simulation of a method
 * @return string string $content re-formatted
 */
function smarty_block_collapsible_sidebar($params, $content, &$smarty)
{
   static $sidebar_index = 0;
   if (is_null($content)) {
        return;
    }
    
    
    $treeExpandedURL = df_absolute_url(DATAFACE_URL.'/images/treeExpanded.gif');
    $treeCollapsedURL = df_absolute_url(DATAFACE_URL.'/images/treeCollapsed.gif');
    
    if (isset($params['javascript_path']) ){
    	$jspath = $params['javascript_path'];
    } else if ( defined('DATAFACE_URL') ){
    	$jspath = DATAFACE_URL.'/js/scriptaculous';
    } else {
    	$jspath = '';
    }
    $jspath = df_absolute_url($jspath);
    
    if ( isset($params['prototype_path']) ){
    	$prototype_path = $params['prototype_path'];
    } else if ( defined('DATAFACE_URL') ){
    	$prototype_path = $jspath.'/lib/prototype.js';
    } else {
    	$prototype_path = $jspath.'/prototype.js';
    }
    
    if ( isset($params['scriptaculous_path']) ){
    	$scriptaculous_path = $params['scriptaculous_path'];
    } else if (defined('DATAFACE_URL') ){
    	$scriptaculous_path = $jspath.'/src/scriptaculous.js';
    } else {
    	$scriptaculous_path = $jspath.'/scriptaculous.js';
    }
    
    if ( !isset($params['heading']) ){
    	$heading = '';
    	
    } else {
    	$heading = $params['heading'];
    }
    
    if ( !isset($params['class']) ){
    	$clazz = $class = 'Dataface_collapsible_sidebar';
    } else {
    	$clazz = $class = $params['class'];
    }
    
    if ( isset($params['onexpand']) ){
    	$onexpand = $params['onexpand'];	
    } else {
    	$onexpand = '';
    }
    
    if ( isset($params['oncollapse']) ){
    	$oncollapse = $params['oncollapse'];
    } else {
    	$oncollapse = '';
    }
    
   
    
    if ( isset($params['id']) ) $section_name = $id = $params['id'];
    else $id = null;
    
    if ( isset($params['prefix'])  and isset($id) ){
    	$id = $params['prefix'].'_'.$id.'_'.($sidebar_index++);
    } else if ( isset($params['prefix'])){
    	$id = $params['prefix'].'_'.($sidebar_index++);
    } else {
    	$id = rand().'_'.($sidebar_index++);
    }
    
    
    $out = '';
    if ( !defined('SMARTY_BLOCK_COLLAPSIBLE_SIDEBAR_JS') ){
    	define('SMARTY_BLOCK_COLLAPSIBLE_SIDEBAR_JS',1);
    	
    	$js = <<< END
			<script type='text/javascript' src='$prototype_path'></script>
			<script type='text/javascript' src='$scriptaculous_path'></script>
			<script type='text/javascript' language='javascript'><!--
/**
 * Example adapted from http://www.dustindiaz.com/collapsable-effects-with-scriptaculous/
 */
		var cd = {
	codes : Array,
	init : function() {
		cd.codes = document.getElementsByClassName('expansion-handle','contentBody');
		cd.attach();
	},
	attach : function() {
		var i;
		for ( i=0;i<cd.codes.length;i++ ) {
			Event.observe(cd.codes[i],'click',cd.collapse,false);
			Element.cleanWhitespace(cd.codes[i].parentNode);
		}
	},
	getEventSrc : function (e) {
		if (!e) e = window.event;
		if (e.originalTarget)
			return e.originalTarget;
		else if (e.srcElement)
		return e.srcElement;
	},
	
	collapse : function(e){
		cd.collapseElement(cd.getEventSrc(e));
	},
	collapseElement : function(orig){
		var img = orig;
		orig = orig.parentNode;
		var el = orig.nextSibling;
		
		if ( Element.hasClassName(el,'closed') ) {
			new Effect.Parallel(
				[
					new Effect.SlideDown(el,{sync:true}),
					new Effect.Appear(el,{sync:true})
				],
				{
					duration:1.0,
					fps:40
				}
			);
			Element.removeClassName(el,'closed');
			if ( Element.hasClassName(orig,'$class-closed') ){
				Element.removeClassName(orig, '$class-closed');
				img.setAttribute('src', '$treeExpandedURL');
			}
			
			var expandCallback = el.parentNode.getAttribute('onexpand');
			el.parentNode.onexpand = function(){ eval(expandCallback);};
			el.parentNode.onexpand();
			
		} else {
			new Effect.Parallel(
				[
					new Effect.SlideUp(el,{sync:true}),
					new Effect.Fade(el,{sync:true})
				],
				{
					duration:1.0,
					fps:40
				}
			);
			Element.addClassName(el,'closed');
			Element.addClassName(orig, '$class-closed');
			img.setAttribute('src', '$treeCollapsedURL');
			
			var collapseCallback = el.parentNode.getAttribute('oncollapse');
			
			el.parentNode.oncollapse = function(){eval(collapseCallback);};
			el.parentNode.oncollapse();
			
		}
	}
};
Event.observe(window,'load',cd.init,false);
//--></script>
END;

		if ( class_exists('Dataface_Application') ){
			$app =& Dataface_Application::getInstance();
			$app->addHeadContent($js);

		} else {
			$out .= $js;
		}
    } 
    $links = '';
    
    
    if (isset($params['see_all']) ){
    	$links .= '<a href="'.$params['see_all'].'">see all</a>';
    }
    if ( !@empty($params['edit_url']) ){
    	$links .= '<a href="'.$params['edit_url'].'">edit</a>';
    }
    
    if (@$params['display'] == 'collapsed' ){
    	$expandImage = $treeCollapsedURL;
    } else {
    	$expandImage = $treeExpandedURL;
    }
    
    $expansionImage = "<img src=\"$expandImage\" style=\"cursor: pointer\" class=\"expansion-handle\"/ alt=\"Click to minimize this section\"> ";
    
    if ( isset($section_name) ){
    	$section_name = 'df:section_name="'.htmlspecialchars($section_name).'"';
    }
    
    if ( isset($params['movable']) ) {
    	$class .= ' movable-handle';
    	$out .= '<div class="movable" id="'.htmlspecialchars($id).'" '.$section_name.' oncollapse="'.htmlspecialchars($oncollapse).'" onexpand="'.htmlspecialchars($onexpand).'">';
    }
    
    if ( @$params['display'] == 'collapsed' ){
    	$class .= " $clazz-closed";
    }
    
    if ( @$params['hide_heading'] ){
    	$headingstyle = 'display: none';
    } else {
    	$headingstyle = '';
    }
    
    $out .= "<h3 class=\"$class\" style=\"padding-left:0; width:100%; $headingstyle\">$links"."$expansionImage $heading</h3>";
    if ( @$params['display'] == 'collapsed' ){
    	$style = 'style="display:none"';
    	$class = 'class="closed"';
    } else {
    	$style = '';
    	$class = '';
    }
    $out .= "<div $class $style>$content</div>";
    if ( isset($params['movable']) ) $out .= '</div>';

    return $out;
    

}

/* vim: set expandtab: */

?>
