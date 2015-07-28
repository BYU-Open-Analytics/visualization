<?php

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Home');
	}
	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
	}
}
