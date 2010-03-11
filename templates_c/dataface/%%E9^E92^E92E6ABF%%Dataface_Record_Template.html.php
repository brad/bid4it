<?php /* Smarty version 2.6.18, created on 2009-09-29 15:08:27
         compiled from Dataface_Record_Template.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'load_record', 'Dataface_Record_Template.html', 20, false),array('function', 'block', 'Dataface_Record_Template.html', 25, false),array('function', 'record_tabs', 'Dataface_Record_Template.html', 36, false),array('function', 'actions', 'Dataface_Record_Template.html', 37, false),array('block', 'use_macro', 'Dataface_Record_Template.html', 21, false),array('block', 'fill_slot', 'Dataface_Record_Template.html', 23, false),array('block', 'define_slot', 'Dataface_Record_Template.html', 30, false),array('modifier', 'count', 'Dataface_Record_Template.html', 38, false),)), $this); ?>
<?php echo $this->_plugins['function']['load_record'][0][0]->load_record(array(), $this);?>

<?php $this->_tag_stack[] = array('use_macro', array('file' => "Dataface_Main_Template.html")); $_block_repeat=true;$this->_plugins['block']['use_macro'][0][0]->use_macro($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>

	<?php $this->_tag_stack[] = array('fill_slot', array('name' => 'main_section')); $_block_repeat=true;$this->_plugins['block']['fill_slot'][0][0]->fill_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
		<?php if ($this->_tpl_vars['ENV']['prefs']['show_result_controller']): ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_details_controller'), $this);?>

		<div id="details-controller"><?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "Dataface_Details_Controller.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?></div>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_details_controller'), $this);?>

		<?php endif; ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_record_heading'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'record_heading')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>

			<h2 class="dataface-record-title"><?php if ($this->_tpl_vars['ENV']['record']): ?><span class="dataface-current-record-prelabel"><b>Current Record:</b> </span><?php echo $this->_tpl_vars['ENV']['record']->getTitle(); ?>
<?php else: ?>No Records Matched your Request<?php endif; ?></h2>
		<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_record_heading'), $this);?>

		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_record_tabs'), $this);?>

		<?php if ($this->_tpl_vars['ENV']['prefs']['show_record_tabs']): ?><?php echo $this->_plugins['function']['record_tabs'][0][0]->record_tabs(array('mincount' => 2,'id' => 'record_tabs','id_prefix' => "record-tabs-",'class' => 'contentViews','selected_action' => $this->_tpl_vars['ENV']['mode']), $this);?>

			<?php echo $this->_plugins['function']['actions'][0][0]->actions(array('var' => 'menus','category' => 'record_actions'), $this);?>
  
			 <?php if (count($this->_tpl_vars['menus']) > 0): ?>
			<div id="contentActionsWrapper" class="contentActions">
			
					
			
					 
			
			
			<ul>
			
			
				
					   
							 <?php $_from = $this->_tpl_vars['menus']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['name'] => $this->_tpl_vars['menu']):
?>
			
							<li>
								<a href="<?php echo $this->_tpl_vars['menu']['url']; ?>
"
								   id="record-actions-menu-<?php echo $this->_tpl_vars['menu']['id']; ?>
"
								   onmouseup="if (activeButton != null) resetButton(activeButton);"
								   title="<?php echo $this->_tpl_vars['menu']['description']; ?>
">
								  <img src="<?php echo $this->_tpl_vars['menu']['icon']; ?>
"
									   alt="" width="16" height="16" />
								  <?php echo $this->_tpl_vars['menu']['label']; ?>

								</a>
							</li>
							<?php endforeach; endif; unset($_from); ?>
							 
			 
				
			
			</ul>
			
			
			
			
					
			
				</div>
			<?php endif; ?>
		
		
		<div class="documentContent" id="region-content" style="border: 1px solid gray">
		<?php endif; ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_record_content'), $this);?>

			<?php $this->_tag_stack[] = array('define_slot', array('name' => 'record_content')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
				Record Content goes here ...
			<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_record_content'), $this);?>

		<?php if ($this->_tpl_vars['ENV']['prefs']['show_record_tabs']): ?>
		</div>
		<?php endif; ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'before_record_footer'), $this);?>

		<?php $this->_tag_stack[] = array('define_slot', array('name' => 'record_footer')); $_block_repeat=true;$this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['define_slot'][0][0]->define_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php echo $this->_plugins['function']['block'][0][0]->block(array('name' => 'after_record_footer'), $this);?>

		
	
	<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['fill_slot'][0][0]->fill_slot($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>

<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo $this->_plugins['block']['use_macro'][0][0]->use_macro($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>