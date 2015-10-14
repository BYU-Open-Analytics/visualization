<?php 

use Phalcon\Mvc\User\Module;

class SkillsHelper extends Module {

	// Perform (or retrieve) calculations for a given skill for a given student

	// Percent of a student's total events between 11pm and 5am (raw percentage, that still needs to be scaled by class)
	public function calculateTimeScore($studentId) {
		return rand(0,100) / 10;
	}

	public function calculateActivityScore($studentId) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		// Aggregate, fetching the student's statements in the past two weeks for open assessments and ayamel
		$aggregation = [
			['$match' => [
				'timestamp' => array('$gte' => new MongoDate(strtotime('-2 weeks'))),
				'statement.actor.mbox' => 'mailto:'.$studentId,
				'lrs._id' => array('$in' => array( 
					$config->lrs->openassessments->id, 
					$config->lrs->ayamel->id,
				)),
			] ],
			['$group' => ['_id' => '$statement.actor.mbox', 'count' => ['$sum' => 1] ] ]
		];

		$collection = $db->statements;
		$results = $collection->aggregate($aggregation)["result"];
		// TODO store raw, and then return scaled
		return $results[0]["count"];
	}

	public function calculateConsistencyScore($studentId) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		// Aggregate, fetching the student's statements in the past two weeks for open assessments and ayamel
		// http://www.saturngod.net/articles/group-by-date-in-mongodb/
		$aggregation = [
			['$match' => [
				'timestamp' => array('$gte' => new MongoDate(strtotime('midnight -2 weeks'))),
				//'statement.actor.mbox' => 'mailto:'.$studentId,
				'lrs._id' => array('$in' => array( 
					$config->lrs->openassessments->id, 
					$config->lrs->ayamel->id,
				)),
			]],
			['$project' => [
				'y' => ['$year' => '$timestamp'],
				'm' => ['$month' => '$timestamp'],
				'd' => ['$dayOfMonth' => '$timestamp'],
			]],
			['$group' => [
				'_id' => [
					'year' => '$y',
					'month' => '$m',
					'day' => '$d',
				],
				'count' => [
					'$sum' => 1
				]
			]],
			['$sort' => [
				'_id.year' => 1,
				'_id.month' => 1,
				'_id.day' => 1,
			]]
		];

		$collection = $db->statements;
		$results = $collection->aggregate($aggregation)["result"];
		print_r($results);
		// TODO store raw, and then return scaled
		return count($results);

	}

	public function calculateAwarenessScore($studentId) {
		return rand(0,100) / 10;

	}

	public function calculateDeepLearningScore($studentId) {
		return rand(0,100) / 10;

	}

	public function calculatePersistenceScore($studentId) {
		return rand(0,100) / 10;

	}


	// Stores the given raw score for a specific skill for the given student in the PostgreSQL students table
	function saveRawSkillScore($studentId, $skillId, $rawScore) {
		// See if this student is already in the database
		echo "<hr>$studentId, $skillId, $rawScore \n";
		if ($existingStudent = Students::findFirst("email = '$studentId'")) {
			// If they do, update the existing database row
			echo $existingStudent->email;
			$existingStudent->{$skillId} = $rawScore;
			if ($existingStudent->update() == false) {
				//print_r($existingStudent->getMessages());
				return false;
			} else {
				return true;
			}
		} else {
			// If they don't, create a new student
			$student = new Students();
			$student->email = $studentId;
			$student->{$skillId} = $rawScore;
			// Initialize all other skill scores to 0
			//$student->time = 0;
			//$student->activity = 0;
			//$student->consistency = 0;
			//$student->awareness = 0;
			//$student->deep_learning = 0;
			//$student->persistence_attempts = 0;
			//$student->persistence_watched = 0;
			
			if ($student->create() == false) {
				return false;
			} else {
				return true;
			}
		}
			
	}
	
	// Retrieves a scaled score for a skill for the given student
	function getScaledSkillScore($studentId, $skillId) {

	}

}
