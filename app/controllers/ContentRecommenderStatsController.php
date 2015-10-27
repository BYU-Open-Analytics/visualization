<?php

use Phalcon\Mvc\Controller;
include __DIR__ . "/../library/array_functions.php";

class ContentRecommenderStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Assessment Stats');
	}

	// Returns content recommendations in 4 groups:
		// Try these quiz questions (Group 1)
		// Watch these videos before attempting these quiz questions (Group 2)
		// Find additional help (Group 3)
		// Practice these questions again (Group 4)
	public function recommendationsAction($scope = 'unit', $groupingId = '3', $debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		if (!isset($groupingId)) {
			echo '[{"error":"No scope grouping ID specified"}]';
			return;
		}

		$classHelper = new ClassHelper();

		$group1 = [];
		$group2 = [];
		$group3 = [];
		$group4 = [];

		// Get the list of questions associated with concepts for the given scope and grouping ID
		$questionIds = [];
		switch ($scope) {
			case "concept":
				// Filter based on concept
				$questionIds = MappingHelper::questionsInConcept($groupingId);
				break;
			case "chapter":
				// Filter based on chapter
				// conceptsInChapter returns an array with more than just concept number, so get just concept_number column
				$questionIds = MappingHelper::questionsInConcepts(array_column(MappingHelper::conceptsInChapter($groupingId), "Section Number"));
				break;
			case "unit":
				// Filter based on unit
				$questionIds = MappingHelper::questionsInConcepts(array_column(MappingHelper::conceptsInChapters(MappingHelper::chaptersInUnit($groupingId)), "Section Number"));
				break;
			default:
				echo '[{"error":"Invalid scope option"}]';
				return;
				break;
		}

		if ($debug) {
			echo "<pre>Getting information for these questions in scope $scope and ID $groupingId\n";
			print_r($questionIds);
		}
		$questions = array();
		// Get some info about each question
		foreach ($questionIds as $questionId) {
			$question = MappingHelper::questionInformation($questionId);
			// Check that it's a valid question
			if ($question != false) {
				// Get number of attempts and number of correct attempts
				$question["attempts"] = MasteryHelper::countAttemptsForQuestion($context->getUserName(), $question["assessmentId"], $question["questionNumber"], $debug);
				$question["correctAttempts"] = MasteryHelper::countCorrectAttemptsForQuestion($context->getUserName(), $question["assessmentId"], $question["questionNumber"], $debug);
				// Get amount of associated videos watched
				// Note that question ID is being used instead of assessment ID and question number, since we're searching the csv mapping and not dealing with assessment statements here
				$question["videoPercentage"] = MasteryHelper::calculateVideoPercentageForQuestion($context->getUserName(), $questionId);
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

			$request = $assessmentsEndpoint."api/question_text";
			if ($debug) {
				echo "Fetching question texts for these assessment IDs:\n";
				print_r(array_column($questions, "assessmentId"));
				print_r($assessmentIds);
				echo $request;
			}
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

			if ($debug) { print_r($questionTexts); }

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
				$questions = MappingHelper::questionsInConcepts(array_column(MappingHelper::conceptsInChapter($groupingId), "Section Number"));
				break;
			case "unit":
				// Filter based on unit
				$questions = MappingHelper::questionsInConcepts(array_column(MappingHelper::conceptsInChapters(MappingHelper::chaptersInUnit($groupingId)), "Section Number"));
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

		$classHelper = new ClassHelper();

		// Array of questions with more details about each
		$questionDetails = array();

		// Get some info about each question
		foreach ($questions as $questionId) {
			$question = MappingHelper::questionInformation($questionId);
			// Check that it's a valid question
			if ($question != false) {
				// Get number of attempts
				$question["attempts"] = MasteryHelper::countAttemptsForQuestion($context->getUserName(), $question["assessmentId"], $question["questionNumber"], $debug);
				$question["scaledAttemptScore"] = $classHelper->calculateScaledAttemptScoreForQuestion($question["attempts"], $question["assessmentId"], $question["questionNumber"], $debug);
				// Get amount of associated videos watched
				// Note that question ID is being used instead of assessment ID and question number, since we're searching the csv mapping and not dealing with assessment statements here
				$question["videoPercentage"] = MasteryHelper::calculateVideoPercentageForQuestion($context->getUserName(), $questionId);

				$questionDetails []= $question;
			}
		}

		/*
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
		//
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
		*/
		// X = video percentage, Y = question attempts
		$headerRow = ["group", "quiz_number", "question_number", "x", "y"];

		$result = array_map(function($q) {
			return ["student", $q["assessmentId"], $q["questionNumber"], $q["videoPercentage"], $q["scaledAttemptScore"]];
		}, $questionDetails);
		if ($debug) {
			echo "question details for scope $scope and grouping $groupingId: \n";
			print_r($questionDetails);
			print_r($result);
		}

		// Output data as csv so that we only have to send header information once for so many points
		if (!$debug) {
			header("Content-Type: text/csv");
		}
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
		$concepts = [];
		switch ($scope) {
			case "chapter":
				// Filter based on chapter
				$concepts = MappingHelper::conceptsInChapter($groupingId);
				break;
			case "unit":
				// Filter based on unit
				$concepts = MappingHelper::conceptsInChapters(MappingHelper::chaptersInUnit($groupingId));
				break;
			default:
				// All concepts
				$concepts = MappingHelper::conceptsInChapters(MappingHelper::allChapters());
				break;
		}
		$masteryHelper = new MasteryHelper();
		foreach ($concepts as $c) {
			$score = $masteryHelper::calculateConceptMasteryScore($context->getUserName(), $c["Section Number"], $debug);
			if ($debug) { echo "Concept mapping info\n"; print_r($c); }
			$result []= ["id" => $c["Section Number"], "display" => $c["Section Title"], "score" => $score, "unit" => $c["Unit"]];
		}
		echo json_encode($result);
	}

}


