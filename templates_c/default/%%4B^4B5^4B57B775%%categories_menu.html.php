<?php /* Smarty version 2.6.18, created on 2009-09-29 15:06:26
         compiled from categories_menu.html */ ?>
<div class="menu-block">
<dl id="categories-menu" class="side-menu">
	<dt>Categories</dt>
	<dd><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=list&-table=products" title="All products">Show All Products</a></dd>
	<?php $_from = $this->_tpl_vars['categories']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['category']):
?>
		<dd><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=list&-table=products&product_categories=<?php echo $this->_tpl_vars['category']['category_id']; ?>
" title="Show products in the `$category.category_name` category"><?php echo $this->_tpl_vars['category']['category_name']; ?>
  <em>(<?php echo $this->_tpl_vars['category']['num']; ?>
)</em></a></dd>
	<?php endforeach; endif; unset($_from); ?>
</dl>
</div>