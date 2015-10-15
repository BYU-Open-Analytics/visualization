<?php

use Phalcon\Mvc\Controller;

class InfoController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Consent Required');
	}
	public function controlAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		$this->view->consentEmail = $this->getDI()->getShared('config')->consent_email;
	}
	public function consentAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		$this->view->consentEmail = $this->getDI()->getShared('config')->consent_email;
	}
}
