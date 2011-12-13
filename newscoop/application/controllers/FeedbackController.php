<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Feedback controller
 */

use Newscoop\Entity\Feedback;

require_once($GLOBALS['g_campsiteDir'].'/include/captcha/php-captcha.inc.php');
require_once($GLOBALS['g_campsiteDir'].'/include/get_ip.php');
require_once($GLOBALS['g_campsiteDir']. '/classes/Plupload.php');

class FeedbackController extends Zend_Controller_Action
{
    public function init()
    {
		$this->getHelper('contextSwitch')->addActionContext('save', 'json')->initContext();
		$this->getHelper('contextSwitch')->addActionContext('upload', 'json')->initContext();
    }

    public function saveAction()
    {
		global $_SERVER;

		$this->_helper->layout->disableLayout();
		$parameters = $this->getRequest()->getParams();

		$errors = array();

		$auth = Zend_Auth::getInstance();

		$publication = new Publication($parameters['f_publication']);

		if ($auth->getIdentity()) {
			$acceptanceRepository = $this->getHelper('entity')->getRepository('Newscoop\Entity\Comment\Acceptance');
			$user = new User($auth->getIdentity());

			$userIp = getIp();
			if ($acceptanceRepository->checkParamsBanned($user->m_data['Name'], $user->m_data['EMail'], $userIp, $parameters['f_publication'])) {
				$errors[] = $this->view->translate('You have been banned from writing feedbacks.');
			}
		}
		else {
			$errors[] = $this->view->translate('You are not logged in.');
		}

		if (!array_key_exists('f_feedback_content', $parameters) || empty($parameters['f_feedback_content'])) {
			$errors[] = $this->view->translate('Feedback content was not filled in.');
		}

		if (empty($errors)) {
			$feedbackRepository = $this->getHelper('entity')->getRepository('Newscoop\Entity\Feedback');
			$feedback = new Feedback();

			$values = array(
				'user' => $auth->getIdentity(),
				'publication' => $parameters['f_publication'],
				'section' => $parameters['f_section'],
				'article' => $parameters['f_article'],
				'subject' => $parameters['f_feedback_subject'],
				'message' => $parameters['f_feedback_content'],
				'url' => $parameters['f_feedback_url'],
				'time_created' => new DateTime(),
				'language' => $parameters['f_language'],
				'status' => 'pending',
				'attachment_type' => 'none',
				'attachment_id' => 0
			);

			if (isset($parameters['image_id'])) {
				$values['attachment_type'] = 'image';
				$values['attachment_id'] = $parameters['image_id'];

				$feedbackRepository->save($feedback, $values);
				$feedbackRepository->flush();

				$current_user = $this->_helper->service('user')->getCurrentUser();
                $this->_helper->service->notifyDispatcher("image.delivered", array('user' => $current_user));

				$this->sendMail($values);
                
                $this->view->response = $this->view->translate('File is uploaded and your message is sent.');
			}
			else if (isset($parameters['document_id'])) {
				$values['attachment_type'] = 'document';
				$values['attachment_id'] = $parameters['document_id'];

				$feedbackRepository->save($feedback, $values);
				$feedbackRepository->flush();

				$current_user = $this->_helper->service('user')->getCurrentUser();
                $this->_helper->service->notifyDispatcher("document.delivered", array('user' => $current_user));
                
                $this->sendMail($values);

				$this->view->response = $this->view->translate('File is uploaded and your message is sent.');
			}
			else {
				$feedbackRepository->save($feedback, $values);
				$feedbackRepository->flush();
                
                $this->sendMail($values);

				$this->view->response = $this->view->translate('Your message is sent.');
			}
		}
		else {
			$errors = implode('<br>', $errors);
			$errors = $this->view->translate('Following errors have been found:') . '<br>' . $errors;
			$this->view->response = $errors;
		}
    }

    public function uploadAction()
    {
		global $Campsite;

		$auth = Zend_Auth::getInstance();
		$userId = $auth->getIdentity();

		$_FILES['file']['name'] = preg_replace('/[^\w\._]+/', '', $_FILES['file']['name']);

		$mimeType = $_FILES['file']['type'];
		$type = explode('/', $mimeType);

		if ($type[0] == 'image') {
			$file = Plupload::OnMultiFileUploadCustom($Campsite['IMAGE_DIRECTORY']);
			$image = Image::ProcessFile($_FILES['file']['name'], $_FILES['file']['name'], $userId, array('Source' => 'feedback', 'Status' => 'Unapproved'));
			$this->view->response = $image->getImageId();
		}
		else if ($type[1] == 'pdf') {
			$attachment = new Attachment();
			$attachment->makeDirectories();
			
			$file = Plupload::OnMultiFileUploadCustom($attachment->getStorageLocation());
			$document = Attachment::ProcessFile($_FILES['file']['name'], $_FILES['file']['name'], $userId, array('Source' => 'feedback', 'Status' => 'Unapproved'));
			$this->view->response = $document->getAttachmentId();
		}
	}
    
    public function sendMail($values)
    {
        //$toEmail = 'dienstpult@tageswoche.ch';
        $toEmail = 'sebastian.simon@sourcefabric.org';
        
        $user = new User($values['user']);
        $fromEmail = $user->getEmail();
        
        $message = $values['message'];
        $message = $message.'<br>Von <a href="http://www.tageswoche.ch/user/profile/'.$user->getUsername().'">'.$user->getUsername().'</a> ('.$user->getRealName().')';
        $message = $message.'<br>Gesendet von: <a href="'.$values['url'].'">'.$values['url'].'</a>';
        
        $mail = new Zend_Mail('utf-8');
        
        $mail->setSubject('Leserfeedback: '.$values['subject']);
        $mail->setBodyHtml($message);
        $mail->setFrom($fromEmail);
        $mail->addTo($toEmail);
        
        if ($values['attachment_type'] == 'image') {
            $item = new Image($values['attachment_id']);
            $location = $item->getImageStorageLocation();
            $contents = file_get_contents($location);
            
            $filename = $item->getImageFileName();
            $tempFilename = explode('.', $filename);
            $extension = $tempFilename[count($tempFilename) - 1];
            
            $at = new Zend_Mime_Part($contents);
            if ($extension == 'gif') $at->type = 'image/gif';
            if ($extension == 'jpg' || $extension == 'jpeg') $at->type = 'image/jpeg';
            if ($extension == 'png') $at->type = 'image/png';
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_BASE64;
            $at->filename    = $filename;
             
            $mail->addAttachment($at);
        }
        else if ($values['attachment_type'] == 'document') {
            $item = new Attachment($values['attachment_id']);
            $location = $item->getStorageLocation();
            $contents = file_get_contents($location);
            
            $filename = $item->getFileName();
            
            $at = new Zend_Mime_Part($contents);
            $at->type = 'application/pdf';
            $at->disposition = Zend_Mime::DISPOSITION_INLINE;
            $at->encoding    = Zend_Mime::ENCODING_BASE64;
            $at->filename    = $filename;
             
            $mail->addAttachment($at);
        }
        
        try {
			$mail->send();
		}
		catch (Exception $e) {
		}
        echo(' ');
    }

    public function indexAction()
    {
		$this->view->param = $this->_getParam('switch');
	}
}
