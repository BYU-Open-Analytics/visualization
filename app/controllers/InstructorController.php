<?php

use Phalcon\Mvc\Controller;

class InstructorController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Instructor Dashboard');
		$this->view->pageTitle = 'Instructor Dashboard';

		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
	}

	public function indexAction() {
		$context = $this->getDI()->getShared('ltiContext');
		echo "Go to either /instructor/student_inspector or /instructor/class";
		$this->view->disable();
	}

	public function student_inspectorAction() {
		$this->tag->setTitle('Student Inspector Dashboard');
		$this->view->pageTitle = 'Student Inspector Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
	}

	public function classAction() {
		$this->tag->setTitle('Class Dashboard');
		$this->view->pageTitle = 'Class Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
	}
}
