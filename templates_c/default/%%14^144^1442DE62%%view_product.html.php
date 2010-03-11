<?php /* Smarty version 2.6.18, created on 2009-09-29 15:15:37
         compiled from view_product.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'string_format', 'view_product.html', 23, false),)), $this); ?>
<div id="product-view">
	<!--<h1><?php echo $this->_tpl_vars['product']->htmlValue('product_name'); ?>
</h1>-->
	<div class="product-image"><img src="<?php echo $this->_tpl_vars['product']->display('product_image'); ?>
" width="200"/></div>
	<div class="product-description">
		<?php echo $this->_tpl_vars['product']->htmlValue('product_description'); ?>

	</div>
	<dl class="product-details">
		<dt>Categories:</dt><dd><?php echo $this->_tpl_vars['product']->htmlValue('product_categories'); ?>
</dd>
		<dt>Current high bid:</dt><dd><?php echo $this->_tpl_vars['product']->htmlValue('high_bid_amount'); ?>
</dd>
		<?php if (! $this->_tpl_vars['product']->val('isOpen')): ?>
			<dt>Bidding open date</dt><dd><?php echo $this->_tpl_vars['product']->htmlValue('opening_time'); ?>
</dd>
		<?php endif; ?>
		<dt>Bidding close time</dt><dd><?php echo $this->_tpl_vars['product']->htmlValue('closing_time'); ?>
</dd>	
	</dl>
	<?php if (isLoggedIn ( )): ?>
		<?php if ($this->_tpl_vars['product']->val('isOpen') && $this->_tpl_vars['product']->val('high_bidder') != getUsername ( )): ?>
		<form action="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
" method="post">
		<input type="hidden" name="--force-validate" value="1" />
		<input type="hidden" name="-action" value="bid" />
		<input type="hidden" name="product_id" value="<?php echo $this->_tpl_vars['product']->val('product_id'); ?>
" />
			<fieldset>
				<legend>Bid on this product</legend>
				<label>Amount:</label><input type="text" name="--bid-amount" value="<?php echo ((is_array($_tmp=$this->_tpl_vars['product']->val('cooked_minimum_bid'))) ? $this->_run_mod_handler('string_format', true, $_tmp, "%.2f") : smarty_modifier_string_format($_tmp, "%.2f")); ?>
" size="6"/>
				<input type="submit" name="submit" value="Submit Bid Now"/>
			</fieldset>
		</form>
		<?php elseif ($this->_tpl_vars['product']->val('high_bidder') != getUsername ( )): ?>
			<b>You cannot bid on this product at this time because bidding is not currently open.  Check the open and close times for this product above.</b>
		<?php else: ?>
			<b>You are currently the high bidder on this product.</b>
		<?php endif; ?>
	<?php else: ?>
	<?php $this->assign('product_id', $this->_tpl_vars['product']->val('product_id')); ?>
	
	<a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=login">Log in to bid on this item</a>
	<?php endif; ?>
</div>