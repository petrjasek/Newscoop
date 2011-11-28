#!/usr/bin/env php
<?php

$plugin_dir = dirname(dirname(dirname(dirname(__FILE__))));
require_once($plugin_dir.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'NewsImportEnv.php');
require_once($plugin_dir.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'TrailerProcessor.php');

/*
if ( ("cli" == php_sapi_name()) && (!isset($GLOBALS['g_cliInited'])) ) {
    NewsImportEnv::BootCli();
    $GLOBALS['g_cliInited'] = true;
}
*/

$trailers_default_locks = NewsImportEnv::GetLockDir();

// whether we can start now
$locks_path_dir = NewsImportEnv::AbsolutePath($trailers_default_locks);
if (!NewsImportEnv::Start($locks_path_dir, 'trailers')) {
    exit(1);
    //$msg = 'trailers_locked';
    //return $msg;
}

//NewsImportEnv::AskForTrailers();

TrailerProcessor::AskForTrailers();



NewsImportEnv::Stop($locks_path_dir, 'trailers');

?>