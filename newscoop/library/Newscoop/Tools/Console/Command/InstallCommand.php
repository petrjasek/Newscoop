<?php
/**
 * @package Newscoop
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Tools\Console\Command;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputOption;
use Newscoop\Install\InstallConfig;
use Newscoop\Install\ConfigWriter;

/**
 * Install Newscoop Command
 */
class InstallCommand extends Console\Command\Command
{
    const ADMIN_PASSWORD = 'admin-password';
    const ADMIN_EMAIL = 'admin-email';
    const SITE_ALIAS = 'site-alias';
    const TEMPLATE_SET = 'template-set';

    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('install');
        $this->setDescription('Install Newscoop');
        $this->addOption(self::ADMIN_PASSWORD, null, InputOption::VALUE_OPTIONAL, 'Admin password?');
        $this->addOption(self::ADMIN_EMAIL, null, InputOption::VALUE_OPTIONAL, 'Admin email?');
        $this->addOption(self::SITE_ALIAS, null, InputOption::VALUE_OPTIONAL, 'Site alias?');
        $this->addOption(self::TEMPLATE_SET, null, InputOption::VALUE_OPTIONAL, 'Template set?');
    }

    /**
     * @see Console\Command\Command
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $config = new InstallConfig();
        $config->admin_password = $input->getOption(self::ADMIN_PASSWORD) ?: $config->admin_password;
        $config->admin_email = $input->getOption(self::ADMIN_EMAIL) ?: $config->admin_email;
        $config->alias = $input->getOption(self::SITE_ALIAS) ?: $config->alias;
        $config->template_set = $input->getOption(self::TEMPLATE_SET) ?: $config->template_set;

        $connection = $this->getConnection($config->db);
        $this->createSchema($connection);
        $this->saveConfig($config);
        $this->installTemplates($config->template_set);
        $this->setAdmin($config, $connection);
        $this->setAlias($config, $connection);
        $this->gc();
    }

    private function gc()
    {
        $upgrade = APPLICATION_PATH . '/../conf/upgrading.php';
        if (file_exists($upgrade)) {
            unlink($upgrade);
        }
    }

    private function setAdmin($config, $connection)
    {
        $sql = "UPDATE liveuser_users
                SET Password = SHA1(:password),
                    EMail = :email,
                    time_updated = NOW(),
                    time_created = NOW(),
                    status = :status,
                    is_admin = :is_admin
                WHERE id = :id";
        $connection->executeUpdate(
            $sql,
            array(
                'password' => $config->admin_password,
                'email' => $config->admin_email,
                'status' => 1,
                'is_admin' => 1,
                'id' => 1,
            )
        );
    }

    private function setAlias($config, $connection)
    {
        $sql = "UPDATE Aliases SET Name = :alias LIMIT 1";
        $connection->executeUpdate(
            $sql,
            array('alias' => $config->alias)
        );
    }

    private function getConnection(array $config)
    {
        $noDbConfig = array_merge($config, array('dbname' => null));
        $connection = \Doctrine\DBAL\DriverManager::getConnection($noDbConfig);
        $sm = $connection->getSchemaManager();

        if (in_array($config['dbname'], $sm->listDatabases())) {
            $sm->dropDatabase($config['dbname']);
        }

        $sm->createDatabase($config['dbname']);

        $connection = \Doctrine\DBAL\DriverManager::getConnection($config);

        $this->em = \Doctrine\ORM\EntityManager::create(
            $connection,
            \Zend_Registry::get('container')->get('em')->getConfiguration()
        );

        return $connection;
    }

    private function createSchema($connection)
    {
        $dir = APPLICATION_PATH . '/../install/sql';

        $inputs = array(
            'campsite_core.sql',
            'campsite_demo_tables.sql',
            'campsite_demo_prepare.sql',
            'campsite_demo_data.sql',
        );

        foreach ($inputs as $input) {
            $file = $dir . '/' . $input;
            $connection->exec(file_get_contents($file));
        }
    }

    private function installTemplates($set)
    {
        $themePath = $this->copyTemplates($set);
        $this->copyData($set);

        $resourceId = new \Newscoop\Service\Resource\ResourceId(__CLASS__);
        $themeService = $resourceId->getService(\Newscoop\Service\IThemeManagementService::NAME_1);
        $publicationService = $resourceId->getService(\Newscoop\Service\IPublicationService::NAME);

        foreach ($themeService->getUnassignedThemes() as $theme) {
        	foreach ($publicationService->getEntities() as $publication) {
        		$themeService->assignTheme($theme, $publication);
        	}
        }
    }

    private function copyTemplates($set)
    {
        $source = APPLICATION_PATH . '/../install/sample_templates/' . $set . '/templates';
        $dest = APPLICATION_PATH . '/../themes/unassigned/' . uniqid($set . '_');

        mkdir($dest);
        $this->copyr($source, $dest);
        return $dest;
    }

    private function copyData($set)
    {
        foreach (array('files', 'images') as $dir) {
            $source = APPLICATION_PATH . '/../install/sample_data/' . $dir;
            $dest = APPLICATION_PATH . '/' . $dir;
            $this->copyr($source, $dir);
        }
    }

    private function copyr($source, $dest)
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if (realpath($dest . '/' . $iterator->getSubPathName())) {
                continue;
            }

            if ($item->isDir()) {
                mkdir($dest . '/' . $iterator->getSubPathName());
            } else {
                copy($item, $dest . '/' . $iterator->getSubPathName());
            }
        }
    }

    private function saveConfig($config)
    {
        $configWriter = new ConfigWriter();
        $configWriter->write($config, APPLICATION_PATH . '/../conf/database_conf.php');
    }
}
