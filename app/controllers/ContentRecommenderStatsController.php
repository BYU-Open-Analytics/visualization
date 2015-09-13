<?php

use Phalcon\Mvc\Controller;
include __DIR__ . "/../library/array_column.php";

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
					if (isset($statement['statement']['result']['success']) && $statement['statement']['result']['success'] == true) {
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
				$id_explosion = explode("#",$id);
				$q_id = end($id_explosion);
				// Make sure the question text exists before setting it
				// Avoid off-by-one error. The question id from statement object id will be 1 to n+1
				$question_text = isset($question_texts[$a_id][$q_id-1]) ? $question_texts[$a_id][$q_id-1] : "Error getting question text for $a_id#$q_id";

				$result []= ['assessment_id' => $a_id, 'question_id' => $q_id, 'attempts' => $q['attempts'], 'correct' => $q['correct'], 'text' => $question_text];
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

	// Returns list of strongest and weakest concepts
	public function conceptsAction() {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}

		$strongest = [
			["id" => 1, "display" => "Chapter 1 - Concept A", "score" => rand(0,50) / 10],
			["id" => 2, "display" => "Chapter 1 - Concept B", "score" => rand(0,50) /10],
			["id" => 3, "display" => "Chapter 1 - Concept C", "score" => rand(0,50) /10],
		];
		$weakest = [
			["id" => 4, "display" => "Chapter 1 - Concept D", "score" => rand(50,100) /10],
			["id" => 5, "display" => "Chapter 1 - Concept E", "score" => rand(50,100) /10],
			["id" => 6, "display" => "Chapter 2 - concept A", "score" => rand(50,100) /10],
		];
		$result = ["strongest" => $strongest, "weakest" => $weakest];
		echo json_encode($result);
	}

	// Returns content recommendations in 4 groups:
		// Try these quiz questions (Group 1)
		// Watch these videos before attempting these quiz questions (Group 2)
		// Find additional help (Group 3)
		// Practice these questions again (Group 4)
	public function recommendationsAction($scope = "weakest") {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		$group1 = [];
		$group2 = [];
		$group3 = [];
		$group4 = [];
		// By default, only show recommendations for weakest concepts. If parameter is for all, then show recommendations for all concepts.
		$count = 6;
		if ($scope == "all") {
			$count = 30;
		}
		for ($i=0; $i<$count; $i++) {
			$group1 [] = ["conceptId" => 4, "assessment_id" => 1, "question_id" => 1, "display" => "Chapter 1 - Concept D - Quiz Question 1", "correct" => false, "attempts" => 0, "classPercentCorrect" => 60, "classAverageAttempts" => 5];
			$group2 [] = ["conceptId" => 4, "assessment_id" => 3, "question_id" => 1, "display" => "Chapter 1 - Concept D - Quiz Question 1", "correct" => true, "attempts" => rand(0,10), "classPercentCorrect" => 60, "classAverageAttempts" => 5];
			$group3 [] = ["conceptId" => 4, "assessment_id" => 3, "question_id" => 1, "display" => "Chapter 1 - Concept D - Quiz Question 1", "correct" => true, "attempts" => 10, "classPercentCorrect" => 60, "classAverageAttempts" => 5];
			$group4 [] = ["conceptId" => 4, "assessment_id" => 3, "question_id" => 1, "display" => "Chapter 1 - Concept D - Quiz Question 1", "correct" => true, "attempts" => 10, "classPercentCorrect" => 60, "classAverageAttempts" => 5];
		}
		$result = [
			"group1" => $group1,
			"group2" => $group2,
			"group3" => $group3,
			"group4" => $group4,
		];
		echo json_encode($result);
	}


	// Returns an array of points for the concept mastery scatterplot
	public function scatterplotAction($scope = 'all', $groupingId = '') {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}

		// Get the list of questions for videos associated with concepts for the given scope and grouping ID
		// We have to do it this way, because questions are only associated with videos.
		$questions = [];
		$videos = CSVHelper::parseWithHeaders('csv/video_concept_question.csv');
		switch ($scope) {
			case "concept":
				// Filter based on chapter
				foreach ($videos as $v) {
					if ($v["concept_number"] == $groupingId) {
						// Add these questions to our list
						$questions = array_merge($questions, explode(",", $v["questions"]));
					}
				}
				break;

			case "chapter":
				// Filter based on chapter
				foreach ($videos as $v) {
					if ($v["chapter_number"] == $groupingId) {
						$questions = array_merge($questions, explode(",", $v["questions"]));
					}
				}
				break;
			case "unit":
				// Filter based on unit
				// Unit number isn't given in this mapping, so get unit -> chapter mapping
				$units = CSVHelper::parseWithHeaders('csv/unit_chapter.csv');
				$unit = $units[array_search($groupingId, array_column($units, 'unit_number'))];
				// Get chapters that are in this unit
				$correspondingChapters = explode(",", $unit["chapters"]);

				foreach ($videos as $v) {
					// Check if this video is in a chapter that is in this unit
					if (in_array($v["chapter_number"], $correspondingChapters)) {
						// Add these questions to our list
						$questions = array_merge($questions, explode(",", $v["questions"]));
					}
				}
				break;
			case "all":
				// Add all questions to list
				foreach ($videos as $v) {
					// Add these questions to our list
					$questions = array_merge($questions, explode(",", $v["questions"]));
				}
				break;
		}

		// Remove duplicate questions (if question is associated with more than one video, only show it once)
		$uniqueQuestions = array_unique($questions);

		// Array of questions with more details about each
		$questionDetails = [];

		// Calculate 1. question attempts and 2. video watch amount for each question
		// 1. Question attempts
		// First get the assessment id for the given question
		$assessmentIds = CSVHelper::parseWithHeaders('csv/quiz_assessmentid.csv');
		foreach ($questions as $q) {
			$questionParts = explode(".", $q);
			// TODO Error checking for things like "Missing quiz" that are in the mappings
			if (count($questionParts) != 2) {
				continue;
			}
			$quizNumber = explode(".", $q)[0];
			$questionNumber = explode(".", $q)[1];
			$assessmentId = $assessmentIds[array_search($quizNumber, array_column($assessmentIds, 'quiz_number'))]["assessment_id"];

			$questionDetails []= ["quizNumber" => $quizNumber, "questionNumber" => $questionNumber, "assessmentId" => $assessmentId];
		}
		

		function randomPoint($group) {
			// Randomly return outliers
			if (rand(0,30) == 5) {
				return [$group, "qid", "aid", rand(-10000, 1000), rand(-10000, 1000)];
			}
			return [$group, "qid", "aid", rand(-100, 100) / 10, rand(-100, 100) / 10];
		}
		$result = [];
		// For now, return random points based on number of questions
		$numPoints = count($questionDetails);
		//foreach ($questionDetails as $q) {
			//$result [] = 
		//}
		for ($i=0; $i<$numPoints; $i++) {
			$result []= randomPoint("student");
		}
		for ($i=0; $i<($numPoints*10); $i++) {
			$result []= randomPoint("class");
		}

		$xValues = array_map(function($point) { return $point[3]; }, $result);
		$yValues = array_map(function($point) { return $point[4]; }, $result);

		// TODO check that xValues and yValues have a length, otherwise statshelper will spit out errors
		// Perform some statistics grossness
			// Remove any outliers for both axes, based on 1.5*IQR
			// Cap and floor x outliers
			$xStats = StatsHelper::boxPlotValues($xValues);
			$result = array_map(function($point) use ($xStats) {
				$x = $point[3];
				// Floor upper outliers
				if ($x > $xStats['q3'] + (1.5 * $xStats['iqr'])) {
					$x = $xStats['q3'] + (.5 * $xStats['iqr']);
				}
				// Cap lower outliers
				if ($x < $xStats['q1'] - (1.5 * $xStats['iqr'])) {
					$x = $xStats['q1'] - (.5 * $xStats['iqr']);
				}
				$point[3] = $x;
				return $point;
			}, $result);
			// Scale all the scores from 0 to 10
			// TODO

			// Cap and floor y outliers
			$yStats = StatsHelper::boxPlotValues($yValues);
			$result = array_map(function($point) use ($yStats) {
				$y = $point[4];
				// Floor upper outliers
				if ($y > $yStats['q3'] + (1.5 * $yStats['iqr'])) {
					$y = $yStats['q3'] + (.5 * $yStats['iqr']);
				}
				// Cap lower outliers
				if ($y < $yStats['q1'] - (1.5 * $yStats['iqr'])) {
					$y = $yStats['q1'] - (.5 * $yStats['iqr']);
				}
				$point[4] = $y;
				return $point;
			}, $result);
			//print_r($result);
			//print_r($yStats);
		
		//die();
		// Output data as csv so that we only have to send header information once for so many points
		header("Content-Type: text/csv");
		$output = fopen("php://output", "w");
		fputcsv($output, ["group", "question_id", "assessment_id", "x", "y"]);
		foreach ($result as $row) {
			fputcsv($output, $row); // here you can change delimiter/enclosure
		}
		fclose($output);
	}

	public function masteryGraphAction($scope = 'all', $groupingId = '') {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}

		$result = [];
		//echo $scope . ":" . $groupingId;

		// Get the list of concepts for the given scope and grouping ID
		$concepts = CSVHelper::parseWithHeaders('csv/concept_chapter_unit.csv');
		switch ($scope) {
			case "chapter":
				// Filter concepts to ones in the selected chapter
				$concepts = array_filter($concepts, function($concept) use ($groupingId) {
					return ($concept["chapter_number"] == $groupingId);
				});
				break;
			case "unit":
				// Filter concepts to ones in the selected unit
				$concepts = array_filter($concepts, function($concept) use ($groupingId) {
					return ($concept["unit_number"] == $groupingId);
				});
				break;
			// If scope is "all", then all concepts are already in the list; no need to filter
		}

		foreach ($concepts as $c) {
			$result []= ["id" => $c["concept_number"], "display" => $c["concept_title"], "score" => (rand(0,100) / 10)];
		}
		echo json_encode($result);
	}

}


