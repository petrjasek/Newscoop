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
use Newscoop\Install\ConnectionManager;

/**
 * Install Newscoop Command
 */
class InstallCommand extends Console\Command\Command
{
    const ADMIN_PASSWORD = 'admin-password';
    const ADMIN_EMAIL = 'admin-email';
    const SITE_ALIAS = 'site-alias';
    const TEMPLATE_SET = 'template-set';
    const DB_OVERWRITE = 'overwrite-db';
    const DB_NAME = 'db-name';
    const DB_HOST = 'db-host';
    const DB_PORT = 'db-port';
    const DB_USER = 'db-user';
    const DB_PASSWORD = 'db-password';

    /**
     * @see Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('install');
        $this->setDescription('Install Newscoop');
        $this->addOption(self::ADMIN_PASSWORD, null, InputOption::VALUE_OPTIONAL, 'Admin password', 'admin');
        $this->addOption(self::ADMIN_EMAIL, null, InputOption::VALUE_OPTIONAL, 'Admin email', 'admin@localhost');
        $this->addOption(self::SITE_ALIAS, null, InputOption::VALUE_OPTIONAL, 'Site alias', 'localhost');
        $this->addOption(self::TEMPLATE_SET, null, InputOption::VALUE_OPTIONAL, 'Template set', 'the_new_custodian');
        $this->addOption(self::DB_OVERWRITE, null, InputOption::VALUE_OPTIONAL, 'Overwrite database', false);
        $this->addOption(self::DB_NAME, null, InputOption::VALUE_OPTIONAL, 'Database name', 'newscoop');
        $this->addOption(self::DB_HOST, null, InputOption::VALUE_OPTIONAL, 'Database host', 'localhost');
        $this->addOption(self::DB_PORT, null, InputOption::VALUE_OPTIONAL, 'Database port', 3306);
        $this->addOption(self::DB_USER, null, InputOption::VALUE_OPTIONAL, 'Database user', 'root');
        $this->addOption(self::DB_PASSWORD, null, InputOption::VALUE_OPTIONAL, 'Database password', '');
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
        $config->overwrite_database = $input->getOption(self::DB_OVERWRITE) ?: $config->overwrite_database;
        $config->db['dbname'] = $input->getOption(self::DB_NAME) ?: $config->db['dbname'];
        $config->db['user'] = $input->getOption(self::DB_USER) ?: $config->db['user'];
        $config->db['password'] = $input->getOption(self::DB_PASSWORD) ?: $config->db['password'];
        $config->db['host'] = $input->getOption(self::DB_HOST) ?: $config->db['host'];
        $config->db['port'] = $input->getOption(self::DB_PORT) ?: $config->db['port'];

        $connection = ConnectionManager::getConnection($config->db, $config->overwrite_database);
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
        return;
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
            $dest = APPLICATION_PATH . '/../' . $dir;
            $this->copyr($source, $dest);
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
