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
		$sh-> saveRawSkillScore("jbaird@uni.ac.uk","consistency",40);

		$sh-> saveRawSkillScore("1baird@uni.ac.uk","activity",86);

		$sh-> saveRawSkillScore("2baird@uni.ac.uk","time",22);
		$sh-> saveRawSkillScore("2baird@uni.ac.uk","time",33);
		$sh-> saveRawSkillScore("2baird@uni.ac.uk","consistency",34);

		$sh-> saveRawSkillScore("3baird@uni.ac.uk","consistency",45);

		$sh-> saveRawSkillScore("4baird@uni.ac.uk","consistency",46);

		$sh-> saveRawSkillScore("5baird@uni.ac.uk","consistency",47);


	}
}
