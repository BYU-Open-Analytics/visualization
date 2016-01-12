<?php

use Phalcon\Mvc\Controller;

// Stats and calculations used in the content recommender dashboard (currently not used)
// These are loaded via ajax and then used in the visualizations

class ContentRecommenderStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Assessment Stats');
	}

	// Returns scatterplot recommendations in 4 groups:
		// Try these quiz questions (Group 1)
		// Watch these videos before attempting these quiz questions (Group 2)
		// Find additional help (Group 3)
		// Practice these questions again (Group 4)
	public function recommendationsAction($scope, $groupingId, $debug = false) {
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
		$questionRows = [];
		switch ($scope) {
			case "concept":
				// Filter based on concept
				$questionRows  = MappingHelper::questionsInConcept($groupingId);
				break;
			case "unit":
				// Filter based on unit
				$questionRows = MappingHelper::questionsInConcepts(MappingHelper::conceptsInUnit($groupingId));
				break;
			default:
				// Allowing all would take too long
				echo '[{"error":"Invalid scope option"}]';
				return;
				break;
		}

		if ($debug) {
			echo "<pre>Getting information for these questions in scope $scope and ID $groupingId\n";
			print_r($questionRows);
		}
		$questions = [];
		// Get some info about each question
		foreach ($questionRows as $question) {
			// Get number of attempts and number of correct attempts
			$question["attempts"] = MasteryHelper::countAttemptsForQuestion($context->getUserName(), $question["OA Quiz ID"], $question["Question Number"], $debug);
			$question["correctAttempts"] = MasteryHelper::countCorrectAttemptsForQuestion($context->getUserName(), $question["OA Quiz ID"], $question["Question Number"], $debug);
			// Get amount of associated videos watched
			$question["videoPercentage"] = MasteryHelper::calculateUniqueVideoPercentageForQuestion($context->getUserName(), $question);
			// Variables used in the display table
			// This is one place where we're just using correct, not better correct, attempts
			$question["correct"] = $question["correctAttempts"]["correct"] > 0;
			$question["classAverageAttempts"] = $classHelper->calculateAverageAttemptsForQuestion($question["OA Quiz ID"], $question["Question Number"], $debug);
			$question["classViewedHint"] = $classHelper->calculateViewedHintPercentageForQuestion($question["OA Quiz ID"], $question["Question Number"], $debug);
			$question["classViewedAnswer"] = $classHelper->calculateViewedAnswerPercentageForQuestion($question["OA Quiz ID"], $question["Question Number"], $debug);
			$questions []= $question;
		}


		// Fetch question texts for all questions in these assessments
			// Get the Open Assessments API endpoint from config
			$assessmentsEndpoint = $this->getDI()->getShared('config')->openassessments_endpoint;
			// Extract a list of assessment IDs from our list of questions. We'll get question texts for these.
			$assessmentIDs = ["assessment_ids" => array_values(array_unique(array_column($questions, "OA Quiz ID")))];

			$request = $assessmentsEndpoint."api/question_text";
			if ($debug) {
				echo "Fetching question texts for these assessment IDs:\n";
				print_r(array_column($questions, "OA Quiz ID"));
				print_r($assessmentIDs);
				echo $request;
			}
			$session = curl_init($request);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_POST, 1);
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($assessmentIDs));

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
				$questions[$key]["display"] = isset($questionTexts[$q["OA Quiz ID"]][$q["Question Number"]-1]) ? $questionTexts[$q["OA Quiz ID"]][$q["Question Number"]-1] : "Error getting question text for #{$q["OA Quiz ID"]} # #{$q["Question Number"]}-1";
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
	// NOTE: currently not updated to current mapping structure. Proceed with caution.
	public function scatterplotAction($scope = 'concept', $groupingId = '', $debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}

		// Get the list of questions associated with concepts for the given scope and grouping ID
		$questions = [];
		switch ($scope) {
			case "concept":
				// Filter based on concept
				$questions = MappingHelper::questionsInConcept($groupingId);
				break;
			case "unit":
				// Filter based on unit
				$questions = MappingHelper::questionsInConcepts(MappingHelper::conceptsInUnit($groupingId));
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

		$classHelper = new ClassHelper();

		// Array of questions with more details about each
		$questionDetails = array();

		// Get some info about each question
		foreach ($questions as $question) {
			// Check that it's a valid question
			if ($question != false) {
				// Get number of attempts
				$question["attempts"] = MasteryHelper::countAttemptsForQuestion($context->getUserName(), $question["OA Quiz ID"], $question["Question Number"], $debug);
				$question["scaledAttemptScore"] = $classHelper->calculateScaledAttemptScoreForQuestion($question["attempts"], $question["OA Quiz ID"], $question["Question Number"], $debug);
				// Get amount of associated videos watched
				// Note that question ID is being used instead of assessment ID and question number, since we're searching the csv mapping and not dealing with assessment statements here
				$question["videoPercentage"] = MasteryHelper::calculateUniqueVideoPercentageForQuestion($context->getUserName(), $questionId);

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
			return ["student", $q["OA Quiz ID"], $q["Question Number"], $q["videoPercentage"], $q["scaledAttemptScore"]];
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
			case "unit":
				// Filter based on unit
				$concepts = MappingHelper::conceptsInUnit($groupingId);
				break;
			default:
				// All concepts
				$concepts = MappingHelper::allConcepts();
				break;
		}
		$masteryHelper = new MasteryHelper();
		foreach ($concepts as $c) {
			$score = $masteryHelper::calculateConceptMasteryScore($context->getUserName(), $c["Lecture Number"], $debug);
			if ($debug) { echo "Concept mapping info\n"; print_r($c); }
			$result []= ["id" => $c["Lecture Number"], "display" => $c["Concept Title"], "score" => $score, "unit" => $c["Unit Number"]];
		}
		echo json_encode($result);
	}

}


