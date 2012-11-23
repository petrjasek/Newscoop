<?php
/**
 * @package Campsite
 *
 * @author Holman Romero <holman.romero@gmail.com>
 * @copyright 2007 MDLF, Inc.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version $Revision$
 * @link http://www.sourcefabric.org
 */

// some installation parts tend to take long time
set_time_limit(0);

define('INSTALL', TRUE);
require_once __DIR__ . '/../application.php';
$application->bootstrap('autoloader');

$GLOBALS['g_campsiteDir'] = dirname(dirname(__FILE__));
require_once($GLOBALS['g_campsiteDir'].'/include/campsite_constants.php');
require_once($GLOBALS['g_campsiteDir'].'/install/classes/CampInstallation.php');
require_once(CS_PATH_CONFIG.DIR_SEP.'install_conf.php');

if (file_exists(CS_PATH_CONFIG.DIR_SEP.'configuration.php')
        && file_exists(CS_PATH_CONFIG.DIR_SEP.'database_conf.php')) {
    header("Location: ".CS_PATH_BASE_URL.str_replace('/install', '', $Campsite['SUBDIR']));
}

// check if template cache dir is writable
$templates_cache = dirname(dirname(__FILE__)) . DIR_SEP . 'cache';
if (!is_writable($templates_cache)) {
    echo '<!DOCTYPE html>';
    echo '<html><head><meta charset="utf-8" />';
    echo '<title>Install requirement</title>';
    echo '<link rel="shortcut icon" href="' . $GLOBALS['g_campsiteDir'] . '/admin-style/images/7773658c3ccbf03954b4dacb029b2229.ico" />';
    echo '</head><body>';
    echo '<h1>Install requirement</h1>';
    echo "<p>Directory '$templates_cache' is not writable.</p>";
    echo "<p>Please make it writable in order to continue. (i.e. <code>$ sudo chmod o+w $templates_cache</code> on linux)</p>";
    echo '</body></html>';
    exit;
}
unset($templates_cache);

$install = new CampInstallation();

$install->initSession();

$step = $install->execute();

$install->dispatch($step);

$install->render();

if ($step == 'finish') {
	$template = CampTemplate::singleton();
	$template->clearCache();
}
