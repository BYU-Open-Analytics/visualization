<?php

use Phalcon\Mvc\Controller;

// The actions here just set up the LTI context and pass it to the view. The view is largely boilerplate, and then data (coming from StudentInspectorStatsController or ClassStatsController) is loaded by the javascript via ajax.

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
		$this->view->ltiContext = $context;
	//	echo "Go to either /instructor/student_inspector or /instructor/class";
		//$this->view->disable();
		$this->response->redirect("./instructor/class");
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
