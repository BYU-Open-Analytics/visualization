<?php

use Phalcon\Mvc\Controller;
include __DIR__ . "/../library/array_functions.php";

class StudentInspectorStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Student Inspector Stats');
	}

	// For examples of getting data from mongo and postgres and different calculations, see ScatterplotRecommenderStatsController.php and StudentSkillsStatsController.php in this folder

	public function studentsAction($scope = 'unit', $groupingId = '3', $debug = false) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		$classHelper = new ClassHelper();
		$masteryHelper = new MasteryHelper();
		$statementHelper = new StatementHelper();
		$students = $classHelper->allStudents();
		$studentInfo = [];
		for ($i=0; $i<10; $i++) {
			// For second parameter of what to query, see http://php.net/manual/en/mongocollection.find.php
			$statements = $statementHelper->getStatements("ayamel",[
				'statement.actor.name' => $students[$i],
			], [ 'statement.object.id' => true, ]
			);
			$count = $statements["cursor"]->count();

			$studentInfo []= ["name" => $students[$i], "count" => $count];
		}

		echo json_encode($studentInfo);
	}
}


