<?php

use Phalcon\Mvc\Controller;

class LTIInfoController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('LTI Session Info');
	}
	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
	}
}
