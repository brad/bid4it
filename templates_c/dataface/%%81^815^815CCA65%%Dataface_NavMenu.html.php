<?php /* Smarty version 2.6.18, created on 2009-09-29 15:07:50
         compiled from Dataface_NavMenu.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'block', 'Dataface_NavMenu.html', 23, false),array('block', 'define_slot', 'Dataface_NavMenu.html', 24, false),)), $this); ?>
<?php if ($this->_tpl_vars['ENV']['prefs']['horizontal_tables_menu']): ?>

<ul id="table_selection_tabs">
	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'tables_menu_head'), $this);?>

	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'tables_menu_options')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
	<?php $_from = $this->_tpl_vars['ENV']['APPLICATION']['_tables']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['table'] => $this->_tpl_vars['label']):
?>
	<li <?php if ($this->_tpl_vars['ENV']['table'] == $this->_tpl_vars['table']): ?>class="selected"<?php endif; ?>><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-table=<?php echo $this->_tpl_vars['table']; ?>
"
                           accesskey="accesskeys-navigation"
                           class="table-selection-tab <?php if ($this->_tpl_vars['ENV']['table'] == $this->_tpl_vars['table']): ?>selected<?php endif; ?>"
                           title="<?php echo $this->_tpl_vars['label']; ?>
"
                           id="TableLink_<?php echo $this->_tpl_vars['table']; ?>
">
                            
                            
                                  <?php echo $this->_tpl_vars['label']; ?>

                            
                        </a></li>
 	<?php endforeach; endif; unset($_from); ?>
 	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
 	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'tables_menu_tail'), $this);?>

 </ul>

<?php else: ?>
<div class="portlet" id="portlet-navigation-tree">
    <div>
        <h5>Navigation</h5>
        <div class="portletBody">
        <?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'tables_menu_head'), $this);?>

        <?php $_from = $this->_tpl_vars['ENV']['APPLICATION']['_tables']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['table'] => $this->_tpl_vars['label']):
?>
        
        	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'tables_menu_options')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
            <div class="portletContent">
             <a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-table=<?php echo $this->_tpl_vars['table']; ?>
"
                           accesskey="accesskeys-navigation"
                           class="navItem <?php if ($this->_tpl_vars['ENV']['table'] == $this->_tpl_vars['table']): ?>currentNavItem<?php endif; ?>"
                           title="<?php echo $this->_tpl_vars['label']; ?>
"
                           id="TableLink_<?php echo $this->_tpl_vars['table']; ?>
">
                            <img
    src="<?php echo $this->_tpl_vars['ENV']['DATAFACE_URL']; ?>
/images/folder_icon.gif" alt="" class="navIconRoot" title="Plone Site" />
                            <span class="navItemText">
                                  <?php echo $this->_tpl_vars['label']; ?>

                            </span>
                        </a>
           </div>
           <?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
       <?php endforeach; endif; unset($_from); ?>
       <?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'tables_menu_tail'), $this);?>

       </div>
   </div>
</div>
<?php endif; ?>