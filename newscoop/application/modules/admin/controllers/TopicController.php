<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * @Acl(resource="topic", action="manage")
 */
class Admin_TopicController extends Zend_Controller_Action
{
    public function saveOrderAction()
    {
        $tree = $this->_getParam('tree');
        $xml = simplexml_load_string('<ul>' . $tree . '</ul>');
        $this->_helper->service('tree')->save($xml);

        $this->_helper->flashMessenger(getGS("Order saved"));
        $this->_helper->redirector('', 'topics');
    }
}
