<?php /* Smarty version 2.6.18, created on 2009-09-29 15:06:26
         compiled from Dataface_Main_Template.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'define_slot', 'Dataface_Main_Template.html', 19, false),array('block', 'translate', 'Dataface_Main_Template.html', 205, false),array('function', 'block', 'Dataface_Main_Template.html', 36, false),array('function', 'actions', 'Dataface_Main_Template.html', 107, false),array('function', 'language_selector', 'Dataface_Main_Template.html', 136, false),array('function', 'actions_menu', 'Dataface_Main_Template.html', 159, false),array('function', 'bread_crumbs', 'Dataface_Main_Template.html', 167, false),array('function', 'load_record', 'Dataface_Main_Template.html', 182, false),array('modifier', 'count', 'Dataface_Main_Template.html', 108, false),array('modifier', 'nl2br', 'Dataface_Main_Template.html', 220, false),)), $this); ?>
<?php if (! $this->_tpl_vars['ENV']['APPLICATION_OBJECT']->main_content_only): ?><?php $this->_tag_stack[] = array('define_slot', array('name' => 'doctype_tag')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd"><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<?php $this->_tag_stack[] = array('define_slot', array('name' => 'html_tag')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->_tpl_vars['ENV']['language']; ?>
" lang="<?php echo $this->_tpl_vars['ENV']['language']; ?>
"><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>

	<head>
	<?php if (! $this->_tpl_vars['ENV']['prefs']['no_history']): ?>
		<?php 
			$app =& Dataface_Application::getInstance();
			$_SESSION['--redirect'] = $app->url('');
		 ?>
	<?php endif; ?>
	
	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'html_head')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $this->_tpl_vars['ENV']['APPLICATION']['oe']; ?>
"/>
		<title><?php $this->_tag_stack[] = array('define_slot', array('name' => 'html_title')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php if ($this->_tpl_vars['ENV']['record']): ?><?php echo $this->_tpl_vars['ENV']['record']->getTitle(); ?>
 - <?php else: ?><?php echo $this->_tpl_vars['ENV']['table']; ?>
 - <?php endif; ?><?php if ($this->_tpl_vars['ENV']['APPLICATION']['title']): ?><?php echo $this->_tpl_vars['ENV']['APPLICATION']['title']; ?>
<?php else: ?>Dataface Application<?php endif; ?><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></title>
		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'dataface_stylesheets')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><link rel="stylesheet" type="text/css" href="<?php echo $this->_tpl_vars['ENV']['DATAFACE_URL']; ?>
/plone.css"/><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'custom_stylesheets')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><!-- Stylesheets go here --><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'custom_stylesheets2'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'dataface_javascripts')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<!-- Common Plone ECMAScripts -->
	
		<!-- Pull-down ECMAScript menu, only active if logged in -->
		
		<script type="text/javascript"
				src="<?php echo $this->_tpl_vars['ENV']['DATAFACE_URL']; ?>
/plone_menu.js">
		</script>
	
		<!-- Define dynamic server-side variables for javascripts in this one  -->
		<script type="text/javascript"
				src="<?php echo $this->_tpl_vars['ENV']['DATAFACE_URL']; ?>
/plone_javascript_variables.js.php">
		</script>
		<script type="text/javascript" language="javascript"><!--
		DATAFACE_URL = '<?php echo $this->_tpl_vars['ENV']['DATAFACE_URL']; ?>
';
		DATAFACE_SITE_URL = '<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_URL']; ?>
';
		DATAFACE_SITE_HREF = '<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
';
		DATAFACE_PATH = '<?php echo $this->_tpl_vars['ENV']['DATAFACE_PATH']; ?>
';
		//--></script>
	
		<script type="text/javascript"
				src="<?php echo $this->_tpl_vars['ENV']['DATAFACE_URL']; ?>
/plone_javascripts.js">
		</script>
		<?php if ($this->_tpl_vars['ENV']['APPLICATION']['usage_mode'] == 'edit'): ?>
		<script type="text/javascript"
				src="<?php echo $this->_tpl_vars['ENV']['DATAFACE_URL']; ?>
/js/editable.js">
		</script>
		<?php endif; ?>
		
		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
				<?php $_from = $this->_tpl_vars['ENV']['APPLICATION_OBJECT']->headContent; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['headContent']):
?>
			<?php echo $this->_tpl_vars['headContent']; ?>

		<?php endforeach; endif; unset($_from); ?>
		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'custom_javascripts')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<!-- custom javascripts can go in slot "custom_javascripts" -->
		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		
		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'head_slot')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<!-- Place any other items in the head of the document by filling the "head_slot" slot -->
		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "head_slot.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'head'), $this);?>

	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>


	</head>
	<body onload="bodyOnload()" <?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'body_atts'), $this);?>
>

		<link rel="alternate" href="<?php echo $this->_tpl_vars['ENV']['APPLICATION_OBJECT']->url('-action=feed&--format=RSS2.0'); ?>
"
          title="RSS 1.0" type="application/rss+xml" />

	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_body'), $this);?>

	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'html_body')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><!-- Replace the entire HTML Body with the "html_body" slot -->
	<div id="top-section">
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_header'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'global_header')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "global_header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_header'), $this);?>

			
		<?php if ($this->_tpl_vars['ENV']['prefs']['show_search']): ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_search'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'search_form')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<div class="search_form" style="float: right; white-space: nowrap" id="top-search-form">
			<form method="GET" action="<?php echo $_SERVER['HOST_URI']; ?>
<?php echo $this->_tpl_vars['ENV']['DATAFACE_SITE_HREF']; ?>
">
			<label>Search:</label>
			<input type="hidden" name="-table" value="<?php echo $this->_tpl_vars['ENV']['APPLICATION_OBJECT']->_currentTable; ?>
"/>
			<input type="text" name="-search" value="<?php echo $this->_tpl_vars['ENV']['search']; ?>
"/>
			<?php echo $this->_plugins['function']['actions'][0][0]->actions(array('category' => 'find_actions','var' => 'find_actions'), $this);?>

			<?php if (count($this->_tpl_vars['find_actions']) > 1): ?>
			<select name="-action">
			<?php $_from = $this->_tpl_vars['find_actions']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['find_action']):
?>
				<option value="<?php echo $this->_tpl_vars['find_action']['name']; ?>
"><?php echo $this->_tpl_vars['find_action']['label']; ?>
</option>
			<?php endforeach; endif; unset($_from); ?>
			</select>
			<?php else: ?>
				<input type="hidden" name="-action" value="list"/>
			<?php endif; ?>
			<input type="submit" name="-submit" value="Submit" id="search_submit_button" />
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_search_form_submit'), $this);?>

			</form>
		
		</div>
		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_search'), $this);?>


		<?php endif; ?>
		<?php if ($this->_tpl_vars['ENV']['prefs']['horizontal_tables_menu'] && $this->_tpl_vars['ENV']['prefs']['show_tables_menu']): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_nav_menu'), $this);?>

			<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "Dataface_NavMenu.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_nav_menu'), $this);?>


		<?php endif; ?>

		<div id="status-bar">
		<?php if ($this->_tpl_vars['ENV']['prefs']['show_result_controller']): ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_language_selector'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'language_selector')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><div id="language_selector"><?php echo $this->_plugins['function']['language_selector'][0][0]->language_selector(array('autosubmit' => 'true','type' => 'ul','use_flags' => false), $this);?>
</div><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_language_selector'), $this);?>

		<?php endif; ?>
		
		<?php if (! $this->_tpl_vars['ENV']['prefs']['hide_user_status']): ?>
		<div id="user-status">
		<?php if ($this->_tpl_vars['ENV']['username']): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_user_status_logged_in'), $this);?>

			<?php $this->_tag_stack[] = array('define_slot', array('name' => 'user_status_logged_in')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Logged in as <?php echo $this->_tpl_vars['ENV']['username']; ?>
 (<a href="<?php echo $this->_tpl_vars['APP']->url('-action=logout'); ?>
" title="Logout">Logout</a>)<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_user_status_logged_in'), $this);?>

		<?php elseif ($this->_tpl_vars['APP']->getAuthenticationTool()): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_user_status_not_logged_in'), $this);?>

			<?php $this->_tag_stack[] = array('define_slot', array('name' => 'user_status_not_logged_in')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><a href="<?php echo $this->_tpl_vars['APP']->url('-action=login'); ?>
" title="Login">Login</a><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_user_status_not_logged_in'), $this);?>

		<?php endif; ?>
		</div>
		<?php endif; ?>

		</div>
		<?php if (! $this->_tpl_vars['ENV']['prefs']['hide_personal_tools']): ?>
		<?php if ($this->_tpl_vars['ENV']['user']): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_personal_tools'), $this);?>

			<?php $this->_tag_stack[] = array('define_slot', array('name' => 'personal_tools')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
				<?php echo $this->_plugins['function']['actions_menu'][0][0]->actions_menu(array('id' => "personal-tools",'category' => 'personal_tools','id_prefix' => 'personal_tools_'), $this);?>

				<!--<div id="personal-tools"><a href="<?php echo $this->_tpl_vars['ENV']['user']->getURL(); ?>
">My Profile</a></div>-->
			<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_personal_tools'), $this);?>

		<?php endif; ?>
		<?php endif; ?>
		<?php if ($this->_tpl_vars['ENV']['prefs']['show_bread_crumbs']): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_bread_crumbs'), $this);?>

			<?php $this->_tag_stack[] = array('define_slot', array('name' => 'bread_crumbs')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><div class="bread-crumbs"><?php echo $this->_plugins['function']['bread_crumbs'][0][0]->bread_crumbs(array(), $this);?>
</div><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_bread_crumbs'), $this);?>

		<?php endif; ?>
		
	
	</div>
	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_main_table'), $this);?>

	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'main_table')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
	<table width="100%" border="0" cellpadding="5" id="main_table">
	<tr>
	<td valign="top" id="left_column">
	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_left_column'), $this);?>

	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'left_column')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<?php if ($this->_tpl_vars['ENV']['prefs']['show_record_tree']): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_record_tree'), $this);?>

			<?php echo $this->_plugins['function']['load_record'][0][0]->load_record(array('var' => 'record'), $this);?>

			<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "RecordNavMenu.html", 'smarty_include_vars' => array('record' => $this->_tpl_vars['record'])));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_record_tree'), $this);?>

		<?php endif; ?>
		<?php if (! $this->_tpl_vars['ENV']['prefs']['horizontal_tables_menu'] && $this->_tpl_vars['ENV']['prefs']['show_tables_menu']): ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_nav_menu'), $this);?>

		<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "Dataface_NavMenu.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_nav_menu'), $this);?>

		<?php endif; ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_application_menu'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'application_menu')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "Dataface_Application_Menu.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_application_menu'), $this);?>

		
	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_left_column'), $this);?>


	</td>
	<td valign="top" id="main_column">
	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_main_column'), $this);?>

	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'main_column')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
	
		<?php if ($this->_tpl_vars['back'] && ! $this->_tpl_vars['ENV']['APPLICATION']['hide_back']): ?>
		<div class="browser_nav_bar">
			<a href="<?php echo $this->_tpl_vars['back']['link']; ?>
" title="<?php $this->_tag_stack[] = array('translate', array('id' => "scripts.GLOBAL.LABEL_BACK")); $_block_repeat=true;$this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Back<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>">&lt;&lt; <?php $this->_tag_stack[] = array('translate', array('id' => "scripts.GLOBAL.LABEL_GO_BACK")); $_block_repeat=true;$this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Go Back<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></a>
		</div>
		<?php endif; ?>
		

		<div class="horizontalDivider"/>
		

		
		<?php if ($this->_tpl_vars['ENV']['APPLICATION_OBJECT']->numMessages() > 0): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_message'), $this);?>

			<div class="portalMessage">
				<ul>
				<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'message'), $this);?>

				<?php $_from = $this->_tpl_vars['ENV']['APPLICATION_OBJECT']->getMessages(); if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['msg']):
?>
					<li><?php echo ((is_array($_tmp=$this->_tpl_vars['msg'])) ? $this->_run_mod_handler('nl2br', true, $_tmp) : smarty_modifier_nl2br($_tmp)); ?>
</li>
				<?php endforeach; endif; unset($_from); ?>
				</ul>
			</div>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_message'), $this);?>

		<?php endif; ?>
		
		<?php if ($this->_tpl_vars['ENV']['APPLICATION_OBJECT']->numErrors() > 0): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_errors'), $this);?>

			<div class="portalMessage">
				<h5><?php $this->_tag_stack[] = array('translate', array('id' => "scripts.GLOBAL.HEADING_ERRORS")); $_block_repeat=true;$this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Errors<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></h5>
				<ul>
					<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'error'), $this);?>

					<?php $_from = $this->_tpl_vars['ENV']['APPLICATION_OBJECT']->getErrors(); if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['error']):
?>
						<li><?php echo ((is_array($_tmp=$this->_tpl_vars['error']->getMessage())) ? $this->_run_mod_handler('nl2br', true, $_tmp) : smarty_modifier_nl2br($_tmp)); ?>
</li>
					<?php endforeach; endif; unset($_from); ?>
				</ul>
			</div>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_errors'), $this);?>

		<?php endif; ?>
		
		<?php if ($this->_tpl_vars['ENV']['prefs']['show_table_tabs']): ?> 
	   		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_table_tabs'), $this);?>

			<?php $this->_tag_stack[] = array('define_slot', array('name' => 'table_tabs')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
				<?php echo $this->_plugins['function']['actions_menu'][0][0]->actions_menu(array('id' => 'table_tabs','id_prefix' => "table-tabs-",'class' => 'contentViews','category' => 'table_tabs','selected_action' => $this->_tpl_vars['ENV']['mode']), $this);?>

			<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_menus'), $this);?>

			<?php $this->_tag_stack[] = array('define_slot', array('name' => 'menus')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "Dataface_TableView_menus.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_menus'), $this);?>

		<?php endif; ?>
		
		<?php if ($this->_tpl_vars['ENV']['prefs']['show_table_tabs']): ?> 
		<div class="documentContent" id="region-content" style="border: 1px solid gray">
		<?php endif; ?>
	
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_main_section'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'main_section')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
			<?php if ($this->_tpl_vars['history'] && ! $this->_tpl_vars['ENV']['APPLICATION']['hide_history']): ?>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_recently_viewed'), $this);?>

			<div id="recentlyViewed">
				<b><?php $this->_tag_stack[] = array('translate', array('id' => "scripts.GLOBAL.LABEL_RECENT_RECORDS")); $_block_repeat=true;$this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Recent Records<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>:</b><select name="recentRecords" onchange="javascript:window.location=this.options[this.selectedIndex].value;" >
					<?php unset($this->_sections['i']);
$this->_sections['i']['loop'] = is_array($_loop=$this->_tpl_vars['history']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['i']['name'] = 'i';
$this->_sections['i']['show'] = true;
$this->_sections['i']['max'] = $this->_sections['i']['loop'];
$this->_sections['i']['step'] = 1;
$this->_sections['i']['start'] = $this->_sections['i']['step'] > 0 ? 0 : $this->_sections['i']['loop']-1;
if ($this->_sections['i']['show']) {
    $this->_sections['i']['total'] = $this->_sections['i']['loop'];
    if ($this->_sections['i']['total'] == 0)
        $this->_sections['i']['show'] = false;
} else
    $this->_sections['i']['total'] = 0;
if ($this->_sections['i']['show']):

            for ($this->_sections['i']['index'] = $this->_sections['i']['start'], $this->_sections['i']['iteration'] = 1;
                 $this->_sections['i']['iteration'] <= $this->_sections['i']['total'];
                 $this->_sections['i']['index'] += $this->_sections['i']['step'], $this->_sections['i']['iteration']++):
$this->_sections['i']['rownum'] = $this->_sections['i']['iteration'];
$this->_sections['i']['index_prev'] = $this->_sections['i']['index'] - $this->_sections['i']['step'];
$this->_sections['i']['index_next'] = $this->_sections['i']['index'] + $this->_sections['i']['step'];
$this->_sections['i']['first']      = ($this->_sections['i']['iteration'] == 1);
$this->_sections['i']['last']       = ($this->_sections['i']['iteration'] == $this->_sections['i']['total']);
?>
					<option value="<?php echo $this->_tpl_vars['history'][$this->_sections['i']['index']]['link']; ?>
" <?php if ($this->_tpl_vars['title'] == $this->_tpl_vars['history'][$this->_sections['i']['index']]['recordTitle']): ?>selected<?php endif; ?>><?php echo $this->_tpl_vars['history'][$this->_sections['i']['index']]['recordTitle']; ?>
</option>
					<?php endfor; endif; ?>
					</select>
			</div>
			<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_recently_viewed'), $this);?>

			<?php endif; ?>
			<div style="clear:both">
				<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_record_content'), $this);?>

				<?php $this->_tag_stack[] = array('define_slot', array('name' => 'record_content')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
				<?php echo $this->_tpl_vars['body']; ?>

				<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
				<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_record_content'), $this);?>

		
			</div>
		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_main_section'), $this);?>


		<?php if ($this->_tpl_vars['ENV']['prefs']['show_table_tabs']): ?>
		</div>
		<?php endif; ?>
	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	</td>
	</tr>
	</table>

	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'fineprint')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_fineprint'), $this);?>

		<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "Dataface_Fineprint.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_fineprint'), $this);?>

	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_global_footer'), $this);?>

	<?php $this->_tag_stack[] = array('define_slot', array('name' => 'global_footer')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "global_footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_global_footer'), $this);?>

	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	</body>
</html>
<?php else: ?>
					<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_main_section'), $this);?>

				<?php $this->_tag_stack[] = array('define_slot', array('name' => 'main_section')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
			<?php if ($this->_tpl_vars['history'] && ! $this->_tpl_vars['ENV']['APPLICATION']['hide_history']): ?>
						<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_recently_viewed'), $this);?>

			<div id="recentlyViewed">
				<b><?php $this->_tag_stack[] = array('translate', array('id' => "scripts.GLOBAL.LABEL_RECENT_RECORDS")); $_block_repeat=true;$this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>Recent Records:<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['translate'][0][0]->translate($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></b><select name="recentRecords" onchange="javascript:window.location=this.options[this.selectedIndex].value;" >
					<?php unset($this->_sections['i']);
$this->_sections['i']['loop'] = is_array($_loop=$this->_tpl_vars['history']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['i']['name'] = 'i';
$this->_sections['i']['show'] = true;
$this->_sections['i']['max'] = $this->_sections['i']['loop'];
$this->_sections['i']['step'] = 1;
$this->_sections['i']['start'] = $this->_sections['i']['step'] > 0 ? 0 : $this->_sections['i']['loop']-1;
if ($this->_sections['i']['show']) {
    $this->_sections['i']['total'] = $this->_sections['i']['loop'];
    if ($this->_sections['i']['total'] == 0)
        $this->_sections['i']['show'] = false;
} else
    $this->_sections['i']['total'] = 0;
if ($this->_sections['i']['show']):

            for ($this->_sections['i']['index'] = $this->_sections['i']['start'], $this->_sections['i']['iteration'] = 1;
                 $this->_sections['i']['iteration'] <= $this->_sections['i']['total'];
                 $this->_sections['i']['index'] += $this->_sections['i']['step'], $this->_sections['i']['iteration']++):
$this->_sections['i']['rownum'] = $this->_sections['i']['iteration'];
$this->_sections['i']['index_prev'] = $this->_sections['i']['index'] - $this->_sections['i']['step'];
$this->_sections['i']['index_next'] = $this->_sections['i']['index'] + $this->_sections['i']['step'];
$this->_sections['i']['first']      = ($this->_sections['i']['iteration'] == 1);
$this->_sections['i']['last']       = ($this->_sections['i']['iteration'] == $this->_sections['i']['total']);
?>
					<option value="<?php echo $this->_tpl_vars['history'][$this->_sections['i']['index']]['link']; ?>
" <?php if ($this->_tpl_vars['title'] == $this->_tpl_vars['history'][$this->_sections['i']['index']]['recordTitle']): ?>selected<?php endif; ?>><?php echo $this->_tpl_vars['history'][$this->_sections['i']['index']]['recordTitle']; ?>
</option>
					<?php endfor; endif; ?>
					</select>
			</div>
						<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_recently_viewed'), $this);?>

			<?php endif; ?>
			<div style="clear:both">
								<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_record_content'), $this);?>

								<?php $this->_tag_stack[] = array('define_slot', array('name' => 'record_content')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
				<?php echo $this->_tpl_vars['body']; ?>

				<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
								<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_record_content'), $this);?>

		
			</div>
		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_main_section'), $this);?>

<?php endif; ?> 