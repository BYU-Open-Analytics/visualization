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
		// We want to time this
		$startTime = microtime(true);

		$config = $this->getDI()->getShared('config');


		$raw = false;
		$debug = false;
		$skillsHelper = new SkillsHelper();
		$classHelper = new ClassHelper();
		$studentIds = $classHelper->allStudents();

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
				echo "Error saving history for $studentId";
			} else {
				echo "Successfully saved history for $studentId";
			}
		}


		// Print total time taken
		$endTime = microtime(true);
		echo "Execution time: " . ($endTime - $startTime) . " seconds";
	}
}
