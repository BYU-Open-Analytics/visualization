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

		// Aggregate, matching the verb, LRS, and object (specific question of a specific assessment), and get a count grouped by student email
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
		return rand(0,100) / 10;

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

	}
	
	// Retrieves a scaled score for a skill for the given student
	function getScaledSkillScore($studentId, $skillId) {

	}

}
