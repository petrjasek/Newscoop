<?php
/**
 * @package Newscoop
 * @copyright 2012 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Search;

use Newscoop\View\ArticleView;
use Newscoop\Http\ClientFactory;

/**
 * Solr Index
 */
class SolrIndex implements Index
{
    /**
     * @var Newscoop\Http\ClientFactory
     */
    private $clientFactory;

    /**
     * @var array
     */
    private $commands = array();

    /**
     * @var array
     */
    private $config = array(
        'update_url' => 'http://localhost:8983/solr/{core}/update',
    );

    /**
     * @param Newscoop\Http\ClientFactory $clientFactory
     * @param array $config
     */
    public function __construct(ClientFactory $clientFactory, array $config)
    {
        $this->clientFactory = $clientFactory;
        $this->config = (object) array_merge($this->config, $config);
    }

    /**
     * @inheritdoc
     */
    public function add(ArticleView $article)
    {
        $this->initCommands($article->language);
        $this->commands[$article->language][] = new AddCommand($article);
    }

    /**
     * @inheritdoc
     */
    public function delete(ArticleView $article)
    {
        $this->initCommands($article->language);
        $this->commands[$article->language][] = new DeleteCommand($article);
    }

    /**
     * Init commands array for given core
     *
     * @param string $core
     * @return void
     */
    private function initCommands($core)
    {
        if (!array_key_exists($core, $this->commands)) {
            $this->actions[$core] = array();
        }
    }

    /**
     * @inheritdoc
     */
    public function commit()
    {
        foreach (array_keys($this->commands) as $core) {
            $client = $this->clientFactory->createClient();
            $response = $client->post(
                array($this->config->update_url, array('core' => $core)),
                array('Content-Type' => 'text/json'),
                $this->getUpdateJson($core)
            )->send();

            if (!$response->isSuccessful()) {
                throw new SolrException();
            }
        }
    }

    /**
     * Get update json
     *
     * @param string $core
     * @return string
     */
    private function getUpdateJson($core)
    {
        $commands = array_map('strval', $this->commands[$core]);
        return sprintf('{%s}', implode(',', $commands));
    }
}
