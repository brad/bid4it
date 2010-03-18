<?
require_once dirname(__FILE__).'/config.inc.php';
header('Content-type: text/javascript');
?>
// Global Plone variables that need to be accessible to the Javascripts

//portal_url = 'http://localhost/~shannah/lesson_plans';
portal_url = '<?= DATAFACE_URL ?>';
DATAFACE_URL = portal_url;
DATAFACE_SITE_URL = '<?= DATAFACE_SITE_URL ?>';
DATAFACE_SITE_HREF = '<?= DATAFACE_SITE_HREF ?>';
