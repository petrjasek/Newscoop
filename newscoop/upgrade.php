<?php
/**
 * @package Campsite
 *
 * @author Mugur Rus <mugur.rus@gmail.com>
 * @copyright 2008 MDLF, Inc.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version $Revision$
 * @link http://www.campware.org
 */


$upgrade_trigger_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'upgrading.php';
if (!file_exists($upgrade_trigger_path)) {
    header('Location: index.php');
    exit(0);
}

require_once(__DIR__ . '/constants.php');
require_once __DIR__ . '/application.php';

$application->bootstrap();

header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");

$g_documentRoot = dirname(__FILE__);

// goes to install process if configuration files does not exist yet
if (!file_exists($g_documentRoot.'/conf/configuration.php')
        || !file_exists($g_documentRoot.'/conf/database_conf.php')) {
    header('Location: install/index.php');
    exit(0);
}

require_once($g_documentRoot.'/include/campsite_init.php');
require_once($g_documentRoot.'/bin/cli_script_lib.php');
require_once($g_documentRoot.'/install/classes/CampInstallation.php');
require_once($g_documentRoot.'/classes/User.php');
require_once($g_documentRoot.'/classes/CampPlugin.php');

set_time_limit(0);
$dbVersion = '';
$dbRoll = '';
$res = camp_detect_database_version($Campsite['DATABASE_NAME'], $dbVersion, $dbRoll);
if ($res !== 0) {
    $dbVersion = '[unknown]';
}
$dbInfo = $dbVersion;
if (!in_array($dbRoll, array('', '.'))) {
    $dbInfo .= ', roll ' . $dbRoll;
}
echo "Upgrading the database from version $dbInfo...";

// initiates the campsite site
$campsite = new CampSite();

// loads site configuration settings
$campsite->loadConfiguration(CS_PATH_CONFIG.DIR_SEP.'configuration.php');

// starts the session
$campsite->initSession();

$session = CampSite::GetSessionInstance();
$configDb = array('hostname'=>$Campsite['db']['host'],
                  'hostport'=>$Campsite['db']['port'],
                  'username'=>$Campsite['db']['user'],
                  'userpass'=>$Campsite['db']['pass'],
                  'database'=>$Campsite['db']['name']);
$session->setData('config.db', $configDb, 'installation');

// upgrading the database
$res = camp_upgrade_database($Campsite['DATABASE_NAME'], true, true);
if ($res !== 0) {
    display_upgrade_error("While upgrading the database: $res");
}
CampCache::singleton()->clear('user');
CampCache::singleton()->clear();
SystemPref::DeleteSystemPrefsFromCache();

// replace $campsite by $gimme
require_once($g_documentRoot.'/classes/TemplateConverterNewscoop.php');
$template_files = camp_read_files($g_documentRoot.'/templates');
$converter = new TemplateConverterNewscoop();
if (!empty($template_files)) {
    foreach($template_files as $template_file) {
        $converter->read($template_file);
        $converter->parse();
        $converter->write();
    }
}

// update plugins
CampPlugin::OnUpgrade();

CampRequest::SetVar('step', 'finish');
$install = new CampInstallation();
$install->initSession();
$step = $install->execute();

// update plugins environment
CampPlugin::OnAfterUpgrade();

CampTemplate::singleton()->clearCache();

// replace javascript by js in .htaccess file
$htaccesspath = $g_documentRoot . '/.htaccess';
if (upgrade_htaccess($htaccesspath) == false) {
    display_upgrade_error('Could not write .htaccess file.<br />Please read the '
        . 'UPGRADE.txt file in this same directory to see what changes need to '
        . 'be apply for this specific version of Newscoop.', FALSE);
}

if (file_exists($upgrade_trigger_path)) {
    @unlink($upgrade_trigger_path);
}

function display_upgrade_error($p_errorMessage, $exit = TRUE) {
    if (defined('APPLICATION_ENV') && APPLICATION_ENV == 'development') {
        var_dump($p_errorMessage);
        if ($exit) {
            exit(1);
        }
    }

    $template = CS_SYS_TEMPLATES_DIR.DIR_SEP.'_campsite_error.tpl';
    $templates_dir = CS_TEMPLATES_DIR;
    $params = array('context' => null,
                    'template' => $template,
                    'templates_dir' => $templates_dir,
                    'error_message' => $p_errorMessage
    );
    $document = CampSite::GetHTMLDocumentInstance();
    $document->render($params);
    if ($exit) exit(0);
}

function upgrade_htaccess($htaccesspath)
{
    if (!file_exists($htaccesspath)) {
        return false;
    }
    if (!($htaccess = @file_get_contents($htaccesspath))) {
        return false;
    }
    $htaccess = str_replace('+javascript', '+js', $htaccess);
    if (@file_put_contents($htaccesspath, $htaccess) === false) {
        return false;
    }
    return true;
}

?>
<p>finished<br/><a href="<?php echo "/$ADMIN";?>">Administration</a></p>
