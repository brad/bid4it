<?php /* Smarty version 2.6.18, created on 2009-09-29 15:06:26
         compiled from public_product_list.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'result_controller', 'public_product_list.html', 21, false),)), $this); ?>


<ol id="product-list">
<?php $_from = $this->_tpl_vars['products']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }$this->_foreach['product'] = array('total' => count($_from), 'iteration' => 0);
if ($this->_foreach['product']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['product']):
        $this->_foreach['product']['iteration']++;
?>
	<li><a class="product-link" href="<?php echo $this->_tpl_vars['product']->getURL('-action=view'); ?>
">
		
		<h3><?php echo $this->_tpl_vars['product']->display('product_name'); ?>
</h3>
		<img src="<?php echo $this->_tpl_vars['product']->display('product_image'); ?>
" width="75" />
		<p class="product-description"><?php echo $this->_tpl_vars['product']->preview('product_description'); ?>
</p>
		<dl class="product-details">
			<dt>Minimum Bid</dt><dd><?php echo $this->_tpl_vars['product']->display('minimum_bid'); ?>
</dd>
			<dt>Current Bid</dt><dd><?php echo $this->_tpl_vars['product']->display('current_high_bid'); ?>
</dd>
		</dl>
		<div style="clear: both"></div>
	</a></li>
	
<?php endforeach; endif; unset($_from); ?>
</ol>

<div style="clear: both"></div>
<?php echo $this->_plugins['function']['result_controller'][0][0]->result_controller(array(), $this);?>