<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 * @link http://www.sourcefabric.org
 */

class TageswocheQuickLinks extends Widget
{
    protected $title = 'TagesWoche Quick Links';

    protected $url = 'http://www.tageswoche.ch/';

    protected $items = array();

    protected $current_issue = null;


    /**
     * @return array
     */
    public function beforeRender()
    {
        $config = @parse_ini_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'quicklinks.ini');

        $items = array();
        foreach ($config as $item) {
            if ($item['issue'] == '__current__') {
                $item['issue'] = $this->getCurrentIssue($item['publication'], $item['language']);
            }

            $items[] = array(
                'label' => $item['label'],
                'link' => '/admin/articles/add.php?f_publication_id=' . $item['publication'] .
                    '&f_issue_number=' . $item['issue'] . '&f_section_number=' . $item['section'] .
                    '&f_language_id=' . $item['language'] . '&f_article_type=' . $item['type']
            );
        }

        $this->items = $items;
    }

    /**
     * @return void
     */
    public function render()
    {
        include_once dirname(__FILE__) . '/quicklinks.php'; 
    }

    /**
     * @return int
     */
    private function getCurrentIssue($publication, $language)
    {
        if (is_null($this->current_issue)) {
            $issue = \Issue::GetCurrentIssue($publication, $language);
            $this->current_issue = $issue->getIssueId();
        }

        return $this->current_issue;
    }
}
