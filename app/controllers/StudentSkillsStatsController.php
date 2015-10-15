<?php

use Phalcon\Mvc\Controller;

class StudentSkillsStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Student Skills Stats');
	}

	public function skillsAction($raw = false, $debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		$skillsHelper = new SkillsHelper();

		$stats = [
		    'student' => [
			['id' => 'time', 'score' => $skillsHelper->calculateTimeScore($context->getUserEmail(), $raw, $debug)],
			['id' => 'activity', 'score' => $skillsHelper->calculateActivityScore($context->getUserEmail(), $raw, $debug)],
			['id' => 'consistency', 'score' => $skillsHelper->calculateConsistencyScore($context->getUserEmail(), $raw, $debug)],
			['id' => 'awareness', 'score' => $skillsHelper->calculateAwarenessScore($context->getUserEmail(), $raw, $debug)],
			['id' => 'deepLearning', 'score' => $skillsHelper->calculateDeepLearningScore($context->getUserEmail(), $raw, $debug)],
			['id' => 'persistence', 'score' => $skillsHelper->calculatePersistenceScore($context->getUserEmail(), $raw, $debug)],
		    ],
		    'class' => [
			['id' => 'time', 'score' => 5],
			['id' => 'activity', 'score' => 5],
			['id' => 'consistency', 'score' => 5],
			['id' => 'awareness', 'score' => 5],
			['id' => 'deepLearning', 'score' => 5],
			['id' => 'persistence', 'score' => 5],
		    ]
		];
		echo json_encode($stats);
	}

	public function time_graphAction($skill) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		if (empty($skill)) {
			echo '[{"error":"No skill specified"}]';
			return;
		}

		$result = [];
		for ($i=0; $i<50; $i++) {
			$result []= ['student', date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - $i, date("Y"))), rand(1,100) / 10];
		}
		for ($i=0; $i<50; $i++) {
			$result []= ['class', date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - $i, date("Y"))), rand(20,80) / 10];
		}

		// Output data as csv so that we only have to send header information once
		header("Content-Type: text/csv");
		$output = fopen("php://output", "w");
		fputcsv($output, ["scope", "date", "score"]);
		foreach ($result as $row) {
			fputcsv($output, $row); // here you can change delimiter/enclosure
		}
		fclose($output);
	}
}
