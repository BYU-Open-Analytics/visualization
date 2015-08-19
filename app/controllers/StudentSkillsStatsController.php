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
		    'student' => [
			['axis' => 'timeManagement', 'value' => rand(1,10)],
			['axis' => 'onlineActivity', 'value' => rand(1,10)],
			['axis' => 'selfRegulation', 'value' => rand(1,10)],
			['axis' => 'selfEfficacy', 'value' => rand(1,10)],
			['axis' => 'consistency', 'value' => rand(1,10)],
			['axis' => 'earlyBird', 'value' => rand(1,10)]
		    ],
		    'class' => [
			['axis' => 'timeManagement', 'value' => 5],
			['axis' => 'onlineActivity', 'value' => 5],
			['axis' => 'selfRegulation', 'value' => 5],
			['axis' => 'selfEfficacy', 'value' => 5],
			['axis' => 'consistency', 'value' => 5],
			['axis' => 'earlyBird', 'value' => 5]
		    ]
		];
		echo json_encode($stats);
	}
}
