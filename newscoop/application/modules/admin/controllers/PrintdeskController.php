<?php

/**
 * @package Newscoop
 * @subpackage Subscriptions
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 *
 *
 */

/**
 * @Acl(resource="user-group", action="manage")
 */
class Admin_PrintdeskController extends Zend_Controller_Action
{
    public function indexAction()
    {
		$user = new Zend_Session_Namespace('user');
        $user->allowPrindesk = true;
        $printdesk = (object)$this->getInvokeArg('bootstrap')->getOption('printdesk');
        $this->view->printdeskUrl = $printdesk->url;

	}
}