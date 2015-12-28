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
		echo "All chapters\n";
		print_r(MappingHelper::allChapters());
		echo "<hr> Chapters in unit 3\n";
		print_r(MappingHelper::chaptersInUnit(3));
		echo "<hr>Concepts in unit 3\n";
		print_r(MappingHelper::conceptsInChapters(MappingHelper::chaptersInUnit(3)));
		echo "<hr>Questions in concept 2.1\n";
		print_r(MappingHelper::questionsInConcept("2.1"));
		echo "<hr>Videos for question 3.8\n";
		print_r(MappingHelper::videosForQuestion("3.8"));
		echo "<hr>Question info for question 3.8\n";
		print_r(MappingHelper::questionInformation("3.8"));
		echo "<hr>Videos for concept 2.1\n";
		print_r(MappingHelper::videosForConcept("2.1"));
		echo "<hr>Resources for concept 1.5\n";
		print_r(MappingHelper::resourcesForConcept("1.5"));
		echo "New all concepts function the same as going through chapters: " . (MappingHelper::allConcepts() == MappingHelper::conceptsInChapters(MappingHelper::allChapters()));
	}

	public function newVideoAction() {
		$context = $this->getDI()->getShared('ltiContext');
		echo "<pre>";
		echo "Calculating video percentage for concept 6.2\n";
		$masteryHelper = new MasteryHelper();
		echo $masteryHelper::calculateUniqueVideoPercentageForConcept($context->getUserName(), "6.2", true);
	}

}
