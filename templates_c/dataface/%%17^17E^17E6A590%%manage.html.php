<?php /* Smarty version 2.6.18, created on 2009-09-29 15:08:20
         compiled from manage.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'use_macro', 'manage.html', 1, false),array('block', 'fill_slot', 'manage.html', 2, false),array('function', 'actions', 'manage.html', 6, false),)), $this); ?>
<?php $this->_tag_stack[] = array('use_macro', array('file' => "Dataface_Main_Template.html")); $_block_repeat=true;$this->_plugins['block']['use_macro'][0][0]->use_macro($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
	<?php $this->_tag_stack[] = array('fill_slot', array('name' => 'main_column')); $_block_repeat=true;$this->_plugins['block']['fill_slot'][0][0]->fill_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<h2>Administration Control Panel</h2>
		<p>This page allows you to manage various aspects of this application.  Select one of the options below.</p>
		
		<?php echo $this->_plugins['function']['actions'][0][0]->actions(array('var' => 'actions','category' => 'management_actions'), $this);?>

		<dl>
		<?php $_from = $this->_tpl_vars['actions']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['name'] => $this->_tpl_vars['action']):
?>
			<dt><a href="<?php echo $this->_tpl_vars['action']['url']; ?>
"><?php echo $this->_tpl_vars['action']['label']; ?>
</a></dt>
			<dd><?php echo $this->_tpl_vars['action']['description']; ?>
</dd>
		<?php endforeach; endif; unset($_from); ?>
		</dl>
	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['fill_slot'][0][0]->fill_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['use_macro'][0][0]->use_macro($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>