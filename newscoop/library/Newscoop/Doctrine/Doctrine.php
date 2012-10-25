<?php
/**
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Doctrine;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

/**
 * Doctrine Configuration
 */
class Doctrine
{
    /** @var Doctrine\ORM\EntityManager */
    private $em;
    private $options;

    public function __construct($options) 
    {
        $this->options = $options['doctrine'];
    }

    /**
     * Get Entity Manager
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        if ($this->em !== null && $this->em->isOpen()) {
            return $this->em;
        }

        return $this->_configureEntityManager();
    }

    /**
     * Configure doctrine entity manager
     * 
     * @return Doctrine\ORM\EntityManager
     */
    private function _configureEntityManager() 
    {
        global $Campsite;
        
        $config = new Configuration();
        
        // set annotations reader
        $metadata = $config->newDefaultAnnotationDriver(realpath($this->options['entity']['dir']));
        $config->setMetadataDriverImpl($metadata);

        // set proxy
        $config->setProxyDir(realpath($this->options['proxy']['dir']));
        $config->setProxyNamespace($this->options['proxy']['namespace']);
        $config->setAutoGenerateProxyClasses($this->options['proxy']['autogenerate']);

        // set cache
        $cache = new $this->options['cache'];
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        $config_file = APPLICATION_PATH . '/../conf/database_conf.php';
        if (empty($Campsite) && is_readable($config_file)) {
            require_once $config_file;
        }

        // set database
        $database = array(
            'driver' => 'pdo_mysql',
            'host' => $Campsite['DATABASE_SERVER_ADDRESS'],
            'dbname' => $Campsite['DATABASE_NAME'],
            'user' => $Campsite['DATABASE_USER'],
            'password' => $Campsite['DATABASE_PASSWORD'],
            'charset' => 'UTF8',
        );

        if (isset($this->options['database'])) {
            $database = $this->options['database'];
        }

        foreach ($this->options['functions'] as $function => $value) {
            $config->addCustomNumericFunction(strtoupper($function), $value);
        }

        $this->em = EntityManager::create($database, $config);

        // fix http://wildlyinaccurate.com/doctrine-2-resolving-unknown-database-type-enum-requested
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('enum', 'string');
        $platform->registerDoctrineTypeMapping('point', 'string');
        $platform->registerDoctrineTypeMapping('geometry', 'string');

        return $this->em;
    }
}
