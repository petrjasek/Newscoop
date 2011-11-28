<?php

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// goes to install process if configuration files does not exist yet
if (!file_exists(APPLICATION_PATH . '/../conf/configuration.php')
    || !file_exists(APPLICATION_PATH . '/../conf/database_conf.php')) {
    $subdir = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/', -2));
    header("Location: $subdir/install/");
    exit;
}

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/../library'),
    realpath(APPLICATION_PATH . '/../include'),
    get_include_path(),
)));

if (!is_file('Zend/Application.php')) {
	// include libzend if we dont have zend_application
	set_include_path(implode(PATH_SEPARATOR, array(
		'/usr/share/php/libzend-framework-php',
		get_include_path(),
	)));
}

/** Zend_Application */
require_once 'Zend/Application.php';

$options = array( "config" => array());
$options['config'][] = APPLICATION_PATH . '/configs/application.ini';
// If a server name is defined for the application use that SERVER_NAME.ini aswell
if( getenv( 'APPLICATION_SERVER_NAME' ) ) {
    $options['config'][] = APPLICATION_PATH . '/configs/' . getenv( 'APPLICATION_SERVER_NAME' ) . '.ini';
}


// Create application, bootstrap, and run
$application = new Zend_Application
(
    APPLICATION_ENV,
    $options
);
$application->bootstrap();
if (empty($GLOBALS['zend_bootstrap_only'])) { // workaround for CS-3806
    $application->run();
}
