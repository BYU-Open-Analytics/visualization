<?php

use Phalcon\Mvc\Controller;

class StatementsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Statements');
	}
	public function indexAction() {
		error_reporting(E_ALL);
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;

		$error = "";
		$statements = Array();

		// Pull the user's statements from the LRS (identification info from the LTI context)
		$config = $this->getDI()->getShared('config');

		//$request = $config->lrs_endpoint.'api/v1/statements/aggregate?pipeline=[{"$match":{"voided":false, "statement.actor.mbox": "mailto:'.$context->getUserEmail().'"}}]';
		// Can't just include entire statement ("statement":1 in $project block), or learning locker php script will run out of memory)
		$request = $config->lrs_endpoint.'api/v1/reports/55b80ccd727c3de8098b4659/run';
		$session = curl_init($request);
		curl_setopt($session, CURLOPT_USERPWD, $config->lrs_username . ":" . $config->lrs_password);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($session);

		// Catch curl errors
		if (curl_errno($session)) {
			$error = "Curl error: " . curl_error($session);
		}

		curl_close($session);

		$parsed = json_decode($response);
		//print_r($statements);
		//$this->view->disable();
		$this->view->error = $error;
		$this->view->statements = $parsed;
	}
	
	// Returns number of question attempts
	public function questionAttemptsAction() {
		error_reporting(E_ALL);
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;

		if ($context->valid) {
			$statementHelper = new StatementHelper();
			$result = $statementHelper->getStatements("put lrs id here",'[{"$match":{"voided":false, "statement.actor.mbox": "mailto:'.$context->getUserEmail().'", "statement.verb.id":"http://adlnet.gov/expapi/verbs/attempted"}},{"$project":{"_id":0, "statement.result.sucess":1, "statement.timestamp":1 }}]');
			if ($result["error"]) {
				echo '{"error":'.$result["error"].'}';
			} else {
				echo '[{"name":"Question Attempts", "value":'.count($result["statements"]).'}]';
			}
		} else {
			echo '{"error":"Invalid lti context"}';
		}
		
		$this->view->disable();
	}
}
