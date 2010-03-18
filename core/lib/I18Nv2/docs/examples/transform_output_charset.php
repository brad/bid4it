<?php

/**
* Automatically transform output charset
* ======================================
*
* I18Nv2 provides an easy way to utilize the ob_iconv_handler() through
* I18Nv2::autoConv($output_charset, $input_charset).
* 
* $Id: transform_output_charset.php,v 1.1 2004/05/03 15:02:17 mike Exp $
*/

require_once 'I18Nv2.php';

// Writing a shell app that should also display nicely in a DOS box
if (I18Nv2_WIN) {
    I18Nv2::autoConv('CP850');
}

// output some latin1 stuff
echo "����\n";

?>