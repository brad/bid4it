<?php
require_once 'init.php';
init(__FILE__, '/~josef/lesson_plans');
require_once 'Dataface/Application.php';

$app =& Dataface_Application::getInstance();
$app->display();


?>
