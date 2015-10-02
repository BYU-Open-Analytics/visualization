<?php 

use Phalcon\Mvc\User\Module;
include __DIR__ . "/../library/array_functions.php";

class MasteryHelper extends Module {

	public static function calculateConceptMasteryScore($studentId, $conceptId) {

		// Get questions in concept
		$questionIds = array();
		$conceptQuestions = CSVHelper::parseWithHeaders('csv/video_concept_question.csv');
		// Filter questions to ones in the selected concept
		$questionLists = array_filter($conceptQuestions, function($concept) use ($conceptId) {
			return ($concept["concept_number"] == $conceptId);
		});
		// Combine multiple rows of questions that are with the same concept
		array_walk($questionLists, function($concept) use (&$questionIds) {
			$questionIds = array_merge($questionIds, explode(",", $concept["questions"]));
		});
		//echo "Concept ID $conceptId: ";
		//print_r($questionIds);
		//echo "<hr>";
		
		// Calculate total # attempts (answered statements) for all questions in the concept
		$conceptTotalAttempts = 0;

		// Array to hold information about each question
		$conceptQuestions = array();

		// Load quiz id -> assessment id mapping
		$assessmentIds = CSVHelper::parseWithHeaders('csv/quiz_assessmentid.csv');
		// Load question type mapping
		$questionTypes = CSVHelper::parseWithHeaders('csv/question_type.csv');

		// Loop through each question and get basic information for each
		foreach ($questionIds as $questionId) {
			// Split up quiz id and question id from format 12.1 (quizNumber.questionNumber)
			$idParts = explode(".", $questionId);
			// Make sure we have a (at least format-wise) valid question id
			if (count($idParts) != 2) {
				continue;
			}
			$quizNumber = $idParts[0];
			$questionNumber = $idParts[1];
			// Get assessment id from quiz id
			$assessmentId = $assessmentIds[array_search($quizNumber, array_column($assessmentIds, 'quiz_number'))]["assessment_id"];

			// Get question type, since we do different calculations based on multiple choice or short answer
			$questionType = $questionTypes[multi_array_search($questionTypes, ["quiz" => $quizNumber, "question" => $questionNumber])[0]]["type"];

			// Don't include essay questions in any calculations
			$questionAttempts = 0;
			if ($questionType != "essay") {
				$questionAttempts = self::countAttemptsForQuestion($studentId, $assessmentId, $questionNumber);
				$conceptTotalAttempts += $questionAttempts;
			}

			// Store this information in the array
			$conceptQuestions []= ["quizNumber" => $quizNumber, "questionNumber" => $questionNumber, "assessmentId" => $assessmentId, "attempts" => $questionAttempts];

		}
		return $conceptTotalAttempts;
	}

	public static function countAttemptsForQuestion($studentId, $assessmentId, $questionNumber) {
		$statementHelper = new StatementHelper();

		// Regex for assessment id and question number (since url for object IDs will change, but end part will be the same format)
		$regex = new MongoRegex('/' . $assessmentId . '\.xml#' . $questionNumber . '$/');
		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";
		// Get the count of answered statements for this question for current user
		$statements = $statementHelper->getStatements("openassessments",[
			'statement.actor.mbox' => 'mailto:'.$studentId,
			'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
			//'statement.object.id' => $regex,
			'statement.object.definition.name.en-US' => $questionDescription,
		], [
			'statement.result.success' => true,
		]);
		if ($statements["error"]) {
			// TODO error handling
		} else {
			// My attempt at debugging slow queries to figure out how to speed them out
			//var_dump($statements["cursor"]->explain());
			return $statements["cursor"]->count();
		}
	}

	//static function 
}
