<?php

use Phalcon\Mvc\Controller;

// Stats and calculations loaded via ajax and used in the student inspector dashboard for the instructor

class StudentInspectorStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Student Inspector Stats');
	}

	public function studentCountAction(){
		$classHelper = new ClassHelper();
		echo json_encode(count( $classHelper->allStudents()));
	}
	// For examples of getting data from mongo and postgres and different calculations, see ScatterplotRecommenderStatsController.php and StudentSkillsStatsController.php in this folder

	public function studentsAction($start,$finish) {
		$this->view->disable();
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		if (!$context->valid) {
			echo '[{"error":"Invalid lti context"}]';
			return;
		}
		$timeArray[] = 280;
		$classHelper = new ClassHelper();
		$masteryHelper = new MasteryHelper();
		$statementHelper = new StatementHelper();
		$recent_concepts = MappingHelper::conceptsWithin2Weeks();
		$students = $classHelper->allStudents();
//		$students = ["John Logie Baird","me"];
		$studentInfo = [];
		$maxCount = 0;
		//count($students)
		for ($i=$start; $i < $finish; $i++) {
			$startTime = microtime(true);
			$studentAverages = StudentMasteryHistory::findFirst([
				"conditions" => "email = ?1",
				"bind" => array(1 => $students[$i]),
				"order" => 'time_stored DESC'
			]);
			// For second parameter of what to query, see http://php.net/manual/en/mongocollection.find.php
			$visStatements = $statementHelper->getStatements("visualization",[
				'statement.actor.name' => $students[$i],
			], [ 'statement.object.id' => true, ]
			);
			$hints = $statementHelper->getStatements("openassessments",[
				'statement.actor.name' => $students[$i],
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/showed-hint',
			], [ 'statement.object.id'=> true, ]
			);
			$showAnswer= $statementHelper->getStatements("openassessments",[
				'statement.actor.name' => $students[$i],
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/showed-answer',
			], [ 'statement.object.id'=> true, ]
			);
			$questionsAnswered= $statementHelper->getStatements("openassessments",[
				'statement.actor.name' => $students[$i],
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
			], [ 'statement.object.id'=> true, 'statement.context.extensions' => true ]
			);
			$hintsShowed = $hints["cursor"]->count();
			$answersShowed = $showAnswer["cursor"]->count();
			$high =0; $medium =0; $low=0;
			$attempts = $questionsAnswered["cursor"]->count();
			$correct = 0;
			foreach($questionsAnswered["cursor"] as $confidenceCheck){
				$confidenceCheck = StatementHelper::replaceHtmlEntity($confidenceCheck, true);
				if($confidenceCheck['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/correct'] == true){
					$correct++;
				}
				if(array_key_exists('http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level',$confidenceCheck['statement']['context']['extensions'])){
					if($confidenceCheck['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level']=="high"){
						$high++;}
					if($confidenceCheck['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level']=="medium"){
						$medium++;}
					if($confidenceCheck['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level']=="low"){
						$low++;}
				}
			}
			$commonConfidence = max($high, $medium, $low);
			if($commonConfidence == $high)
				$medianConfidence = "High";
			else if($commonConfidence == $medium)
				$medianConfidence = "Medium";
			else
				$medianConfidence = "Low";
			$count = $visStatements["cursor"]->count();
			$escapedString = str_replace("'","", $students[$i]);
		//	echo $escapedString;
			$lastHistory = VideoHistory::findFirst([
					"student = '$escapedString'",
					"order" => "time_stored DESC"
				]);
			$vidPercent = $lastHistory->vidpercentage;
			if($count > $maxCount && $students[$i] != 'John Logie Baird'){
				$maxCount = $count;
			}
			if(!is_object($studentAverages)){
				$newStudent = ["name" => $students[$i], "average" => 0, "count" => $count, "vPercentage" => $vidPercent, "correct" => $correct, "attempts" => $attempts, "hintsShowed" => $hintsShowed, "answersShowed" => $answersShowed, "confidence" => $medianConfidence ];
			}
			else{

				$newStudent = ["name" => $studentAverages->email, "average" => $studentAverages->recent_average,"count" => $count,"vPercentage" => $vidPercent, "correct" => $correct, "attempts" => $attempts, "hintsShowed" => $hintsShowed, "answersShowed" => $answersShowed, "confidence" => $medianConfidence];
			}
			if($students[$i] != 'John Logie Baird' && $students[$i] != 'Bob Bodily'){ 
				$studentInfo []=$newStudent;}
			$endTime = microtime(true);
			$timeArray[$i] = $endTime - $startTime;
			//echo "Execution time: " . ($endTime - $startTime) ." seconds\n";
		}
		$avg = 0;
		foreach($timeArray as $time){
			$avg += $time;
		}
		$avg = $avg/280;
	//	echo "Average Time: ".$avg." seconds\n";
		//Sorts the students by their recent mastery average, from highest to lowest.
		usort($studentInfo, function($student1,$student2){
			return $student1["average"] <= $student2["average"];
		});
		$firstRow = ["max" => $maxCount];
		array_unshift($studentInfo, $firstRow);
		echo json_encode($studentInfo);
	}
}
