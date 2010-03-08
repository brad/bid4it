<?php
/* Entry page, point application here. */

$time = microtime(true);
	// use the timer to time how long it takes to generate a page
require_once '{__DATAFACE_PATH__}/dataface-public-api.php';
	// include the initialization file
df_init(__FILE__, '{__DATAFACE_URL__}');
	// initialize the site

$app =& Dataface_Application::getInstance();
	// get an application instance and perform initialization
$app->display();
	// display the application

$time = microtime(true) - $time;
echo "<p>Execution Time: $time</p>";
?>
