<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty jsencode modifier plugin
 *
 * Type:     modifier<br>
 * Name:     truncate<br>
 * Purpose:  Trim whitespace, encode, and optionally strip tages in string.
 * @author Sebastian Göbel <sebastian.goebel at sourcefabric dot org>
 * @param string
 * @param boolean
 * @param boolean
 * @return string
 */

function smarty_modifier_jsencode($string, $strip_tags = '<h4><b><i><em><strong><br><p><a><img>', $addslashes = true)
{
    //$string = iconv("UTF-8","UTF-8//IGNORE",$string); // http://www.zeitoun.net/articles/clear-invalid-utf8/start
   $string = iconv("UTF-8","ISO-8859-1//TRANSLIT",$string);
   $string = iconv("ISO-8859-1","UTF-8//TRANSLIT",$string);
    //$string = preg_replace('/[^(\x20-\x7F)]*/','', $string);
   //$string = utf8_encode($string);

    $string = preg_replace("/\s+/", " ", $string);  // replace whitespace by single space
    
    if ($strip_tags) {
      $string = strip_tags($string, $strip_tags);
    }
    
    if ($addslashes) {
      $string = addslashes($string);
    }
    
    return $string;
} // smarty_modifier_jsencode

?>
