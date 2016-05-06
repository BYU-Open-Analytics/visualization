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

	NOTE: Lecture Number and Concept ID are synonymous; there is a 1:1 relationship, but it is always called Lecture Number in the CSV files
	*/
	// Array indexes are sometimes weird, so look for the headings in the CSV files

	// Functions that return a 2D array will be an array of rows from the CSV files.

	// Returns a 2D array of all units
	static public function allUnits() {
		// Get unit list from the "Unit Number" column from the concepts mapping
		$allConcepts = CSVHelper::parseWithHeaders('csv/concepts.csv');
		$unitList = array_column($allConcepts, "Unit Number");
		// This is a list like 1,1,1,1,1,2,2,2,2,2,3,3,3,3,4,4,4, so get just unique unit numbers
		$unitList = array_unique($unitList);
		// Give each unit a title based on its number
		$units = [];
		foreach ($unitList as $unit) {
			$units []= ["Unit Number" => $unit, "Unit Title" => "Unit {$unit}"];
		}
		return $units;
	}

	// Returns a 2D array of all concepts
	static public function allConcepts() {
		$allConcepts = CSVHelper::parseWithHeaders('csv/concepts.csv');
		return $allConcepts;
	}
	static public function currentUnit(){
		## Note that it is important that the concepts are listed in chronological order in concepts.csv for this method to work!
		## The dates must also be in the format mm/dd/yyyy. If you want to change this, edit the string concat on line 61.
		$allConcepts= CSVHelper::parseWithHeaders('csv/concepts.csv');
		$unitList = array_column($allConcepts, "Unit Number");
		$numOfUnits = count(array_unique($unitList));
		$lastDates = [];
		//Here we loop through all of the units. The purpose of this loop is two-fold: discover how many units we have, and
		//put the latest date that is still in the unit into our lastDates[] array.
		for($loop = 1; $loop <= $numOfUnits; $loop++){
			$conceptsInUnit = array_filter($allConcepts, function($concept) use ($loop) {
				return ($concept["Unit Number"] == "{$loop}");
			});
			$latest = count($conceptsInUnit);
			$lastDates[] = end($conceptsInUnit)["Date"];
		}
		$unit = 0;
		$today = getdate();
		//This is where the formatting comes in.
		$todayMDY = $today["mon"]."/".$today["mday"]."/".$today["year"];
		$count = 1;
		foreach($lastDates as $date){
			if($todayMDY > $date){
				$unit = $count+1;
			}
			$count++;
		}
		if($unit > $numOfUnits)
			$unit = $numOfUnits;
		return $unit;

	}
	// Returns a 2D array of concepts
	static public function conceptsInUnit($unitNumber) {
		$allConcepts = CSVHelper::parseWithHeaders('csv/concepts.csv');
		// Filter concepts to the given unit
		$concepts = array_filter($allConcepts, function($concept) use ($unitNumber) {
			return ($concept["Unit Number"] == $unitNumber);
		});
		return $concepts;
	}

	// Returns a 2D array of questions for the given concept (pass either concept row, or lecture number/concept id)
	static public function questionsInConcept($concept) {
		$lectureNumber = $concept;
		// If the concept row was passed in, extract lecture number
		if (is_array($concept)) {
			$lectureNumber = $concept["Lecture Number"];
		}
		// Get all questions
		$allQuestions = CSVHelper::parseWithHeaders('csv/questions.csv');
		// Filter questions to ones associated with this concept (lecture number)
		$conceptQuestions = array_filter($allQuestions, function($question) use ($lectureNumber) {
			return ($question["Lecture Number"] == $lectureNumber);
		});

		return $conceptQuestions;
	}

	// Returns a 2D array of questions for the given array of concepts (pass either an array of concept rows, or lecture numbers/concept ids)
	static public function questionsInConcepts($concepts) {
		$questions = array();
		// Get questions in each of the concepts in the given array of concepts
		foreach ($concepts as $concept) {
			$questions = array_merge($questions, self::questionsInConcept($concept));
		}
		// Remove any duplicate questions (would only happen if the same concept is in the array >1 time)
		$questions = array_unique($questions);
		return $questions;
	}

	// Returns an array of videos for the given concept (pass either concept row, or lecture number/concept id)
	static public function videosForConcept($concept) {
		$lectureNumber = $concept;
		// If the concept row was passed in, extract lecture number
		if (is_array($concept)) {
			$lectureNumber = $concept["Lecture Number"];
		}
		// Get all videos
		$allVideos = CSVHelper::parseWithHeaders('csv/videos.csv');
		$conceptVideos = array();
		// Filter all videos to ones in this concept
		foreach ($allVideos as $video) {
			if ($video['Lecture Number'] == $lectureNumber) {
				$conceptVideos []= $video;
			}
		}
		return $conceptVideos;
	}

	static public function videosInConceptLength($concept){
		$videos = MappingHelper::videosForConcept($concept);
		$length = 0;
		foreach($videos as $vid){
			$length += $vid["Video Length"];
		}
		return $length;
	}

	// Returns a 2D array of videos for the given array of concepts (pass either an array of concept rows, or lecture numbers/concept ids)
	static public function videosForConcepts($concepts) {
		$videos = array();
		foreach ($concepts as $concept) {
			$videos = array_merge($videos, self::videosForConcept($concept));
		}
		$videos = array_unique($videos, SORT_REGULAR);
		return $videos;
	}

	// Returns a 2D array of concepts that have a date within the past two weeks
	static public function conceptsWithin2Weeks(){
		$allConcepts = self::allConcepts();
		$returnConcepts = [];
		$today = strtotime("today");
		//And with a wave of the hands,
		$weeksAgo = $today - 1209600;
		foreach($allConcepts as $concept){
			if(strtotime($concept["Date"]) >= $weeksAgo && strtotime($concept["Date"]) <= $today){
				$returnConcepts [] = $concept;
			}
		}
		return $returnConcepts;
	}
	// Returns a 2D array of resources for a given concept (pass either concept row, or lecture number/concept id)
	static public function resourcesForConcept($concept) {
		$lectureNumber = $concept;
		// If the concept row was passed in, extract lecture number
		if (is_array($concept)) {
			$lectureNumber = $concept["Lecture Number"];
		}
		$allResources = CSVHelper::parseWithHeaders('csv/resources.csv');
		$conceptResources = array();
		// Find all resources for this concept
		foreach ($allResources as $resource) {
			if ($resource['Lecture Number'] == $lectureNumber) {
				$conceptResources []= $resource;
			}
		}
		return $conceptResources;
	}


	// Returns an array of information about a given question ID with format {assessment ID}.{question number}
		// Array with quizNumber, questionNumber, assessmentId, and questionType (and options if question is multiple_choice)
		// If given questionId is not valid, it returns false
	static public function questionInformation($questionId) {
		// Split up quiz id and question id from format 12.1 (quizNumber.questionNumber)
		$idParts = explode(".", $questionId);
		// Make sure we have a (at least format-wise) valid question id
		if (count($idParts) != 2) {
			return false;
		}
		$assessmentID = $idParts[0];
		$questionNumber = $idParts[1];

		// Get row from question info CSV
		// Load question information mapping
		$allQuestions = CSVHelper::parseWithHeaders('csv/questions.csv');

		// Search questions for one with this assessment ID and question number
		$searchResults = multi_array_search($allQuestions, ["OA Quiz ID" => $assessmentID, "Question Number" => $questionNumber]);
		// If there's not one, return false
		if (count($searchResults) <= 0) {
			return false;
		}
		// If we have one, get it
		$questionRow = $allQuestions[$searchResults[0]];

		/*
		// Get assessment id from quiz id
		$assessmentId = $questionRow["OA Quiz ID"];

		// Get question type: multiple_choice, short_answer, or essay
		$questionType = $questionRow["Type"];

		$question = [
			"questionNumber" => $questionNumber,
			"assessmentId" => $assessmentId,
			"questionType" => $questionType
		];

		// If a multiple choice question, add the number of options the question has
		if ($questionType == "multiple_choice") {
			$question["options"] = $questionRow["Multiple Choice Options"];
		}
		 */
		return $questionRow;
	}

}
