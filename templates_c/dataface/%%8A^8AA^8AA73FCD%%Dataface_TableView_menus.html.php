<?php /* Smarty version 2.6.18, created on 2009-09-29 15:07:50
         compiled from Dataface_TableView_menus.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('function', 'actions', 'Dataface_TableView_menus.html', 21, false),array('modifier', 'count', 'Dataface_TableView_menus.html', 22, false),)), $this); ?>
    

 <?php echo $this->_plugins['function']['actions'][0][0]->actions(array('var' => 'menus','category' => 'table_actions'), $this);?>
  
 <?php if (count($this->_tpl_vars['menus']) > 0): ?>
<div id="contentActionsWrapper" class="contentActions">

        

         


<ul>


    
           
                 <?php $_from = $this->_tpl_vars['menus']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['name'] => $this->_tpl_vars['menu']):
?>

                <li>
                    <a href="<?php echo $this->_tpl_vars['menu']['url']; ?>
"
                       id="actions-menu-<?php echo $this->_tpl_vars['menu']['id']; ?>
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