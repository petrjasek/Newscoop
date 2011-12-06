<?php

$_plugin_dir = dirname(dirname(dirname(dirname(__FILE__))));
$_newscoop_dir = dirname(dirname($_plugin_dir));

if (!file_exists($_newscoop_dir . DIRECTORY_SEPARATOR . 'conf')) {
    if (file_exists($_newscoop_dir . DIRECTORY_SEPARATOR . 'newscoop' . DIRECTORY_SEPARATOR . 'conf')) {
        $_newscoop_dir = $_newscoop_dir . DIRECTORY_SEPARATOR . 'newscoop';
    }
}

require($_newscoop_dir . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'database_conf.php');

?>
