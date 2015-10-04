<?php 

use Phalcon\Mvc\User\Module;
include __DIR__ . "/../library/array_functions.php";

class MasteryHelper extends Module {

	public static function calculateConceptMasteryScore($studentId, $conceptId, $debug = false) {

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
		if ($debug) {
			echo "Concept ID $conceptId: ";
			print_r($questionIds);
			echo "<hr>";
		}
		
		// Calculate total # attempts (answered statements) for all questions in the concept
		$conceptTotalAttempts = 0;
		$conceptTotalCorrectAttempts = 0;

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
				// Store this information in the array
				$conceptQuestions []= ["quizNumber" => $quizNumber, "questionNumber" => $questionNumber, "assessmentId" => $assessmentId, "questionType" => $questionType];
			}

		}
				//$attempts = self::countAttemptsForQuestion($studentId, $assessmentId, $questionNumber, $debug);
				//$conceptTotalAttempts += $attempts;
				//$correctAttempts = self::countcorrectAttemptsForQuestion($studentId, $assessmentId, $questionNumber, $debug);
				//$conceptTotalCorrectAttempts += $correctAttempts;
		if ($debug) {
			echo "Questions in concept $conceptId <hr><pre>";
			print_r($conceptQuestions);
		}
		// Avoid division by zero
		return $conceptTotalAttempts == 0 ? 0 : $conceptTotalCorrectAttempts / $conceptTotalAttempts;
	}

	// Returns the number of attempts (answered statements) a student has made for a particular question
	public static function countAttemptsForQuestion($studentId, $assessmentId, $questionNumber) {
		$statementHelper = new StatementHelper();

		// Regex for assessment id and question number (since url for object IDs will change, but end part will be the same format)
		$regex = new MongoRegex('/' . $assessmentId . '\.xml#' . $questionNumber . '$/');
		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";
		// Get the count of answered statements for this question for current user
		// TODO take the verb authority (adlnet/expapi/verbs/) part and put into a global constant
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

	// Returns the number of correct attempts (Correct = if there's a correct statement with no show answer statement in the minute before) a student has for a particular question
	public static function countCorrectAttemptsForQuestion($studentId, $assessmentId, $questionNumber, $debug = false) {
		$statementHelper = new StatementHelper();

		// Regex for assessment id and question number (since url for object IDs will change, but end part will be the same format)
		$regex = new MongoRegex('/' . $assessmentId . '\.xml#' . $questionNumber . '$/');
		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";
		// Get the count of answered and showed-answer statements for this question for current user
		// TODO take the verb authority (adlnet/expapi/verbs/) part and put into a global constant
		$statements = $statementHelper->getStatements("openassessments",[
			'statement.actor.mbox' => 'mailto:'.$studentId,
			'statement.verb.id' => array('$in' => array(
				'http://adlnet.gov/expapi/verbs/answered',
				'http://adlnet.gov/expapi/verbs/showed-answer'
			)),
			//'statement.object.id' => $regex,
			'statement.object.definition.name.en-US' => $questionDescription,
		], [
			'statement.verb.id' => true,
			'statement.result.success' => true,
			'statement.timestamp' => true,
		]);
		if ($statements["error"]) {
			// TODO error handling
		} else {
			$correctAttempts = 0;
			// Find the correct answered statements
			foreach ($statements["cursor"] as $statement) {
				if ($statement["statement"]["verb"]["id"] == 'http://adlnet.gov/expapi/verbs/answered' && $statement["statement"]["result"]["success"] == true) {
					if ($debug) echo "Correct answered statement!<br>";
					// Now see if there's a shown-answer statement in the preceding minute
					// Set this to false if we find a shown-answer statement in the preceding minute
					$attemptCorrect = true;
					foreach ($statements["cursor"] as $possibleShowAnswerStatement) {
						if ($possibleShowAnswerStatement["statement"]["verb"]["id"] == 'http://adlnet.gov/expapi/verbs/showed-answer') {
							// Compare time 
							$timeDifference = strtotime($statement["statement"]["timestamp"]) - strtotime($possibleShowAnswerStatement["statement"]["timestamp"]);
							if ($debug) echo "Time diff (seconds): $timeDifference<br>";
							// TODO move this magic number 60
							if ($timeDifference > 0 && $timeDifference < 60) {
								$attemptCorrect = false;
								break;
							}
						}
					}
					if ($attemptCorrect) {
						$correctAttempts++;
					}

				}
				if ($debug) {
					echo strtotime($statement["statement"]["timestamp"]);
					echo "<pre>"; print_r($statement);
					echo "<hr>";
				}
			}
			if ($debug) echo "correct attempts: $correctAttempts";
			return $correctAttempts;
		}
	}
}
