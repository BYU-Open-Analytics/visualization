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
			['axis' => 'time', 'value' => rand(1,10)],
			['axis' => 'activity', 'value' => rand(1,10)],
			['axis' => 'regulation', 'value' => rand(1,10)],
			['axis' => 'efficacy', 'value' => rand(1,10)],
			['axis' => 'consistency', 'value' => rand(1,10)],
		    ],
		    'class' => [
			['axis' => 'time', 'value' => 5],
			['axis' => 'activity', 'value' => 5],
			['axis' => 'regulation', 'value' => 5],
			['axis' => 'efficacy', 'value' => 5],
			['axis' => 'consistency', 'value' => 5],
		    ]
		];
		echo json_encode($stats);
	}
}
