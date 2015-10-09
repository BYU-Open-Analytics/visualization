<?php 

use Phalcon\Mvc\User\Module;

class MappingHelper extends Module {

	/* NOTE: The usage of quiz number, assessment Id, question Id, etc. is often rather opaque.
	 Here's a list of what I'm calling what:
	• questionId: human-normal id consisting of quiz number, period, and question number: "3.4" (fourth question of quiz 3)
		○ This is what is listed in the csv mappings
	• quizNumber: the number of the quiz in the course flow. Sequential, 1-whatever. Needs to be converted to assessmentId before use in statement queries.
	• questionNumber: the 1-n number of the question
	• assessmentId: the Open Assessments ID for a quiz. Not sequential, or related to quizNumber. Needed for statement queries.
	*/

	// Returns an array of all chapter numbers
	static public function allChapters() {
		// Get unit -> chapter mapping
		$units = CSVHelper::parseWithHeaders('csv/unit_chapter.csv');
		$chapters = [];
		foreach ($units as $unit) {
			$chapters = array_merge($chapters, explode(",", $unit["chapters"]));
		}
		return $chapters;
	}

	// Returns an array of chapter numbers for a given unit
	static public function chaptersInUnit($unitNumber) {
		// Get unit -> chapter mapping
		$units = CSVHelper::parseWithHeaders('csv/unit_chapter.csv');
		$unit = $units[array_search($unitNumber, array_column($units, 'unit_number'))];
		// Get chapters that are in this unit
		$correspondingChapters = explode(",", $unit["chapters"]);
		return $correspondingChapters;

	}

	// Returns an array of concept numbers for the given array of chapter numbers
	static public function conceptsInChapters($chapters) {
		$allConcepts = CSVHelper::parseWithHeaders('csv/concept_chapter_unit.csv');
		$concepts = array_filter($allConcepts, function($concept) use ($chapters) {
			return (in_array($concept["chapter_number"], $chapters));
		});
		return $concepts;
	}

	// Returns an array of unique questions (format is {quiz number}.{question number}) for the given concept id
	static public function questionsInConcept($conceptId) {
		$conceptQuestions = CSVHelper::parseWithHeaders('csv/video_concept_question.csv');
		// Filter questions to ones in the selected concept
		$questionLists = array_filter($conceptQuestions, function($concept) use ($conceptId) {
			return ($concept["concept_number"] == $conceptId);
		});
		$questionIds = array();
		// Combine multiple rows of questions that are with the same concept
		array_walk($questionLists, function($concept) use (&$questionIds) {
			$questionIds = array_merge($questionIds, explode(",", $concept["questions"]));
		});
		// Remove duplicate questions (if question is associated with more than one video in the concept, only show it once)
		$questionIds = array_unique($questionIds);
		return $questionIds;
	}

	// Returns an array of unique (if question is in more than one concept, this will only list it once) questions (format is {quiz number}.{question number}) for the given array of concept ids
	static public function questionsInConcepts($conceptIds) {
		$questions = array();
		foreach ($conceptIds as $conceptId) {
			$questions = array_merge($questions, self::questionsInConcept($conceptId));
		}
		$questions = array_unique($questions);
		return $questions;
	}

	// Returns an array of videos for a given questionId
	static public function videosForQuestion($questionId) {
		$videos = CSVHelper::parseWithHeaders('csv/video_concept_question.csv');
		$relatedVideos = array();
		foreach ($videos as $video) {
			// Can't just do a string search in $video["questions"], since 1.11 would match 1.1
			$videoQuestions = explode(",", $video["questions"]);
			if (in_array($questionId, $videoQuestions)) {
				$relatedVideos []= $video;
			}
		}
		return $relatedVideos;
	}

	// Returns an array of information about a given question ID with format {quiz number}.{question number}
		// Array with quizNumber, questionNumber, assessmentId, and questionType (and options if multiple_choice)
		// If given questionId is not valid, it returns false
	static public function questionInformation($questionId) {
		// Load quiz id -> assessment id mapping
		$assessmentIds = CSVHelper::parseWithHeaders('csv/quiz_assessmentid.csv');
		// Load question type mapping
		$questionTypes = CSVHelper::parseWithHeaders('csv/question_type.csv');

		// Split up quiz id and question id from format 12.1 (quizNumber.questionNumber)
		$idParts = explode(".", $questionId);
		// Make sure we have a (at least format-wise) valid question id
		if (count($idParts) != 2) {
			return false;
		}
		$quizNumber = $idParts[0];
		$questionNumber = $idParts[1];
		// Get assessment id from quiz id
		$assessmentId = $assessmentIds[array_search($quizNumber, array_column($assessmentIds, 'quiz_number'))]["assessment_id"];

		// Get question type
		$questionType = $questionTypes[multi_array_search($questionTypes, ["quiz" => $quizNumber, "question" => $questionNumber])[0]]["type"];

		$question = [
			"quizNumber" => $quizNumber,
			"questionNumber" => $questionNumber,
			"assessmentId" => $assessmentId,
			"questionType" => $questionType
		];

		// If a multiple choice question, add the number of options the question has
		if ($questionType == "multiple_choice") {
			$question["options"] = $questionTypes[multi_array_search($questionTypes, ["quiz" => $quizNumber, "question" => $questionNumber])[0]]["options"];
		}
		return $question;
	}

}