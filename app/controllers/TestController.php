<?php

use Phalcon\Mvc\Controller;

// These are just used for development purposes, for testing new code isolated outside the context of an actual dashboard

class TestController extends Controller
{
	public function initialize() {
		// All our testing things need LTI context and no view
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->context = $context;
		if (!$context->valid) {
			return;
		}
		$this->view->disable();
	}

	public function currentUnitAction(){
		$mapper = new MappingHelper();
		$mapper->currentUnit();
	}
	public function contextAction(){
		$context = $this->getDI()->getShared('ltiContext');
		// Send a statement tracking that they viewed this page
		$statementHelper = new StatementHelper();
		$statement = $statementHelper->buildStatement([
			"statementName" => "dashboardLaunched",
			"dashboardID" => "dashboard_select",
			"dashboardName" => "Dashboard Selector",
			"verbName" => "launched",
		], $context);
		if($statement){
			echo "working\n";
			$res =$statementHelper->sendStatements("visualization", [$statement]);
			echo $res;
		}
		echo $this->getDI()->getShared('ltiContext')->getCourseKey();
	}

	public function cacheTestAction(){
			$classHelper = new ClassHelper();
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
			if(!$lastHistory);{
				echo "last history is false";
			}
			if(gettype($lastHistory))
			if (date("Y-m-d") == date("Y-m-d", strtotime($lastHistory->time_stored))) {
				echo "    History already saved today for $studentId\n";
				continue;
			}
	}
}

	public function correctAction($studentId, $assessmentId, $questionNumber) {
		$masteryHelper = new MasteryHelper();
		$masteryHelper::countCorrectAttemptsForQuestion($studentId, $assessmentId, $questionNumber, true);

	}
	public function dbAction() {
		$this->view->disable();
		echo "<pre>Skill score saving test: ";
		$sh = new SkillsHelper();
		for ($i=0; $i<100; $i++) {
			$sh-> saveRawSkillScore($i."John Logie Baird","consistency",rand(0,100));
			$sh-> saveRawSkillScore($i."John Logie Baird","activity",rand(0,100));
			$sh-> saveRawSkillScore($i."John Logie Baird","time",rand(0,100));
			$sh-> saveRawSkillScore($i."John Logie Baird","persistence_attempts",rand(0,100));
			$sh-> saveRawSkillScore($i."John Logie Baird","persistence_watched",rand(0,100));
		}
	}

	public function scaledAction() {
		$this->view->disable();
		echo "<pre>Scaled score test: ";
		$sh = new SkillsHelper();
		echo $sh->getScaledSkillScore("jbaird@uni.ac.uk", "persistence", true);

	}

	public function deepLearningAction() {
		$this->view->disable();
		$context = $this->getDI()->getShared('ltiContext');
		$sh = new SkillsHelper();
		echo $sh->calculateDeepLearningScore($context->getUserName(), true, true);
	}

	public function timeAction() {
		$this->view->disable();
		$context = $this->getDI()->getShared('ltiContext');
		$sh = new SkillsHelper();
		echo $sh->calculateTimeScore($context->getUserName(), true, true);
	}

	public function mappingsAction() {
		echo "<pre>";
		echo "All units\n";
		print_r(MappingHelper::allUnits());
		echo "<hr>Concepts in unit 3\n";
		print_r(MappingHelper::conceptsInUnit("3"));
		echo "<hr>Questions in concept (lecture number) 1\n";
		print_r(MappingHelper::questionsInConcept("1"));
		echo "<hr>Question info for question 78.4\n";
		print_r(MappingHelper::questionInformation("78.4"));
		echo "<hr>Videos for concept 9\n";
		print_r(MappingHelper::videosForConcept("9"));
		echo "<hr>Resources for concept 1\n";
		print_r(MappingHelper::resourcesForConcept("1"));
		echo "<hr>Concepts within the past 2 weeks: \n";
		print_r(MappingHelper::conceptsWithin2Weeks());
	}

	public function newVideoAction() {
		$context = $this->getDI()->getShared('ltiContext');
		echo "<pre>";
		echo "Calculating video percentage for concept 6.2\n";
		$masteryHelper = new MasteryHelper();
		echo $masteryHelper::calculateUniqueVideoPercentageForConcept($context->getUserName(), "6.2", true);
	}

}
