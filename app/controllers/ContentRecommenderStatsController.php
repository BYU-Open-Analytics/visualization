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
		// Get the Open Assessments API endpoint from config
		$assessments_endpoint = $this->getDI()->getShared('config')->openassessments_endpoint;

		// This contains our different data elements
		$result = Array();
		$statementHelper = new StatementHelper();

		$questions = array();
		$assessment_list = array();

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
			// Calculate number of attempts and correct for each question
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
					// Add this question's assessment id to the list of assessments we need to get question text for
					preg_match('/assessments\/(.*)\.xml/', $id, $matches);
					$assessment_list []= end($matches);
				}
			}
			// Fetch question texts for all questions in the assessments
			$assessment_ids = ["assessment_ids" => array_keys(array_flip($assessment_list))];
			$request = $assessments_endpoint."api/question_text";
			$session = curl_init($request);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_POST, 1);
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($assessment_ids));

			$response = curl_exec($session);

			// Catch curl errors
			if (curl_errno($session)) {
				$error = "Curl error: " . curl_error($session);
			}
			curl_close($session);

			$question_texts = json_decode($response, true);

			foreach ($questions as $id => $q) {
				// Get assessment id
				preg_match('/assessments\/(.*)\.xml/', $id, $matches);
				$a_id= end($matches);
				// Avoid off-by-one error. The question id from statement object id will be 1 to n+1
				$q_id = end(explode("#",$id)) - 0;
				// Make sure the question text exists before setting it
				//echo $question_texts[$a_id][$q_id]." \n";
				$question_text = isset($question_texts[$a_id][$q_id]) ? $question_texts[$a_id][$q_id] : "Error getting question text for $id";
				$result []= ['id' => $id, 'attempts' => $q['attempts'], 'correct' => $q['correct'], 'text' => $question_text];
			}
			// Sort the results with highest number of attempts first
			usort($result, function($a, $b) {
				return $b['attempts'] - $a['attempts'];
			});
			echo json_encode($result);
		}
	}

	public function videos_tableAction() {
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

		$videos = array();

		//Get all video statements for the user
		$statements = $statementHelper->getStatements("ayamel",[
			'statement.actor.mbox' => 'mailto:'.$context->getUserEmail(),
			], [
			'statement.verb.id' => true,
			'statement.object' => true,
			'statement.result.success' => true,
			]);
		if ($statements["error"]) {
			$result []= ["error" => $statements["error"]];
		} else {
			foreach ($statements["cursor"] as $statement) {
				// Since we're now doing more than just counting, we need to do processing that Learning Locker normally would first:
				$statement = StatementHelper::replaceHtmlEntity($statement, true);
					//print_r($statement);
				if (isset($statement['statement']['object']['id'])) {
					$id = $statement['statement']['object']['id'];
					// Add this to our list of attempted questions, or increase the count
					if (isset($videos[$id])) {
						$videos[$id]['statements'] []= $statement['statement'];
					} else {
						$videos[$id] = ['statements' => [$statement['statement']]];
					}
					// We only need to know if they ever got this question right, so if any attempt is correct, mark it
					//if ($statement['statement']['result']['success'] == true) {
						//$questions[$id]['correct'] = true;
					//}
				}
			}

			// Now we've got an array of video ids and all their statements
			// TODO actually calculate what percentage of the video they've watched, not just the furthest point they got to
			//print_r($videos);
			foreach ($videos as $id => $vid) {
				$furthestPoint = 0;
				foreach ($vid['statements'] as $st) {
					$furthestPoint = max($st['object']['definition']['extensions']['https://ayamel.byu.edu/playerTime'] , $furthestPoint);
				}
				$result []= ['id' => $id, 'furthestPoint' => $furthestPoint];
			}
			//foreach ($questions as $id => $q) {
				//$result []= ['id' => $id, 'attempts' => $q['attempts'], 'correct' => $q['correct']];
			//}
			// Sort the results with highest number of attempts first
			//usort($result, function($a, $b) {
				//return $b['attempts'] - $a['attempts'];
			//});
			echo json_encode($result);
		}
	}

}

