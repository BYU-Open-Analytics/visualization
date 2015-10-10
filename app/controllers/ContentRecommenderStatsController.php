<?php

use Phalcon\Mvc\Controller;
include __DIR__ . "/../library/array_functions.php";

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
		// Get the list of concepts and (for now) randomly choose some for strongest and weakest
		$concepts = CSVHelper::parseWithHeaders('csv/concept_chapter_unit.csv');

		$conceptIndices = count($concepts) - 1;
		$strongest = [];
		for ($i=0; $i<3; $i++) {
			$c = $concepts[rand(0, $conceptIndices)];
			$strongest []= ["id" => $c["concept_number"], "display" => $c["concept_number"]." ".$c["concept_title"], "score" => rand(50,100) / 10];
		}
		$weakest = [];
		for ($i=0; $i<3; $i++) {
			$c = $concepts[rand(0, $conceptIndices)];
			$weakest []= ["id" => $c["concept_number"], "display" => $c["concept_number"]." ".$c["concept_title"], "score" => rand(0,50) / 10];
		}

		$result = ["strongest" => $strongest, "weakest" => $weakest];
		echo json_encode($result);
	}

	// Returns content recommendations in 4 groups:
		// Try these quiz questions (Group 1)
		// Watch these videos before attempting these quiz questions (Group 2)
		// Find additional help (Group 3)
		// Practice these questions again (Group 4)
	public function recommendationsAction($unit, $debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		if (!isset($unit)) {
			echo '[{"error":"No unit specified"}]';
			return;
		}

		$classHelper = new ClassHelper();

		$group1 = [];
		$group2 = [];
		$group3 = [];
		$group4 = [];
		// Get chapters from this unit (or all units)
		$chapterNumbers = ($unit == "all") ? MappingHelper::allChapters() : MappingHelper::chaptersInUnit($unit);
		// Then get concepts that are in those chapters
		$concepts = MappingHelper::conceptsInChapters($chapterNumbers);
		// We need just the concept numbers to find questions
		$conceptNumbers = array_column($concepts, "concept_number");
		// Finally, get all the question ids in those concepts
		$questionIds = MappingHelper::questionsInConcepts($conceptNumbers);

		$questions = array();
		// Get some info about each question
		foreach ($questionIds as $questionId) {
			$question = MappingHelper::questionInformation($questionId);
			// Check that it's a valid question
			if ($question != false) {
				// Get number of attempts and number of correct attempts
				$question["attempts"] = MasteryHelper::countAttemptsForQuestion($context->getUserEmail(), $question["assessmentId"], $question["questionNumber"], $debug);
				$question["correctAttempts"] = MasteryHelper::countCorrectAttemptsForQuestion($context->getUserEmail(), $question["assessmentId"], $question["questionNumber"], $debug);
				// Get amount of associated videos watched
				// Note that question ID is being used instead of assessment ID and question number, since we're searching the csv mapping and not dealing with assessment statements here
				$question["videoPercentage"] = MasteryHelper::calculateVideoPercentageForQuestion($context->getUserEmail(), $questionId);
				// Variables used in the display table
				// This is one place where we're just using correct, not better correct, attempts
				$question["correct"] = $question["correctAttempts"]["correct"] > 0;
				$question["classAverageAttempts"] = $classHelper->calculateAverageAttemptsForQuestion($question["assessmentId"], $question["questionNumber"], $debug);
				$question["classViewedHint"] = $classHelper->calculateViewedHintPercentageForQuestion($question["assessmentId"], $question["questionNumber"], $debug);
				$question["classViewedAnswer"] = $classHelper->calculateViewedAnswerPercentageForQuestion($question["assessmentId"], $question["questionNumber"], $debug);
				$questions []= $question;
			}
		}


		// Fetch question texts for all questions in these assessments
			// Get the Open Assessments API endpoint from config
			$assessmentsEndpoint = $this->getDI()->getShared('config')->openassessments_endpoint;
			$assessmentIds = ["assessment_ids" => array_values(array_unique(array_column($questions, "assessmentId")))];
			print_r(array_column($questions, "assessmentId"));
			print_r($assessmentIds);
			$request = $assessmentsEndpoint."api/question_text";
			echo $request;
			$session = curl_init($request);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_POST, 1);
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($assessmentIds));

			$response = curl_exec($session);

			// Catch curl errors
			if (curl_errno($session)) {
				$error = "Curl error: " . curl_error($session);
			}
			curl_close($session);

			$questionTexts = json_decode($response, true);

			print_r($questionTexts);

			foreach ($questions as $key => $q) {
				// Make sure the question text exists before setting it
				// Avoid off-by-one error. The question id from statement object id will be 1 to n+1
				$questions[$key]["display"] = isset($questionTexts[$q["assessmentId"]][$q["questionNumber"]-1]) ? $questionTexts[$q["assessmentId"]][$q["questionNumber"]-1] : "Error getting question text for #{$q["assessmentId"]} # #{$q["questionNumber"]}-1";
				//$q["questionText"] = $questionText;
			}

		// Now go through the questions for each group and find matching questions
		foreach ($questions as $question) {
			// Group 1:
				// Questions with 0 attempts
			if ($question["attempts"] == 0) {
				$group1 []= $question;
			}
			// Group 2:
				// >0 attempts for each question
				// No correct statements without a show answer statement in the preceding minute for each question (correctAttempts < 1)
				// Watched less than half of the videos associated with each question
			if ($question["attempts"] > 0 && $question["correctAttempts"]["betterCorrect"] == 0 && $question["videoPercentage"] < 0.50) {
				$group2 [] = $question;
			}
			// Group 3:
				// >0 attempts for each question
				// No correct statements without a show answer statement in the preceding minute for each question (correctAttempts < 1)
				// Watched more than half of the videos associated with each question
			if ($question["attempts"] > 0 && $question["correctAttempts"]["betterCorrect"] == 0 && $question["videoPercentage"] >= 0.50) {
				$group3 [] = $question;
			}
			// Group 4:
				// >  0 correct statements without a show answer statement in the preceding minute for each question (correctAttempts > 0)
				// More than 1 attempt
			if ($question["correctAttempts"]["betterCorrect"] > 0 && $question["attempts"] > 1) {
				$group4 []= $question;
			}
			
		}

		if ($debug) { print_r($questions); }

		$result = [
			"group1" => $group1,
			"group2" => $group2,
			"group3" => $group3,
			"group4" => $group4,
		];
		echo json_encode($result);
	}


	// Returns an array of points for the concept mastery scatterplot
	public function scatterplotAction($scope = 'concept', $groupingId = '', $debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}

		// TODO default to current concept?

		// Get the list of questions associated with concepts for the given scope and grouping ID
		$questions = [];
		switch ($scope) {
			case "concept":
				// Filter based on concept
				$questions = MappingHelper::questionsInConcept($groupingId);
				break;
			case "chapter":
				// Filter based on chapter
				// conceptsInChapter returns an array with more than just concept number, so get just concept_number column
				$questions = MappingHelper::questionsInConcepts(array_column(MappingHelper::conceptsInChapter($groupingId), "concept_number"));
				break;
			case "unit":
				// Filter based on unit
				$questions = MappingHelper::questionsInConcepts(array_column(MappingHelper::conceptsInChapters(MappingHelper::chaptersInUnit($groupingId)), "concept_number"));
				break;
			default:
				echo '[{"error":"Invalid scope option"}]';
				return;
				break;
		}
		if ($debug) {
			echo "questions for scope $scope and grouping $groupingId: \n";
			print_r($questions);
		}

		// Remove duplicate questions (if question is associated with more than one video, only show it once)
		$uniqueQuestions = array_unique($questions);

		// Array of questions with more details about each
		$questionDetails = array();

		// Get some info about each question
		foreach ($questions as $questionId) {
			$question = MappingHelper::questionInformation($questionId);
			// Check that it's a valid question
			if ($question != false) {
				// Get number of attempts
				$question["attempts"] = MasteryHelper::countAttemptsForQuestion($context->getUserEmail(), $question["assessmentId"], $question["questionNumber"], $debug);
				// Get amount of associated videos watched
				// Note that question ID is being used instead of assessment ID and question number, since we're searching the csv mapping and not dealing with assessment statements here
				$question["videoPercentage"] = MasteryHelper::calculateVideoPercentageForQuestion($context->getUserEmail(), $questionId);

				$questionDetails []= $question;
			}
		}

		$questionDetails = [];

		$headerRow = ["group", "quiz_number", "question_number", "x", "y"];
		function randomPoint($group, $q) {
			// Randomly return outliers
			if (rand(0,30) == 5) {
				return [$group, $q["quizNumber"], $q["questionNumber"], rand(-10000, 1000), rand(-10000, 1000)];
			}
			return [$group, $q["quizNumber"], $q["questionNumber"], rand(-100, 100), rand(-100, 100)];
		}
		$result = [];
		// For now, return random points based on number of questions
		$numPoints = count($questionDetails);
		//foreach ($questionDetails as $q) {
			//$result [] = 
		//}
		for ($i=0; $i<$numPoints; $i++) {
			$result []= randomPoint("student", $questionDetails[$i]);
			for ($j=0; $j<10; $j++) {
				$result []= randomPoint("class", $questionDetails[$i]);
			}
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
		fputcsv($output, $headerRow);
		foreach ($result as $row) {
			fputcsv($output, $row); // here you can change delimiter/enclosure
		}
		fclose($output);
	}

	public function masteryGraphAction($scope = 'all', $groupingId = '', $debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}

		$result = [];

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

		$masteryHelper = new MasteryHelper();
		foreach ($concepts as $c) {
			$score = $masteryHelper::calculateConceptMasteryScore($context->getUserEmail(), $c["concept_number"], $debug);
			$result []= ["id" => $c["concept_number"], "display" => $c["concept_title"], "score" => $score];
		}
		echo json_encode($result);
	}

}


