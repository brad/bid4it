<?php /* Smarty version 2.6.18, created on 2009-09-29 15:11:44
         compiled from Dataface_New_Record.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'use_macro', 'Dataface_New_Record.html', 20, false),array('block', 'fill_slot', 'Dataface_New_Record.html', 21, false),array('block', 'define_slot', 'Dataface_New_Record.html', 23, false),array('function', 'block', 'Dataface_New_Record.html', 22, false),array('modifier', 'count', 'Dataface_New_Record.html', 25, false),)), $this); ?>
<?php $this->_tag_stack[] = array('use_macro', array('file' => "Dataface_Main_Template.html")); $_block_repeat=true;$this->_plugins['block']['use_macro'][0][0]->use_macro($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
	<?php $this->_tag_stack[] = array('fill_slot', array('name' => 'main_section')); $_block_repeat=true;$this->_plugins['block']['fill_slot'][0][0]->fill_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_new_record_form'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'new_record_form')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		
		<?php if ($this->_tpl_vars['tabs'] && count($this->_tpl_vars['tabs']) > 0): ?>
			<div id="edit-record-tabs-container">
			<ul id="edit-record-tabs">
				<li class="label">Steps:</li>
			<?php $_from = $this->_tpl_vars['tabs']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['tab']):
?>
				<li class="<?php echo $this->_tpl_vars['tab']['css_class']; ?>
"><a href="<?php echo $this->_tpl_vars['tab']['url']; ?>
"><?php echo $this->_tpl_vars['tab']['label']; ?>
</a></li>
			<?php endforeach; endif; unset($_from); ?>
				<li class="Instructions">Remember to press &quot;Save&quot; when you're done.</li>
			</ul>
			</div>
		<?php endif; ?>
		<?php echo $this->_tpl_vars['form']; ?>

		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_new_record_form'), $this);?>

	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['fill_slot'][0][0]->fill_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['use_macro'][0][0]->use_macro($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>