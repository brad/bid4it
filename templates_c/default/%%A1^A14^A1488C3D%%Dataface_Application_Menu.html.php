<?php /* Smarty version 2.6.18, created on 2009-09-29 15:06:26
         compiled from Dataface_Application_Menu.html */ ?>
<?php if (isLoggedIn ( )): ?>
<div class="menu-block">
	<dl>
		<dt>Options</dt>
		
			<dd id="menu-watchlist"><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=watch_list">My Watch List</a></dd>
			<dd id="menu-edit-profile"><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=edit&-table=users&username==<?php echo $this->_tpl_vars['ENV']['username']; ?>
">Edit My Profile</a></dd>
			<?php if (isAdmin ( )): ?>
				<dd id="menu-auction-settings"><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=edit&-table=config">Auction Settings</a></dd>
				<dd id="menu-add-product"><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=new&-table=products">Add New Product</a></dd>
				<dd id="menu-add-category"><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=new&-table=product_categories">Add New Category</a></dd>
				<dd id="menu-view-reports"><a href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
?-action=reports">View Reports</a></dd>
			
			<?php endif; ?>

	</dl>
	
</div>		
<?php endif; ?>