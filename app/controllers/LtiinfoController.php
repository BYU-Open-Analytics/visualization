<?php

use Phalcon\Mvc\Controller;

class LTIInfoController extends Controller
{
	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = LTIContext::getContext($this->getDI()->getShared('config'));
		$this->view->context = $context;
	}
}
