<?php

use Phalcon\Mvc\Controller;

class StudentSkillsStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Student Skills Stats');
	}

	public function skillsAction() {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		$stats = [
		    'student' => [
			['id' => 'time', 'score' => rand(1,100) / 10],
			['id' => 'activity', 'score' => rand(1,100) / 10],
			['id' => 'regulation', 'score' => rand(1,100) / 10],
			['id' => 'efficacy', 'score' => rand(1,100) / 10],
			['id' => 'consistency', 'score' => rand(1,100) / 10],
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
