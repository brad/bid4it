<?php
/*
Binary Large Object from database.
 */
if ( !isset( $_REQUEST['-field'] ) ) die("Could not complete request.  No field name specified.");
if ( !isset( $_REQUEST['-table'] ) ) die("Could not complete request.  No table specified.");

require_once 'Dataface/Application.php';
$app =& Dataface_Application::getInstance();
$fieldname = $_REQUEST['-field'];
$tablename = $_REQUEST['-table'];
$table =& Dataface_Table::loadTable($tablename);

if ( !$table->isBlob($fieldname) ) die("blob.php can only load blobs.  '$fieldname' does not appear to be a blob");
$field =& $table->getField($fieldname);
print_r($field); exit;

if ( isset($_REQUEST['-index']) ) $index = $_REQUEST['-index'];
	else $index = 0;
	$queryTool =& Dataface_QueryTool::loadResult($tablename, null, $_REQUEST);
	$mimetype = $field['mimetype'];
	$columns = array($fieldname, $mimetype);
	$queryTool->loadCurrent($columns, true, true);
	header("Content-type: ".$table->getValue($mimetype, $index));
echo $table->getValue($fieldname, $index);

