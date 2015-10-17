<?php

use Phalcon\Mvc\Controller;

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
	public function correctAction($studentId, $assessmentId, $questionNumber) {
		$masteryHelper = new MasteryHelper();
		$masteryHelper::countCorrectAttemptsForQuestion($studentId, $assessmentId, $questionNumber, true);
		
	}
	public function dbAction() {
		$this->view->disable();
		echo "<pre>Skill score saving test: ";
		$sh = new SkillsHelper();
		for ($i=0; $i<100; $i++) {
			$sh-> saveRawSkillScore($i."jbaird@uni.ac.uk","consistency",rand(0,100));
			$sh-> saveRawSkillScore($i."jbaird@uni.ac.uk","activity",rand(0,100));
			$sh-> saveRawSkillScore($i."jbaird@uni.ac.uk","time",rand(0,100));
			$sh-> saveRawSkillScore($i."jbaird@uni.ac.uk","persistence_attempts",rand(0,100));
			$sh-> saveRawSkillScore($i."jbaird@uni.ac.uk","persistence_watched",rand(0,100));
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
		echo $sh->calculateDeepLearningScore($context->getUserEmail(), true, true);
	}

	public function timeAction() {
		$this->view->disable();
		$context = $this->getDI()->getShared('ltiContext');
		$sh = new SkillsHelper();
		echo $sh->calculateTimeScore($context->getUserEmail(), true, true);
	}
}
