<?php

use Phalcon\Mvc\Controller;

// Stats and calculations loaded via ajax and used in the student inspector dashboard for the instructor

class StudentInspectorStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Student Inspector Stats');
	}

	// For examples of getting data from mongo and postgres and different calculations, see ScatterplotRecommenderStatsController.php and StudentSkillsStatsController.php in this folder

	public function studentsAction() {
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
		$concepts = MappingHelper::allConcepts();
		$students = $classHelper->allStudents();
		$studentInfo = [];
		for ($i=0; $i < 1; $i++) {
			$studentAverages = StudentMasteryHistory::findFirst([
				"email = 'me'",
				"order" => 'recent_average DESC'
			]);
			// For second parameter of what to query, see http://php.net/manual/en/mongocollection.find.php
		#	$statements = $statementHelper->getStatements("ayamel",[
		#		'statement.actor.name' => $students[$i],
		#	], [ 'statement.object.id' => true, ]
		#	);
		#	$count = $statements["cursor"]->count();
			$newStudent = ["name" => $studentAverages->email, "average" => $studentAverages->recent_average];
			$studentInfo []=$newStudent; 
		}

		echo json_encode($studentInfo);
	}
}


