<?php 

use Phalcon\Mvc\User\Module;
include __DIR__ . "/../library/array_functions.php";

class MappingHelper extends Module {

	/* NOTE: The usage of quiz number, assessment Id, question Id, etc. is often rather opaque.
	 Here's a list of what I'm calling what:
	• questionId: human-normal id consisting of quiz number, period, and question number: "3.4" (fourth question of quiz 3)
		○ This is what is listed in the csv mappings
	• quizNumber: the number of the quiz in the course flow. Sequential, 1-whatever. Needs to be converted to assessmentId before use in statement queries.
	• questionNumber: the 1-n number of the question
	• assessmentId: the Open Assessments ID for a quiz. Not sequential, or related to quizNumber. Needed for statement queries.
	*/
	// Array indexes are sometimes weird, so look for the headings in the CSV files

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

	// Returns an array of concept numbers for the given chapter number
	static public function conceptsInChapter($chapter) {
		return self::conceptsInChapters([$chapter]);
	}

	// Returns an array of concept numbers for the given array of chapter numbers
	static public function conceptsInChapters($chapters) {
		$allConcepts = CSVHelper::parseWithHeaders('csv/concept_chapter.csv');
		$concepts = array_filter($allConcepts, function($concept) use ($chapters) {
			return (in_array($concept["Chapter Number"], $chapters));
		});
		return $concepts;
	}

	// TODO
	// Returns an array of unique questions (format is {quiz number}.{question number}) for the given concept id
	static public function questionsInConcept($conceptId) {
		$videos = CSVHelper::parseWithHeaders('csv/mappings.csv');
		$questionInfo = CSVHelper::parseWithHeaders('csv/questions.csv');

		// Get list of quiz numbers that are associated with this concept
		$quizNumbers = array();
		foreach ($videos as $video) {
			// Concept ID is the same as section number for our mappings' purposes
			if ($video["Section Number"] == $conceptId) {
				$quizNumbers [] = $video["Quiz #"];
			}
		}
		$quizNumbers = array_unique($quizNumbers);
		
		// Then get question IDs for questions that are in those quizzes
		$questionIds = array();
		foreach ($questionInfo as $q) {
			if (in_array($q["Quiz Number"], $quizNumbers)) {
				$questionIds [] = $q["Quiz Number"] . "." . $q["Question Number"];
			}
		}

		// Filter questions to ones in the selected concept
		//$questionLists = array_filter($conceptQuestions, function($concept) use ($conceptId) {
			//return ($concept["concept_number"] == $conceptId);
		//});
		//$questionIds = array();
		//// Combine multiple rows of questions that are with the same concept
		//array_walk($questionLists, function($concept) use (&$questionIds) {
			//$questionIds = array_merge($questionIds, explode(",", $concept["questions"]));
		//});

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
		$videos = CSVHelper::parseWithHeaders('csv/mappings.csv');
		$relatedVideos = array();
		// Get the quiz number from the question ID so we can find videos that are related to the quiz that this question is in
		$quizNumber = self::questionInformation($questionId)["quizNumber"];
		foreach ($videos as $video) {
			if ($video['Quiz #'] == $quizNumber) {
				$relatedVideos []= $video;
			}
		}
		return $relatedVideos;
	}

	// Returns an array of information about a given question ID with format {quiz number}.{question number}
		// Array with quizNumber, questionNumber, assessmentId, and questionType (and options if multiple_choice)
		// If given questionId is not valid, it returns false
	static public function questionInformation($questionId) {
		// Load question information mapping
		$questionInfo = CSVHelper::parseWithHeaders('csv/questions.csv');

		// Split up quiz id and question id from format 12.1 (quizNumber.questionNumber)
		$idParts = explode(".", $questionId);
		// Make sure we have a (at least format-wise) valid question id
		if (count($idParts) != 2) {
			return false;
		}
		$quizNumber = $idParts[0];
		$questionNumber = $idParts[1];

		// Get row from question info csv
		$questionRow = $questionInfo[multi_array_search($questionInfo, ["Quiz Number" => $quizNumber, "Question Number" => $questionNumber])[0]];

		// Get assessment id from quiz id
		$assessmentId = $questionRow["OA Quiz ID"];

		// Get question type: multiple_choice, short_answer, or essay
		$questionType = $questionRow["Type"];

		$question = [
			"quizNumber" => $quizNumber,
			"questionNumber" => $questionNumber,
			"assessmentId" => $assessmentId,
			"questionType" => $questionType
		];

		// If a multiple choice question, add the number of options the question has
		if ($questionType == "multiple_choice") {
			$question["options"] = $questionRow["Multiple Choice Options"];
		}
		return $question;
	}

}
