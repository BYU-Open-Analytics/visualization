<?php

use Phalcon\Mvc\Controller;

class ContentRecommenderStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Assessment Stats');
	}
	public function questions_tableAction() {
		// TODO take out error reporting for production
		ini_set('log_errors', 1);
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

		$questions = array();

		//Get all attempts for the user
		$attempts = $statementHelper->getStatements("openassessments",[
			'statement.actor.mbox' => 'mailto:'.$context->getUserEmail(),
			'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
			], [
			'statement.object' => true,
			'statement.result.success' => true,
			]);
		if ($attempts["error"]) {
			$result []= ["error" => $attempts["error"]];
		} else {
			foreach ($attempts["cursor"] as $statement) {
				// Since we're now doing more than just counting, we need to do processing that Learning Locker normally would first:
				$statement = StatementHelper::replaceHtmlEntity($statement, true);
					//print_r($statement);
				if (isset($statement['statement']['object']['id'])) {
					$id = $statement['statement']['object']['id'];
					// Add this to our list of attempted questions, or increase the count
					if (isset($questions[$id])) {
						$questions[$id]['attempts'] += 1;
					} else {
						$questions[$id] = ['attempts'=>1, 'correct'=>false];
					}
					// We only need to know if they ever got this question right, so if any attempt is correct, mark it
					if ($statement['statement']['result']['success'] == true) {
						$questions[$id]['correct'] = true;
					}
				}
			}
			foreach ($questions as $id => $q) {
				$result []= ['id' => $id, 'attempts' => $q['attempts'], 'correct' => $q['correct']];
			}
			// Sort the results with highest number of attempts first
			usort($result, function($a, $b) {
				return $b['attempts'] - $a['attempts'];
			});
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

		$userConfidences = ["overall" => array(), "correct" => array(), "incorrect" => array()];
		$classConfidences = ["overall" => array(), "correct" => array(), "incorrect" => array()];

		//Get all user answer attempts
		$attempts = $statementHelper->getStatements("openassessments",[
			'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
			], [
			'_id' => false,
			'statement.actor.mbox' => true,
			'statement.context.extensions' => true,
			'statement.result.success' => true,
			]);
		if ($attempts["error"]) {
			$result []= ["error" => $attempts["error"]];
		} else {
			foreach ($attempts["cursor"] as $statement) {
				// Since we're now doing more than just counting, we need to do processing that Learning Locker normally would first:
				$statement = StatementHelper::replaceHtmlEntity($statement, true);
				if (isset($statement['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level']) && isset($statement['statement']['result']['success'])) {
					$level = $statement['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level'];
					$correct = $statement['statement']['result']['success'];
					//Put this in user confidences if necessary
					if (isset($statement['statement']['actor']['mbox']) && $statement['statement']['actor']['mbox'] == 'mailto:'.$context->getUserEmail()) {
						$userConfidences["overall"] []= $levelValue[$level];
						if ($correct == true) {
							$userConfidences["correct"] []= $levelValue[$level];
						} else {
							$userConfidences["incorrect"] []= $levelValue[$level];
						}
					}
					// Then always in class confidences
					$classConfidences["overall"] []= $levelValue[$level];
					if ($correct == true) {
						$classConfidences["correct"] []= $levelValue[$level];
					} else {
						$classConfidences["incorrect"] []= $levelValue[$level];
					}
				}
			}
			// User stats
			foreach ($userConfidences as $name => $list) {
				//Avoid division by 0
				$average = (count($list) > 0) ? (array_sum($list) / count($list)) : 0;
				$result['user'][$name] = $average;
				//$result []= ["name" => $name, "value" => $average];
			}
			// Class stats
			foreach ($classConfidences as $name => $list) {
				//Avoid division by 0
				$average = (count($list) > 0) ? (array_sum($list) / count($list)) : 0;
				$result['class'][$name] = $average;
				//$result []= ["name" => $name, "value" => $average];
			}
			echo json_encode($result);
		}
	}
}

