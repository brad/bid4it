<?php /* Smarty version 2.6.18, created on 2009-09-29 15:11:44
         compiled from Dataface_Form_Template.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'collapsible_sidebar', 'Dataface_Form_Template.html', 123, false),)), $this); ?>

<!-- Display the fields -->
 
<?php echo '
<script language="javascript" type="text/javascript"><!--
	function Dataface_QuickForm(){
		
	}
	Dataface_QuickForm.prototype.setFocus = function(element_name){
		document.'; ?>
<?php echo $this->_tpl_vars['form_data']['name']; ?>
<?php echo '.elements[element_name].focus();
		document.'; ?>
<?php echo $this->_tpl_vars['form_data']['name']; ?>
<?php echo '.elements[element_name].select();
	}
	var quickForm = new Dataface_QuickForm();
//--></script>
'; ?>

		
<form<?php echo $this->_tpl_vars['form_data']['attributes']; ?>
>
<?php echo $this->_tpl_vars['form_data']['hidden']; ?>

<?php echo $this->_tpl_vars['form_data']['javascript']; ?>

 

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "Dataface_Form_Section_Template.html", 'smarty_include_vars' => array('elements' => $this->_tpl_vars['form_data']['elements'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
<?php $_from = $this->_tpl_vars['form_data']['sections']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['section']):
?>
	<?php if ($this->_tpl_vars['section']['elements']): ?>

				<?php if ($this->_tpl_vars['section']['field']['collapsed']): ?><?php $this->assign('display', 'collapsed'); ?><?php else: ?><?php $this->assign('display', 'expanded'); ?><?php endif; ?>
		<?php if ($this->_tpl_vars['section']['name'] == '__submit__'): ?><?php $this->assign('hide_heading', '1'); ?><?php else: ?><?php $this->assign('hide_heading', '0'); ?><?php endif; ?>
		
		<?php $this->_tag_stack[] = array('collapsible_sidebar', array('heading' => $this->_tpl_vars['section']['header'],'display' => $this->_tpl_vars['display'],'hide_heading' => $this->_tpl_vars['hide_heading'])); $_block_repeat=true;smarty_block_collapsible_sidebar($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
			<?php if ($this->_tpl_vars['section']['field']['template']): ?>
				<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => $this->_tpl_vars['section']['field']['template'], 'smarty_include_vars' => array('elements' => $this->_tpl_vars['section']['elements'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
			<?php else: ?>
				<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "Dataface_Form_Section_Template.html", 'smarty_include_vars' => array('elements' => $this->_tpl_vars['section']['elements'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
			<?php endif; ?>
		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_collapsible_sidebar($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php endif; ?>
<?php endforeach; endif; unset($_from); ?>

</form>

 