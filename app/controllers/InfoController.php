<?php

use Phalcon\Mvc\Controller;

// Simple information pages used for the study and for development purposes

class InfoController extends Controller
{
	// Information page shown to students who are currently in the control group
	public function controlAction() {
		$this->tag->setTitle('Check Back Later');
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		$this->view->consentEmail = $this->getDI()->getShared('config')->consent_email;

		// Figure out the date that the student will be able to access the dashboard
		$current = Date('Y-m-d');
		$period1Start = Date('Y-m-d', strtotime("October 22"));
		$period2Start = Date('Y-m-d', strtotime("November 14"));
		$period3Start = Date('Y-m-d', strtotime("December 5"));
		if ($current >= $period2Start) {
			// Period 2
			$this->view->accessDate = "December&nbsp;5th";
		} else {
			// Period 1
			$this->view->accessDate = "November&nbsp;14th";
		}

		// Send a statement tracking that they viewed this page
		$statementHelper = new StatementHelper();
		$statement = $statementHelper->buildStatement([
			"statementName" => "interacted",
			"dashboardID" => "info",
			"dashboardName" => "Info",
			"verbName" => "launched",
			"objectName" => "controlGroup",
		], $context);
		if ($statement) {
			$statementHelper->sendStatements("visualization", [$statement]);
		}
	}

	// Information page shown to students who have not consented to be part of the study
	public function consentAction() {
		$this->tag->setTitle('Consent Required');
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		$this->view->consentEmail = $this->getDI()->getShared('config')->consent_email;

		// Send a statement tracking that they viewed this page
		$statementHelper = new StatementHelper();
		$statement = $statementHelper->buildStatement([
			"statementName" => "interacted",
			"dashboardID" => "info",
			"dashboardName" => "Info",
			"verbName" => "launched",
			"objectName" => "consentRequired",
		], $context);
		if ($statement) {
			$statementHelper->sendStatements("visualization", [$statement]);
		}
	}

	// Privacy policy page that we need to use google analytics
	public function privacyAction() {
		$this->tag->setTitle("Privacy Policy");
	}

	// Some pages useful for debugging things in development

	// View dumps out LTI session and launch information
	public function lti_infoAction() {
		// Send the context to the view
		$this->view->context = $this->getDI()->getShared('ltiContext');
	}

	// Shows recent xAPI statements from the current user
	public function recent_statementsAction() {
		// Get our context and check that it's valid
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
		if (!$context->valid) {
			return;
		}

		$statementHelper = new StatementHelper();

		// Get all statements for current user
		$attempts = $statementHelper->getStatements("",['statement.actor.name'=>$context->getUserName()],[]);
		// Get most recent statements first, and only get 100. (The query hasn't actually been run on the server yet, so we can still add more options)
		$cursor = $attempts["cursor"]->sort(['statement.timestamp' => -1]);
		$cursor = $attempts["cursor"]->limit(100);
		// Send result cursor to view
		$this->view->statements = $cursor;
	}
}
