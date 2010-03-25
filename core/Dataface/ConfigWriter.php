<?php

/**
 * A class responsible for writing configuration information.  This could easily 
 * be included inside the ConfigTool class except that we want to keep the size of the 
 * config tool down to a minumum because it is always needed.
 */
 
class Dataface_ConfigWriter {

	
	function setupDB(){
		if ( !is_a($this, 'Dataface_ConfigTool') ){
			trigger_error('ConfigWriter methods are only to be used via the Dataface_ConfigTool class.', E_USER_ERROR);
		}
		$app =& Dataface_Application::getInstance();
		$config = file_get_contents(DATAFACE_PATH.'/install/dbconfig.sql');
		foreach ( explode(';',$config) as $query){
			if (!trim($query)) continue;
			$res = mysql_query($query, $app->db());
			if ( !$res ){
				trigger_error("Could not set up configuration database: ".mysql_error($app->db()), E_USER_ERROR);
			}
		}
		return true;
	}
	
	function writeConfig($storage=null){
		if ( !is_a($this, 'Dataface_ConfigTool') ){
			trigger_error('ConfigWriter methods are only to be used via the Dataface_ConfigTool class.', E_USER_ERROR);
		}
		
		$this->loadAllConfig();
		$app =& Dataface_Application::getInstance();
		
		if ( $storage === null ) $storage = $app->_conf['config_storage'];
		
		switch (strtolower($storage)){
			case 'db':
			case 'database':
			case 'sql':
				return $this->writeConfigToDB();
			case 'ini':
				return $this->writeConfigToINI();
		}
	
	}
	
	function writeConfigToDB(){
		import('Dataface/Table.php');
		import('Dataface/Record.php');
		import('Dataface/IO.php');
		if ( !is_a($this, 'Dataface_ConfigTool') ){
			trigger_error('ConfigWriter methods are only to be used via the Dataface_ConfigTool class.', E_USER_ERROR);
		}
		$this->loadAllConfig();
		$app =& Dataface_Application::getInstance();
		// first let's make copies of the current configuration.
		$timestamp = time();
		foreach ( $this->configTypes as $type ){
			$res = mysql_query("CREATE TABLE `__".addslashes($type)."__".$timestamp."` SELECT * FROM `__".addslashes($type)."__`", $app->db());
			if ( !$res ){
				trigger_error("Failed to make backup of table '__".$type."__'.". mysql_error($app->db()), E_USER_ERROR);
			}
		}
		
		$res = mysql_query("CREATE TABLE `__properties__".$timestamp."` SELECT * FROM `__properties__`", $app->db());
		if ( !$res ){
			trigger_error("Failed to make backup of table '__properties__'.", $app->db());
		}
		
		// Now that we have made our backups, we can continue to write the configuration to the database.
		//print_r($this->config);
		foreach ( $this->configTypes as $type ){
		
			$res = mysql_query("DELETE FROM `__".addslashes($type)."__`", $app->db());
			if ( !$res ){
				trigger_error("Failed to delete all records from table '__".$type."__'", $app->db());
			}
			
		
			foreach ( $this->config[$type] as $tablename=>$tableConfig ){
				foreach ( $tableConfig as $sectionname=>$section){
					$tableObj =& Dataface_Table::loadTable('__'.$type.'__');
					$record =& new Dataface_Record('__'.$type.'__', array());
					$record->useMetaData = false;  // some of the field names begin with '__' which would conflict with dataface's handling of MetaData fields.
					
					
					foreach ( array_keys($tableObj->fields()) as $fieldname ){
						$record->setValue($fieldname, @$section[$fieldname]);
						unset($section[$fieldname]);
					}
					$record->setValue('name',$sectionname);
					$record->setValue('table', $tablename);
					//echo nl2br("Section name: $sectionname\nTable: $tablename\n");
					//print_r($record->strvals());
					
					echo nl2br("\nWriting section: $sectionname : ");
					print_r($record->strvals());
					
					// now that we have created the record, we write the record
					$io =& new Dataface_IO('__'.$type.'__');
					$res = $io->write($record);
					if ( PEAR::isError($res) ){
						trigger_error($res->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
					} else if (!$res ){
						trigger_error("Failure to write to database for unknown reason.", E_USER_ERROR);
					}
					
					// now for the rest of the properties.
					foreach ( $section as $propertyName=>$propertyValue ){
						$res = mysql_query("
							INSERT INTO 
							 `__properties__` 
							 (`parent_id`,`parent_type`,`property_name`,`property_value`)
							VALUES
							 ('".$record->val($type.'_id')."', 
							 '".addslashes($type)."',
							 '".addslashes($propertyName)."',
							 '".addslashes($propertyValue)."')", $app->db());
						if ( !$res ){
							trigger_error("Failed to add property '$propertyName' to table '__properties__' with value '$propertyValue'".mysql_error($app->db()), E_USER_ERROR);
						}
					}
					
					unset($tableObj);
					unset($record);
					unset($io);
				}
			}
			
		}
	
	}

}
