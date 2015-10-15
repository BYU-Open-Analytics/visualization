<?php

use Phalcon\Mvc\Controller;

class ConsentController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Consent Required');
	}
	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		$this->view->consentEmail = $this->getDI()->getShared('config')->consent_email;
	}
}
