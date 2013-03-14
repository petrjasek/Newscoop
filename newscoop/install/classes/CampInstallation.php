<?php
/**
 * @package Campsite
 *
 * @author Holman Romero <holman.romero@gmail.com>
 * @author Mugur Rus <mugur.rus@gmail.com>
 * @copyright 2007 MDLF, Inc.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version $Revision$
 * @link http://www.sourcefabric.org
 */

/**
 * Includes
 */
require_once($GLOBALS['g_campsiteDir'].'/template_engine/classes/CampSession.php');
require_once($GLOBALS['g_campsiteDir'].'/template_engine/classes/CampVersion.php');
require_once($GLOBALS['g_campsiteDir'].'/install/classes/CampTemplate.php');
require_once($GLOBALS['g_campsiteDir'].'/install/classes/CampInstallationBase.php');
require_once($GLOBALS['g_campsiteDir'].'/install/classes/CampInstallationView.php');

/**
 * Class CampInstallation
 */
final class CampInstallation extends CampInstallationBase
{
    /**
     * @var array
     */
    private $m_steps = array(
        'precheck' => array(
            'tplfile' => 'precheck.tpl',
            'title' => 'Pre-installation Check',
            'order' => 1
        ),
        'license' => array(
            'tplfile' => 'license.tpl',
            'title' => 'License',
            'order' => 2
        ),
        'database' => array(
            'tplfile' => 'database.tpl',
            'title' => 'Database Settings',
            'order' => 3
        ),
        'mainconfig' => array(
            'tplfile' => 'mainconfig.tpl',
            'title' => 'Main Configuration',
            'order' => 4
        ),
        'loaddemo' => array(
            'tplfile' => 'loaddemo.tpl',
            'title' => 'Sample Site',
            'order' => 5
        ),
        'cronjobs' => array(
            'tplfile' => 'cronjobs.tpl',
            'title' => 'Automated Tasks',
            'order' => 6
        ),
        'finish' => array(
            'tplfile' => 'finish.tpl',
            'title' => 'Finish',
            'order' => 7
        )
     );

    /**
     * @var array
     */
    private $m_lists = array();

    /**
     * @var string
     */
    private $m_title = null;

    /**
     * @var  object
     */
    private $m_version = null;


    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->m_os = self::GetHostOS();
        $this->m_version = new CampVersion();
    }


    public function execute()
    {
        parent::execute();

        return $this->m_step;
    }

    public function dispatch($p_step)
    {
        if (array_key_exists($p_step, $this->m_steps)) {
            $this->m_step = $p_step;
        } else {
            $this->m_step = $this->m_defaultStep;
        }

        $cVersion = new CampVersion();
        $this->m_title = $cVersion->getPackage().' '.$cVersion->getRelease();
        $this->m_title .= (strlen($cVersion->getDevelopmentStatus()) > 0) ? '-'.$cVersion->getDevelopmentStatus() : '';
        $this->m_title .= (strlen($cVersion->getCodeName()) > 0 && $cVersion->getCodeName() != 'undefined') ? ' [ '.$cVersion->getCodeName().' ]' : '';
        $this->m_title .= ' Installer';
    }


    public function initSession()
    {
        $session = CampSession::singleton();
    }

    public function render()
    {
        $tpl = CampTemplate::singleton();

        $tpl->assign('site_title', $this->m_title);
        $tpl->assign('message', $this->m_message);
        $tpl->assign('package', $this->m_version->getPackage());
        $tpl->assign('version', $this->m_version->getVersion());
        $tpl->assign('release_date', $this->m_version->getReleaseDate());
        $tpl->assign('organization', $this->m_version->getOrganization());
        $tpl->assign('copyright', $this->m_version->getCopyright());
        $tpl->assign('license', $this->m_version->getLicense());
        $tpl->assign('host_os', $this->m_os);
        $tpl->assign('current_step', $this->m_step);
        $tpl->assign('current_step_title', $this->m_steps[$this->m_step]['title']);
        $tpl->assign('step_titles', $this->m_steps);

        $session = CampSession::singleton();
        $config_db = $session->getData('config.db', 'installation');

        $files = array();
        foreach (new FilesystemIterator(__DIR__ . '/../sample_templates', FilesystemIterator::SKIP_DOTS) as $item) {
            if ($item->isDir()) {
                $files[] = $item->getBasename();
            }
        }

        $tpl->assign('sample_templates', $files);
        $tpl->assign('overwrite_db', $this->m_overwriteDb);

        $database_conf = dirname(__FILE__) . '/../../conf/database_conf.php';

        if (!empty($config_db)) {
            $tpl->assign('db', $config_db);
        } elseif (file_exists($database_conf)) { 
            // use predefined settings
            global $Campsite;
            require_once $database_conf;
            $tpl->assign('db', array(
                'hostname' => $Campsite['db']['host'],
                'hostport' => $Campsite['db']['port'],
                'username' => $Campsite['db']['user'],
                'userpass' => $Campsite['db']['pass'],
                'database' => $Campsite['db']['name'],
                'predefined' => TRUE,
            ));
        } else {
            $tpl->assign('db', array(
                'hostname' => 'localhost',
                'username' => 'root',
                'database' => 'newscoop',
                'hostport' => '',
                'userpass' => ''
            ));
        }

        $config_site = $session->getData('config.site', 'installation');
        if (!empty($config_site)) {
            $tpl->assign('mc', $config_site);
        } else {
            $tpl->assign( 'mc', array( 'sitetitle' => '', 'adminemail' => '' ) );
        }

        $config_demo = $session->getData('config.demo', 'installation');
        if (!empty($config_demo)) {
            $tpl->assign('dm', $config_demo);
        } else {
            $tpl->assign( 'dm', array( 'loaddemo' => '' ) );
        }

        $view = new CampInstallationView($this->m_step);

        $tpl->display($this->getTemplateName());
    }

    public static function GetHostOS()
    {
        $os = 'unsupported';

        if (strtoupper(PHP_OS) === 'LINUX') {
            $os = 'linux';
        } elseif (strtoupper(PHP_OS) === 'FREEBSD') {
            $os = 'freebsd';
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $os = 'windows';
        }

        return $os;
    }

    private function getTemplateName()
    {
        return $this->m_steps[$this->m_step]['tplfile'];
    }

}
