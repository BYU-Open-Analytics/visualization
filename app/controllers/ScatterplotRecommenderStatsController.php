<?php

use Phalcon\Mvc\Controller;
include __DIR__ . "/../library/array_functions.php";

class ScatterplotRecommenderStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Scatterplot Recommender Stats');
	}

	// Returns videos and their unique percentage watched for a given content grouping (all, unit, chapter, or concept)
	public function videoRecommendationsAction($scope = 'all', $groupingId = 'all', $debug = false) {
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

		// Get the list of videos associated with concepts for the given scope and grouping ID
		$videos = [];
		switch ($scope) {
			case "concept":
				// Filter based on concept
				$videos = MappingHelper::videosForConcept($groupingId);
				break;
			case "chapter":
				// Filter based on chapter
				// conceptsInChapter returns an array with more than just concept number, so get just concept_number column
				$videos = MappingHelper::videosForConcepts(array_column(MappingHelper::conceptsInChapter($groupingId), "Section Number"));
				break;
			case "unit":
				// Filter based on unit
				$videos = MappingHelper::videosForConcepts(array_column(MappingHelper::conceptsInChapters(MappingHelper::chaptersInUnit($groupingId)), "Section Number"));
				break;
			default:
				// All videos
				$videos = MappingHelper::videosForConcepts(array_column(MappingHelper::allConcepts(), "Section Number"));
				break;
		}

		// Find percentage watched for each video
		foreach ($videos as $key => $video) {
			$percentageWatched = MasteryHelper::calculateUniqueVideoPercentageForVideo($context->getUserName(), $video, $debug);
			// Add the percentage to the video
			$video["percentageWatched"] = $percentageWatched;
			// Add the modified video back to the original array
			$videos[$key] = $video;
		}

		// Format each video for the frontend
		$formatted = [];
		foreach ($videos as $video) {
			$formatted []= $video;
		}

		if ($debug) {
			echo "<pre>Getting information for these video IDs in scope $scope and ID $groupingId\n";
			foreach ($videos as $video) {
				echo $video["Video ID"]."\n";
			}
		}

		echo json_encode($formatted);
	}


	// Returns the additional resources recommendations for the given scope and grouping ID (only concept is currently supported)
	public function resourceRecommendationsAction($scope, $groupingId, $debug = false) {
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

		// Get the list of resources associated with concepts for the given scope and grouping ID
		$resources = [];
		switch ($scope) {
			case "concept":
				// Filter based on concept
				$resources = MappingHelper::resourcesForConcept($groupingId);
				break;
			default:
				// We currently only need resources for a selected concept
				echo '[{"error":"Invalid scope option"}]';
				return;
				break;
		}

		echo json_encode($resources);
	}

	

	// Returns scatterplot recommendations in 4 groups:
		// Try these quiz questions (Group 1)
		// Watch these videos before attempting these quiz questions (Group 2)
		// Find additional help (Group 3)
		// Practice these questions again (Group 4)
	public function questionRecommendationsAction($scope = 'unit', $groupingId = '3', $debug = false) {
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
				// Allowing all would take too long
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
				$question["videoPercentage"] = MasteryHelper::calculateUniqueVideoPercentageForQuestion($context->getUserName(), $questionId);
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

	public function conceptsAction($scope = 'all', $groupingId = '', $debug = false) {
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
			case "concept":
				// Get a specific concept
				$conceptId = $groupingId;
				$concepts = array_filter(MappingHelper::allConcepts(), function($concept) use ($conceptId) {
					return ($concept["Section Number"] == $conceptId);
				});
				break;
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
				$concepts = MappingHelper::allConcepts();
				break;
		}
		$masteryHelper = new MasteryHelper();
		foreach ($concepts as $c) {
			//$score = $masteryHelper::calculateConceptMasteryScore($context->getUserName(), $c["Section Number"], $debug);
			//$videoPercentage = $masteryHelper::calculateUniqueVideoPercentageForConcept($context->getUserName(), $c["Section Number"], $debug);
			$score = rand(0,100) / 10;
			$videoPercentage = rand(0,100);
			if ($debug) { echo "Concept mapping info\n"; print_r($c); }
			$result []= [
				"id" => $c["Section Number"],
				"title" => $c["Section Title"],
				"masteryScore" => $score,
				"videoPercentage" => $videoPercentage,
				"unit" => $c["Unit"]
			];
		}
		echo json_encode($result);
	}

	// Mastery over time
	public function time_graphAction($debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}


		$email = $context->getUserName();
		// Fetch skill history items for the current student
		$historyResults = MasteryHistory::find([
			"email = '$email'",
			"order" => 'time_stored ASC'
		]);

		$historyPoints = [];
		foreach ($historyResults as $day) {
			// Scores are saved at 3am, so they actually correspond to the previous day
			$formattedDate = date('M j', strtotime('-1 day', strtotime($day->time_stored)));
			$historyPoints []= [$formattedDate, round($day->unit3 * 100) / 100, round($day->unit4 * 100) / 100];
			//$historyPoints []= [$formattedDate, rand(0,100) / 10, rand(0,100) / 10];
		}
		if ($debug) {
			print_r($historyPoints);
		}

		// Output data as csv so that we only have to send header information once
		if (!$debug) {
			header("Content-Type: text/csv");
		}
		$output = fopen("php://output", "w");
		// Header row
		fputcsv($output, ["date", "Unit 3", "Unit 4"]);
		foreach ($historyPoints as $row) {
			fputcsv($output, $row); // here you can change delimiter/enclosure
		}
		fclose($output);
	}
}


