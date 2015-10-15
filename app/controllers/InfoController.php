<?php

use Phalcon\Mvc\Controller;

class InfoController extends Controller
{
	public function controlAction() {
		$this->tag->setTitle('Consent Required');
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		$this->view->consentEmail = $this->getDI()->getShared('config')->consent_email;
	}
	public function consentAction() {
		$this->tag->setTitle('Check Back Later');
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		$this->view->consentEmail = $this->getDI()->getShared('config')->consent_email;
	}
}
