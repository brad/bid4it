<?php /* Smarty version 2.6.18, created on 2009-09-29 15:08:27
         compiled from after_user_profile.html */ ?>
<fieldset>
<legend>User Details</legend>
<dl>
	<dt>Full Name</dt><dd><?php echo $this->_tpl_vars['user']->val('fullname'); ?>
</dd>
	<dt>Title</dt><dd><?php echo $this->_tpl_vars['user']->val('title'); ?>
</dd>
	<dt>Department</dt><dd><?php echo $this->_tpl_vars['user']->val('department'); ?>
</dd>
	<dt>Email</td><dd><?php echo $this->_tpl_vars['user']->val('email'); ?>
</dd>
	<dt>Phone</dt><dd><?php echo $this->_tpl_vars['user']->val('phone'); ?>
</dd>
</dl>