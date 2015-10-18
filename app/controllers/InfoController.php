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
	public function consentAction() {
		$this->tag->setTitle('Check Back Later');
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
}
