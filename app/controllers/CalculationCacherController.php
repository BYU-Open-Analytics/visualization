<?php

use Phalcon\Mvc\Controller;

class CalculationCacherController extends Controller
{
	public function initialize() {
		$this->view->disable();
	}
	public function indexAction() {
		die("Please provide an action, e.g. http://vis.site/calculation_cacher/daily");
	}

	// Poorly named, since all of these are run daily. But this one saves skill history for each student
	public function dailyAction() {
		$config = $this->getDI()->getShared('config');
		if (!isset($_GET["p"])) {
			die("No history saver password provided.");
		}
		if ($_GET["p"] != $config->historySaverPassword) {
			die("Invalid history saver password provided.");
		}

		// We want to time this
		$startTime = microtime(true);

		$raw = false;
		$debug = false;
		$skillsHelper = new SkillsHelper();
		$classHelper = new ClassHelper();
		$studentIds = $classHelper->allStudents();
		//$studentIds = ["John Logie Baird"];

		// Update skill scores for every student, and save history
		foreach ($studentIds as $studentId) {
			// TODO make this more efficient
			$history = new SkillHistory();
			$history->email = $studentId;
			$history->time = $skillsHelper->calculateTimeScore($studentId, $raw, $debug);
			$history->activity = $skillsHelper->calculateActivityScore($studentId, $raw, $debug);
			$history->consistency = $skillsHelper->calculateConsistencyScore($studentId, $raw, $debug);
			$history->awareness = $skillsHelper->calculateAwarenessScore($studentId, $raw, $debug);
			$history->deep_learning = $skillsHelper->calculateDeepLearningScore($studentId, $raw, $debug);
			$history->persistence = $skillsHelper->calculatePersistenceScore($studentId, $raw, $debug);

			if ($history->create() == false) {
				echo "*** Error saving history for $studentId\n";
			} else {
				//echo "    Successfully saved history for $studentId\n";
			}
		}

		// Print total time taken
		$endTime = microtime(true);
		echo "Execution time: " . ($endTime - $startTime) . " seconds\n";
	}

	public function dailyConceptAverages(){

	}
	// Stores the unit 3 and 4 mastery scores for every student
	public function dailyMasteryAction() {
		$config = $this->getDI()->getShared('config');
		if (!isset($_GET["p"])) {
			die("No history saver password provided.");
		}
		if ($_GET["p"] != $config->historySaverPassword) {
			die("Invalid history saver password provided.");
		}

		ini_set('max_execution_time', 300);
		set_time_limit(300);
		// We want to time this
		$startTime = microtime(true);

		$debug = false;
		$classHelper = new ClassHelper();
		$masteryHelper = new MasteryHelper();
		$studentIds = $classHelper->allStudents();
		//$studentIds = ["John Logie Baird"];

		// Calculate an overall mastery score for these units, as well as an average for concepts over the past 2 weeks
		$units = ["1", "2", "3", "4", "recent"];
		// Go through each student and calculate unit mastery scores
		foreach ($studentIds as $studentId) {
			// See if we've already scored mastery scores for this student on the current day (this script just runs multiple times, until a better method to get around 60 second execution time limit is devised)
			$lastHistory = StudentMasteryHistory::findFirst([
					"email = '$studentId'",
					"order" => "time_stored DESC"
				]);
			if (date("Y-m-d") == date("Y-m-d", strtotime($lastHistory->time_stored))) {
				echo "    History already saved today for $studentId\n";
				continue;
			}
			$scores = [];
			foreach ($units as $unit) {
				// We fetch recent concepts differently from the rest of the units
				if ($unit == "recent") {
					$concepts = MappingHelper::conceptsWithin2Weeks();
				} else {
					$concepts = MappingHelper::conceptsInUnit($unit);
				}
				$unitScore = 0;
				foreach ($concepts as $c) {
					$unitScore += $masteryHelper::calculateConceptMasteryScore($studentId, $c, $debug);
				}
				$unitScore = $unitScore / count($concepts);
				$scores[$unit] = $unitScore;
			}
			if ($debug) {
				echo "Scores for student $studentId \n";
				print_r($scores);
			}
			$history = new StudentMasteryHistory();
			$history->email = $studentId;
			$history->unit1 = $scores["1"];
			$history->unit2 = $scores["2"];
			$history->unit3 = $scores["3"];
			$history->unit4 = $scores["4"];
			$history->recent_average = $scores["recent"];

			if ($history->create() == false) {
				echo "*** Error saving mastery history for $studentId\n";
			} else {
					echo "    Successfully saved history for $studentId\n";
			}
		}

		// Print total time taken
		$endTime = microtime(true);
		echo "Execution time: " . ($endTime - $startTime) . " seconds\n";
	}

	// Stores the class average for each concept
	public function classConceptHistoryAction() {
		$config = $this->getDI()->getShared('config');
		if (!isset($_GET["p"])) {
			die("No history saver password provided.");
		}
		if ($_GET["p"] != $config->historySaverPassword) {
			die("Invalid history saver password provided.");
		}

		// We want to time this
		$startTime = microtime(true);

		$raw = false;
		$debug = false;
		$classHelper = new ClassHelper();
		$masteryHelper = new MasteryHelper();
		$allConcepts = MappingHelper::allConcepts();
		$studentIds = $classHelper->allStudents();
		//$studentIds = ["John Logie Baird"];

		foreach ($allConcepts as $concept) {

			$classHelper = new ClassHelper();
			$masteryHelper = new MasteryHelper();
			$studentIds = $classHelper->allStudents();
			$sum = 0;
			foreach ($studentIds as $student) {
				$sum += $masteryHelper->calculateUniqueVideoPercentageForConcept($student,$concept);
			}
			$avg = $sum / count($studentIds);
			echo $avg;
			$lecNum = $concept["Lecture Number"];
			$lastHistory = ClassConceptHistory::findFirst([
					"concept_id = $lecNum",
					"order" => "time_stored DESC"
				]);
			if (date("Y-m-d") == date("Y-m-d", strtotime($lastHistory->time_stored))) {
				echo "    History already saved today for concept #$lecNum\n";
				continue;
			}
			$scoreSum = 0;
			$scoreCount = 0;
			// Check if it's a concept in the future
			$today = strtotime("today");
			$includeZero = true;
			if (strtotime($concept["Date"]) > $today) {
				$includeZero = false;
			}
			// Calculate concept score for each student
			foreach ($studentIds as $studentId) {
				// Calculate score
				$score = $masteryHelper->calculateConceptMasteryScore($studentId, $concept["Lecture Number"], $debug);
				// Add score
				if ($score == 0) {
					if ($includeZero) {
						$scoreCount++;
					}
				} else {
					$scoreCount++;
					$scoreSum += $score;
				}
			}
			// Calculate average
			$average = $scoreSum / $scoreCount;
			// Store concept average
			$conceptHistory = new ClassConceptHistory();
			$conceptHistory->concept_id = $concept["Lecture Number"];
			$conceptHistory->average_mastery = $average;
			$conceptHistory->videopercentage = $avg;

			if ($conceptHistory->create() == false) {
				echo "*** Error saving concept history for $concept\n";
			} else {
			}

		}

		// Print total time taken
		$endTime = microtime(true);
		echo "Execution time: " . ($endTime - $startTime) . " seconds\n";
	}
	public function videoHistoryAction() {
		$config = $this->getDI()->getShared('config');
		if (!isset($_GET["p"])) {
			die("No history saver password provided.");
		}
		if ($_GET["p"] != $config->historySaverPassword) {
			die("Invalid history saver password provided. Should be $config->historySaverPassword");
		}

		// We want to time this
		$startTime = microtime(true);

		$raw = false;
		$debug = false;
		$classHelper = new ClassHelper();
		$masteryHelper = new MasteryHelper();
		$studentIds = $classHelper->allStudents();
		$recent_concepts = MappingHelper::conceptsWithin2Weeks();

		foreach ($studentIds as $student) {

			$lastHistory = VideoHistory::findFirst([
					"student = '$student'",
					"order" => "time_stored DESC"
				]);
			if (date("Y-m-d") == date("Y-m-d", strtotime($lastHistory->time_stored))) {
				echo "    History already saved today for student: $student\n";
				continue;
			}
			$videoPercentage = $masteryHelper->calculateUniqueVideoPercentageForConcepts($student,$recent_concepts);
			$videoHistory = new VideoHistory();
			$videoHistory->student = $student;
			$videoHistory->vidpercentage = $videoPercentage;

			if ($videoHistory->create() == false) {
				echo "*** Error saving concept history for $concept\n";
			} else {
			}

		}

		// Print total time taken
		$endTime = microtime(true);
		echo "Execution time: " . ($endTime - $startTime) . " seconds\n";
	}
}
