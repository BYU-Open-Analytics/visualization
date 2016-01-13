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
		$recent_concepts = MappingHelper::conceptsWithin2Weeks();
		$students = ["John Logie Baird"];
		//$students [] = "me";
		$studentInfo = [];
		for ($i=0; $i < count($students); $i++) {
			$studentAverages = StudentMasteryHistory::findFirst([
				"conditions" => "email = ?1",
				"bind" => array(1 => $students[$i]),
				"order" => 'recent_average DESC'
			]);
			// For second parameter of what to query, see http://php.net/manual/en/mongocollection.find.php
			$statements = $statementHelper->getStatements("visualization",[
				'statement.actor.name' => $students[$i],
			], [ 'statement.object.id' => true, ]
			);
			echo MasteryHelper::calculateUniqueVideoPercentageForConcepts($students[$i],$recent_concepts);
			
			$count = $statements["cursor"]->count();
			if(!is_object($studentAverages)){
				
				$newStudent = ["name" => $students[$i], "average" => 0, "count" => $count];
			}
			else{
				
				$newStudent = ["name" => $studentAverages->email, "average" => $studentAverages->recent_average,"count" => $count];
			}
			$studentInfo []=$newStudent; 
		}

		echo json_encode($studentInfo);
	}
}


