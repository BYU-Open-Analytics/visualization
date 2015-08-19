<?php

use Phalcon\Mvc\Controller;

class StudentSkillsStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Student Skills Stats');
	}

	public function indexAction() {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		$stats = [
			'timeManagement' => rand(1,10),
			'onlineActivity' => rand(1,10),
			'selfRegulation' => rand(1,10),
			'selfEfficacy' => rand(1,10),
			'consistency' => rand(1,10),
			'earlyBird' => rand(1,10)
		];
		echo json_encode($stats);
	}
}
