<?php
require_once 'HTML/QuickForm/input.php';
function df_clone($object){
	if (version_compare(phpversion(), '5.0') < 0) {
  		return $object;
 	 } else {
   		return @clone($object);
  	}
 
}

class HTML_QuickForm_grid extends HTML_QuickForm_input {


	var $fields=array();
	var $elements=array();
	var $next_row_id=0;
	var $addNew=true;
	var $delete=true;
	var $reorder=true;
	var $edit=true;
	
	function getName(){ return $this->name;}
	
	function HTML_QuickForm_grid($elementName=null, $elementLabel=null, $attributes=null)
    {
        $this->HTML_QuickForm_input($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->name = $elementName;
        $this->setName($elementName);
        
        $this->_type = 'grid';
       
    } //end constructor
    
    function addField(&$fieldDef, $element){
    	$this->fields[$fieldDef['name']] =& $fieldDef;
    	$this->elements[$fieldDef['name']] =& $element;
    }
    
    function getColumnLabels(){
    	$out = array();
    	foreach ( $this->fields as $field ){
    		$out[] = $field['widget']['label'];
    	}
    	return $out;
    }
    
    function getColumnIds(){
    	$out = array();
    	foreach ($this->fields as $field ){
    		$out[] = $field['name'];
    	}
    	return $out;
    }
    
    
    function getCellTemplate($column, $fieldId, $value=null, $permissions=array('view'=>1,'edit'=>1)){
    	$element = df_clone($this->elements[$column]);
    	
    	$element->setName($this->name.'['.($this->next_row_id).']['.$column.']');
    	$element->updateAttributes(
    		array(
    			'id'=>$column.'_'.$fieldId,
    			'onchange'=>'dataGridFieldFunctions.addRowOnChange(event);'.$element->getAttribute('onchange'),
    			'style'=>'width:100%;'.$element->getAttribute('style')
    			)
    		);
    	
    	if ($this->isFrozen() or !Dataface_PermissionsTool::checkPermission('edit', $permissions)) {
            $element->freeze();
        } else {
        	$element->unfreeze();
        }
        if ( isset($value) ){
       	 $element->setValue($value);
       	}
    	
    	return $element->toHtml();
    }
    
    function getEmptyCellTemplate($column, $fieldId){
    	return $this->getCellTemplate($column, $fieldId, null);
    
    }
    
    
    
    function toHtml(){
    	
        
    	ob_start();
    	if ( !defined('HTML_QuickForm_grid_displayed') ){
    		define('HTML_QuickForm_grid_displayed',true);
    		echo '<script type="text/javascript" language="javascript" src="'.DATAFACE_URL.'/HTML/QuickForm/grid.js"></script>';
    		
    	}
    	
    	
    	$columnNames = $this->getColumnLabels();
    	$columnIds = $this->getColumnIds();
    	$fielddata = $this->getValue();
    	
    	
    	
    	if ( !is_array($fielddata) ){
    		$fielddata = array();
    	}
    	$fieldName = $this->name;
?>
			<table style="width: 100%; <?php echo $this->getAttribute('style');?>">
                <thead>
                    <tr>
                    <?php foreach ( $columnNames as $columnName):?>
                        <th class="discreet" style="text-align: left">
                        	<?php echo $columnName?>
                        </th>
                     <?php endforeach;?>
                        <th /> 
                        <th />
                    </tr>
                </thead>            
                <tbody>
                	<?php $emptyRow = false; $count=0; foreach ( $fielddata as $rows ):?>
                	
                	<?php if ( !is_array($rows) /*or !isset($rows['__permissions__'])*/ ) continue; ?>
                    <tr df:row_id="<?php echo $this->next_row_id;?>">
                    	<?php $fieldId = $fieldName.'_'.($this->next_row_id); ?>
                      
                     
                     <?php
                        //IE doesn't seem to respect em unit paddings here so we
                        //use absolute pixel paddings.
                     ?>
                       <?php $rowEmpty = true; foreach ( $columnIds as $column ): ?>
                       <td style="padding-right: 10px;" valign="top"> 
                        <?php
                        	//$column_definition = $this->getColumnDefinition($column);
                        	$cell_value = $rows[$column];
                        	if ( trim($cell_value) ) $rowEmpty = false;
                        	if ( isset($rows['__permissions__']) and @$rows['__permissions__'][$column] ){
                        		$perms = $rows['__permissions__'][$column];
                        	} else {
                        		$perms = array('view'=>1,'edit'=>1);
                        	}
                        	$cell_html = $this->getCellTemplate($column, $fieldId, $cell_value, $perms);
                        ?>
                        	
                        <span>
                             <?php echo $cell_html;?>
                        </span>
                       </td>
                       <?php endforeach;
                       if ( $rowEmpty ) $emptyRow = true;
                       if ( $this->delete ):
                       ?>
                        <td style="width: 20px">
                        	<input type="hidden" name="<?php echo $fieldName.'['.$this->next_row_id.'][__id__]';?>" value="<?php echo $rows['__id__'];?>"/>
                            <img src="<?php echo DATAFACE_URL.'/images/delete_icon.gif';?>" 
                               style="cursor: pointer;"
                                  
                                 alt="Delete row"  
                                 onclick="dataGridFieldFunctions.removeFieldRow(this);return false"/>
                        </td>
                        <?php endif;?>
                        <?php if ($this->reorder):?>
                        <td style="width: 20px">
                           <img src="<?php echo DATAFACE_URL.'/images/arrowUp.gif';?>" 
                               style="cursor: pointer; display: block;"
                                 
                                 alt="Move row up"  
                                 onclick="dataGridFieldFunctions.moveRowUp(this);return false"/>
                            <img src="<?php echo DATAFACE_URL.'/images/arrowDown.gif';?>" 
                               style="cursor: pointer; display: block;"
                            
                                 alt="Move row up"  
                                 onclick="dataGridFieldFunctions.moveRowDown(this);return false"/> 
                          <?php endif;?>
                           <input type="hidden"
                                  name="<?php echo $fieldName.'['.$this->next_row_id.'][__order__]';?>"
                                  id="<?php echo 'orderindex__'.$fieldId;?>"
                                  value="<?php echo $this->next_row_id;?>"
                                  />                         
                        </td>
                       
                        
                    
                    </tr>
                    <?php $this->next_row_id++; endforeach;?>
                    <?php if (!$emptyRow and $this->addNew): ?>
                    <tr df:row_id="<?php echo $this->next_row_id;?>">
                    <?php
                    	$fieldId = $fieldName.'_'.$this->next_row_id;
                    	
                    ?>
                    	<?php foreach ($columnIds as $column):?>
                        <td style="padding-right: 10px;" valign="top">
                           <span >
                           <?php
                           	$cell_html = $this->getEmptyCellTemplate($column, $fieldId);
                           	echo $cell_html;
                           ?>
                                                                       
                              </span>
                        </td>
                        <?php endforeach;?>
                        <td style="width: 20px">
                        <?php if (!$this->_flagFrozen):?>
                           <input type="hidden" name="<?php echo $fieldName.'['.$this->next_row_id.'][__id__]';?>" value="new"/>
                           
                            <img style="display: none; cursor: pointer" 
                                 src="<?php echo DATAFACE_URL.'/images/delete_icon.gif';?>" 
                                 alt="Delete row"  
                                 onclick="dataGridFieldFunctions.removeFieldRow(this); return false"/>
                        <?php endif;?>
                        </td>
                        <td style="width: 20px">
                        <?php if (!$this->_flagFrozen):?>
                           <img src="<?php echo DATAFACE_URL.'/images/arrowUp.gif';?>" 
                                style="display: none; cursor: pointer;"
                                alt="Move row up"  
                                onclick="dataGridFieldFunctions.moveRowUp(this); return false"/>
                           <img src="<?php echo DATAFACE_URL.'/images/arrowDown.gif';?>" 
                                 style="display: none; cursor: pointer;"
                                 alt="Move row down"  
                                 onclick="dataGridFieldFunctions.moveRowDown(this); return false"/>  
                           <input type="hidden"
                                   value="<?php echo ( $this->getValue() ? 999999 : 0);?>"
                                   name="<?php echo $fieldName.'['.$this->next_row_id.'][__order__]';?>"
                                   id="<?php echo 'orderindex__'.$fieldId;?>"
                                    />
                        <?php endif;?>
                        </td>
                    </tr>
                    <?php endif;?>
                </tbody>
            </table>
            <input type="hidden" name="<?php echo $fieldName.'[__loaded__]';?>" value="1"/>

<?php
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
    
    }

}