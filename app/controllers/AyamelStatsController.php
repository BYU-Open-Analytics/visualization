<?php

use Phalcon\Mvc\Controller;

class AyamelStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Ayamel Statements');
	}
	public function verb_countsAction() {
		// TODO take out error reporting for production
		error_reporting(E_ALL);
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}

		// This contains our different data elements
		$result = Array();
		$statementHelper = new StatementHelper();

		// This contains our different data elements
		$result = Array();
		$statementHelper = new StatementHelper();

		$verbs = Array();

		//Get verbs of all statements
		//$statements = $statementHelper->getStatements("ayamel",'[{"$match":{"voided":false}},{"$project":{"statement.verb.display.en-US":1}}]');
		$statements = $statementHelper->getStatements("ayamel",['voided'=>false], ['_id'=>false, 'statement.verb.display.en-US'=>true]);
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
			//Only include if it has at least 3% of total statements
			$inclusionThreshold = count($statements["statements"]) * 0.03;
			//Put the verb counts into the format we want in d3
			foreach ($verbs as $verb => $count) {
				if ($count > $inclusionThreshold) {
					$result []= ["name" => str_replace("_"," ",$verb), "value" => $count];
				}
			}
		}

		echo json_encode($result);
	}
}
