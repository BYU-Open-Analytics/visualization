<?php

use Phalcon\Mvc\Controller;

class StatementsController extends Controller
{
	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = LTIContext::getContext($this->getDI()->getShared('config'));
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;

		// Pull the user's statements from the LRS (identification info from the LTI context)
		$config = $this->getDI()->getShared('config');

		$request = $config->lrs_endpoint.'api/v1/statements/aggregate?pipeline=[{"$match":{"voided":false, "statement.actor.mbox": "mailto:'.$context->getUserEmail().'"}}]';
		$session = curl_init($request);
		curl_setopt($session, CURLOPT_USERPWD, $config->lrs_username . ":" . $config->lrs_password);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($session);
		curl_close($session);

		//echo $request;
		//echo $response;

		//TODO some error checking here before passing data to view
		$parsed = json_decode($response)->result;
		$statements = array();
		if (count($parsed) > 0) {
			foreach ($parsed as $result) {
				$statements[] = $result->statement;
			}
		}

		//print_r($statements);
		//$this->view->disable();
		$this->view->statements = $statements;
	}
}
