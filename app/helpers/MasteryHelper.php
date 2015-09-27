<?php 

use Phalcon\Mvc\User\Module;

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

		// Load quiz id -> assessment id mapping
		$assessmentIds = CSVHelper::parseWithHeaders('csv/quiz_assessmentid.csv');

		// Loop through each question
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
			$conceptTotalAttempts += self::countAttemptsForQuestion($studentId, $assessmentId, $questionNumber);
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
}
