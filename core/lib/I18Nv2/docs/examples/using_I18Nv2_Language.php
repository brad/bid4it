<?php

/**
* Using I18Nv2_Language
* =====================
*
* I18Nv2 provides translated lists of language names.
* 
* $Id: using_I18Nv2_Language.php,v 1.1 2004/05/03 15:02:17 mike Exp $
*/

require_once 'I18Nv2/Language.php';

$lang = &new I18Nv2_Language('it', 'iso-8859-1');

echo "Italian name for English: ",
    $lang->getName('en'), "\n";

echo "Italian name for French:  ",
    $lang->getName('fr'), "\n";
?>