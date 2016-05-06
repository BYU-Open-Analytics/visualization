<?php

use Phalcon\Mvc\Controller;

// Stats and calculations loaded via ajax and used in the class dashboard for the instructor
//
class ClassStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Instructor Class Stats');
	}
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

	public function questionsAction($concept = 1){

	}
	// For examples of getting data from mongo and postgres and different calculations, see ScatterplotRecommenderStatsController.php and StudentSkillsStatsController.php in this folder
	public function conceptsAction($unit = '1'){
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		$concepts = MappingHelper::conceptsInUnit($unit);
		$maxPercentage = 0;
		$conceptArray = [];
		foreach($concepts as $concept){
			$conceptID = $concept['Lecture Number'];
			$historicalConceptMasteryScores = ClassConceptHistory::find([
				"concept_id = '$conceptID'",
				"order" => 'time_stored DESC',
			]);
			$newConcept = ["id" => $conceptID, "title" => $concept["Concept Title"], "history" => []];
			foreach($historicalConceptMasteryScores as $score){
				$newConcept["history"] [] = ["date" => $score->time_stored, "average" => $score->average_mastery, "percent" => $score->videopercentage];
			}
			$newConcept["history"] = $newConcept["history"][0];
			if($newConcept["history"]["percent"] > $maxPercentage){
				$maxPercentage = $newConcept["history"]["percent"];
			}
			$conceptsArray []= $newConcept;
		}
		$firstRow = ["max"=> $maxPercentage];
		array_unshift($conceptsArray,$firstRow);
		echo json_encode($conceptsArray);
	}
}
