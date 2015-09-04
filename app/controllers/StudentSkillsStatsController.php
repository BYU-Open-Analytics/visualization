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
			['id' => 'time', 'score' => rand(1,10)],
			['id' => 'activity', 'score' => rand(1,10)],
			['id' => 'regulation', 'score' => rand(1,10)],
			['id' => 'efficacy', 'score' => rand(1,10)],
			['id' => 'consistency', 'score' => rand(1,10)],
		    ],
		    'class' => [
			['id' => 'time', 'score' => 5],
			['id' => 'activity', 'score' => 5],
			['id' => 'regulation', 'score' => 5],
			['id' => 'efficacy', 'score' => 5],
			['id' => 'consistency', 'score' => 10],
		    ]
		];
		echo json_encode($stats);
	}
}
