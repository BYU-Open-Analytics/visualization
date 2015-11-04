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
		//$studentIds = $classHelper->allStudents();
		$studentIds = ["John Logie Baird"];
		
		$units = ["3", "4"];
		// Go through each student and calculate unit mastery scores
		foreach ($studentIds as $studentId) {
			// See if we've already scored mastery scores for this student on the current day (this script just runs multiple times, until a better method to get around 60 second execution time limit is devised)
			$lastHistory = MasteryHistory::findFirst([
					"email = '$studentId'",
					"order" => "time_stored DESC"
				]);
			if (date("Y-m-d") == date("Y-m-d", strtotime($lastHistory->time_stored))) {
				echo "History already saved today for $studentId";
				continue;
			}
			$scores = [];
			foreach ($units as $unit) {
				$concepts = MappingHelper::conceptsInChapters(MappingHelper::chaptersInUnit($unit));
				$unitScore = 0;
				foreach ($concepts as $c) {
					$unitScore += $masteryHelper::calculateConceptMasteryScore($studentId, $c["Section Number"], $debug);
				}
				$unitScore = $unitScore / count($concepts);
				$scores[$unit] = $unitScore;
			}
			if ($debug) {
				echo "Scores for student $studentId \n";
				print_r($scores);
			}
			$history = new MasteryHistory();
			$history->email = $studentId;
			$history->unit3 = $scores["3"];
			$history->unit4 = $scores["4"];

			if ($history->create() == false) {
				echo "*** Error saving mastery history for $studentId\n";
			} else {
				//if ($debug) {
					echo "    Successfully saved history for $studentId\n";
				//}
			}
		}

		// Print total time taken
		$endTime = microtime(true);
		echo "Execution time: " . ($endTime - $startTime) . " seconds\n";
	}
}
