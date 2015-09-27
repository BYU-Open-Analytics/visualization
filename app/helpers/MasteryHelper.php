<?php 

use Phalcon\Mvc\User\Module;

class MasteryHelper extends Module {

	public static function calculateConceptMasteryScore($studentId, $conceptId) {

		$statementHelper = new StatementHelper();

		// Get questions in concept
		// TODO remove hardcoded example question IDs
		$questionIds = array();
		

		$conceptQuestions = CSVHelper::parseWithHeaders('csv/concept_question.csv');
		// Filter questions to ones in the selected concept
		$questionLists = array_filter($conceptQuestions, function($concept) use ($conceptId) {
			return ($concept["concept_number"] == $conceptId);
		});
		array_walk($questionLists, function($concept) use (&$questionIds) {
			$questionIds = array_merge($questionIds, explode(",", $concept["questions"]));
		});
			
		//echo "Concept ID $conceptId: ";
		//print_r($questionIds);
		//echo "<hr>";
		

		// Calculate total # attempts (answered statements) for all questions in the concept
		$conceptTotalAttempts = 0;

		// Loop through each question
		foreach ($questionIds as $questionId) {
			// Split up quiz id and question id from format 12.1 (quizId.questionNumber)
			$idParts = explode(".", $questionId);
			// Make sure we have a (at least format-wise) valid question id
			if (count($idParts) != 2) {
				continue;
			}
			$quizId = $idParts[0];
			$questionNumber = $idParts[1];
			// Get assessment id from quiz id
			// TODO actually do this
			$assessmentId = $quizId;

			// Regex for assessment id and question number (since url for object IDs will change, but end part will be the same format)
			$regex = new MongoRegex('/' . $assessmentId . '\.xml#' . $questionNumber . '$/');
			// Get the count of answered statements for this question for current user
			$statements = $statementHelper->getStatements("openassessments",[
				'statement.actor.mbox' => 'mailto:'.$studentId,
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
				'statement.object.id' => $regex,
			], [
				'statement.result.success' => true,
			]);
			if ($statements["error"]) {
				// TODO error handling
			} else {
				$conceptTotalAttempts += $statements["cursor"]->count();
			}
		}
		return $conceptTotalAttempts;
	}
}
