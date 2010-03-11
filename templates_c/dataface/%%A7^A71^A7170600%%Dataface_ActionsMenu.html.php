<?php /* Smarty version 2.6.18, created on 2009-09-29 15:07:50
         compiled from Dataface_ActionsMenu.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'define_slot', 'Dataface_ActionsMenu.html', 20, false),array('modifier', 'count', 'Dataface_ActionsMenu.html', 21, false),array('function', 'block', 'Dataface_ActionsMenu.html', 23, false),)), $this); ?>
<?php $this->_tag_stack[] = array('define_slot', array('name' => 'actions_menu')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
<?php if (count($this->_tpl_vars['actions']) > 0): ?>
<ul <?php if ($this->_tpl_vars['class']): ?>class="<?php echo $this->_tpl_vars['class']; ?>
"<?php endif; ?> <?php if ($this->_tpl_vars['id']): ?>id="<?php echo $this->_tpl_vars['id']; ?>
"<?php endif; ?>>
	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'actions_menu_head'), $this);?>

<?php $_from = $this->_tpl_vars['actions']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['name'] => $this->_tpl_vars['action']):
?>
            
              <li id="<?php echo $this->_tpl_vars['id_prefix']; ?>
<?php echo $this->_tpl_vars['action']['id']; ?>
"
                  <?php if ($this->_tpl_vars['action']['name'] == $this->_tpl_vars['selected_action'] || $this->_tpl_vars['action']['selected']): ?>class="selected"<?php else: ?>class="plain"<?php endif; ?>>
                
                <a id="<?php echo $this->_tpl_vars['id_prefix']; ?>
<?php echo $this->_tpl_vars['action']['id']; ?>
-link" href="<?php if ($this->_tpl_vars['action']['name'] == $this->_tpl_vars['selected_action'] || $this->_tpl_vars['action']['selected']): ?>#<?php else: ?><?php echo $this->_tpl_vars['action']['url']; ?>
<?php endif; ?>"<?php if ($this->_tpl_vars['action']['onclick']): ?> onclick="<?php echo $this->_tpl_vars['action']['onclick']; ?>
"<?php endif; ?>
                   accesskey="<?php echo $this->_tpl_vars['action']['accessKey']; ?>
" title="<?php echo $this->_tpl_vars['action']['description']; ?>
">
                   <?php if ($this->_tpl_vars['action']['icon']): ?><img id="<?php echo $this->_tpl_vars['id_prefix']; ?>
<?php echo $this->_tpl_vars['action']['id']; ?>
-icon" src="<?php echo $this->_tpl_vars['action']['icon']; ?>
" alt="<?php echo $this->_tpl_vars['action']['label']; ?>
"/><?php endif; ?>
                   <span class="action-label"><?php echo $this->_tpl_vars['action']['label']; ?>
</span>
                </a>
              </li>
            
            
<?php endforeach; endif; unset($_from); ?>
            
     <?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'actions_menu_tail'), $this);?>
       
</ul>
<?php endif; ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>