<?php

use Phalcon\Mvc\Controller;

class AssessmentStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Assessment Stats');
	}
	public function attempt_countsAction() {
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

		//Get number of question attempts
		$attempts = $statementHelper->getStatements("openassessments",'[{"$match":{"voided":false, "statement.actor.mbox": "mailto:'.$context->getUserEmail().'", "statement.verb.id":"http://adlnet.gov/expapi/verbs/attempted"}},{"$project":{"_id":1 }}]');
		if ($attempts["error"]) {
			$result []= ["error" => $attempts["error"]];
		} else {
			$result []= ["name" => "Question Attempts", "value" => count($attempts["statements"])];
		}

		//Get number of correct attempts
		$correctAttempts = $statementHelper->getStatements("openassessments",'[{"$match":{"voided":false, "statement.actor.mbox": "mailto:'.$context->getUserEmail().'", "statement.verb.id":"http://adlnet.gov/expapi/verbs/answered", "statement.result.success":true}},{"$project":{"_id":1 }}]');
		if ($correctAttempts["error"]) {
			$result []= ["error" => $correctAttempts["error"]];
		} else {
			$result []= ["name" => "Correct Attempts", "value" => count($correctAttempts["statements"])];
		}

		//Get number of incorrect attempts
		$incorrectAttempts = $statementHelper->getStatements("openassessments",'[{"$match":{"voided":false, "statement.actor.mbox": "mailto:'.$context->getUserEmail().'", "statement.verb.id":"http://adlnet.gov/expapi/verbs/answered", "statement.result.success":false}},{"$project":{"_id":1 }}]');
		if ($incorrectAttempts["error"]) {
			$result []= ["error" => $incorrectAttempts["error"]];
		} else {
			$result []= ["name" => "Incorrect Attempts", "value" => count($incorrectAttempts["statements"])];
		}

		echo json_encode($result);
	}

	public function confidence_countsAction() {
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

		//Get number of answer attempts
		$attempts = $statementHelper->getStatements("openassessments",'[{"$match":{"voided":false, "statement.actor.mbox": "mailto:'.$context->getUserEmail().'", "statement.verb.id":"http://adlnet.gov/expapi/verbs/answered"}},{"$project":{"_id":0, "statement.context.extensions":1 }}]');
		if ($attempts["error"]) {
			$result []= ["error" => $attempts["error"]];
		} else {
			foreach ($attempts["statements"] as $statement) {
				if (isset($statement->statement->context->extensions->{"http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level"})) {
					$level = $statement->statement->context->extensions->{"http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level"};
					if (isset($result[$level])) {
						$result[$level] += 1;
					} else {
						$result[$level] = 1;
					}
				}
			}
			echo json_encode($result);
		}
	}

	public function confidence_averageAction() {
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

		// Numerical confidence values
		$levelValue = ["low" => -1, "medium" => 0, "high" => 1];

		$confidences = ["overall" => array(), "correct" => array(), "incorrect" => array()];

		//Get all user answer attempts
		$attempts = $statementHelper->getStatements("openassessments",'[{"$match":{"voided":false, "statement.actor.mbox": "mailto:'.$context->getUserEmail().'", "statement.verb.id":"http://adlnet.gov/expapi/verbs/answered"}},{"$project":{"_id":0, "statement.context.extensions":1, "statement.result.success":1 }}]');
		if ($attempts["error"]) {
			$result []= ["error" => $attempts["error"]];
		} else {
			foreach ($attempts["statements"] as $statement) {
				if (isset($statement->statement->context->extensions->{"http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level"}) && isset($statement->statement->result->success)) {
					$level = $statement->statement->context->extensions->{"http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level"};
					$correct = $statement->statement->result->success;
					$confidences["overall"] []= $levelValue[$level];
					if ($correct == true) {
						$confidences["correct"] []= $levelValue[$level];
					} else {
						$confidences["incorrect"] []= $levelValue[$level];
					}
				}
			}
			foreach ($confidences as $name => $list) {
				$average = array_sum($list) / count($list);
				$result []= ["name" => $name, "value" => $average];
			}
			echo json_encode($result);
		}
	}
}
