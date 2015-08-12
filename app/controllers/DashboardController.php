<?php

use Phalcon\Mvc\Controller;

class DashboardController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Visualization Dashboard');
	}
	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
	}
}
