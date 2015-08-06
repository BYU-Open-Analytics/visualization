<?php

use Phalcon\Mvc\Controller;

class AyamelStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Ayamel Statements');
	}
	public function indexAction() {
		error_reporting(E_ALL);
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;

		if ($context->valid) {
			// This contains our different data elements
			$result = Array();
			$statementHelper = new StatementHelper();

			$verbs = Array();

			//Get all verbs of statements
			$statements = $statementHelper->getStatements("ayamel",'[{"$match":{"voided":false}},{"$project":{"statement.verb.display.en-US":1}}]');
			if ($statements["error"]) {
				$result []= ["error" => $attempts["error"]];
			} else {
				//Count how many we have of each verb
				foreach ($statements["statements"] as $statement) {
					$verb = $statement->statement->verb->display->{"en-US"};
					if (isset($verbs[$verb])) {
						$verbs[$verb] += 1;
					} else {
						$verbs[$verb] = 1;
					}
					//echo($statement->statement->verb->display->{"en-US"}."<br>");
				}
				//Only include if it has at least 5% of total statements
				$inclusionThreshold = count($statements["statements"]) * 0.05;
				//Put the verb counts into the format we want in d3
				foreach ($verbs as $verb => $count) {
					if ($count > $inclusionThreshold) {
						$result []= ["name" => $verb, "value" => $count];
					}
				}
			}

			echo json_encode($result);
		} else {
			echo '[{"error":"Invalid lti context"}]';
		}
		
		$this->view->disable();
	}
}
