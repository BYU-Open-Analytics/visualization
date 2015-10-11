<?php 

use Phalcon\Mvc\User\Module;
include __DIR__ . "/../library/array_functions.php";

// Functions relating to the entire class of students
class ClassHelper extends Module {

	// Cache some results
	static $cachedStudents = null;

	// Returns the average number of attempts per student for a question, to 1 decimal place
	public function calculateAverageAttemptsForQuestion($assessmentId, $questionNumber, $debug = false) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";

		// Aggregate, matching the verb, LRS, and object (specific question of a specific assessment), and get a count grouped by student email
		$aggregation = [
			['$match' => [
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
				'lrs._id' => $config->lrs->openassessments->id,
				'statement.object.definition.name.en-US' => $questionDescription,
			] ],
			['$group' => ['_id' => '$statement.actor.mbox', 'count' => ['$sum' => 1] ] ]
		];

		$collection = $db->statements;
		// Get the results and average them
		$results = $collection->aggregate($aggregation)["result"];
		$resultsSum = array_sum(array_column($results, "count"));
		$resultsCount = count($results);
		// Avoid division by 0
		$average = $resultsCount > 0 ? $resultsSum / $resultsCount : 0;
		return round($average * 10) / 10;
	}

	// Returns a number 0-10 representing the percentile of the given number of attempts in the distribution of all students' number of attempts for the given question
	public function calculateScaledAttemptScoreForQuestion($attemptsToScale, $assessmentId, $questionNumber, $debug = false) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";

		// Aggregate, matching the verb, LRS, and object (specific question of a specific assessment), and get a count grouped by student email
		$aggregation = [
			['$match' => [
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
				'lrs._id' => $config->lrs->openassessments->id,
				'statement.object.definition.name.en-US' => $questionDescription,
			] ],
			['$group' => ['_id' => '$statement.actor.mbox', 'count' => ['$sum' => 1] ] ]
		];

		$collection = $db->statements;
		// Get the results and average them
		$results = $collection->aggregate($aggregation)["result"];
		if ($debug) {
			echo "Calculating scaled score for $attemptsToScale from the following class attempt counts: \n";
			$attemptCounts = array_column($results, "count");
			sort($attemptCounts);
			foreach ($attemptCounts as $r) {
				echo $r.",";
			}
			echo "\n";
			$maxValue = max($attemptCounts);
			for ($i=0; $i<=$maxValue; $i++) {
				$scaledScore = StatsHelper::calculateScaledScore($attemptCounts, $i);
				echo "Percent rank for $i attempts is: $scaledScore\n";
			}
		}
		$resultsSum = array_sum(array_column($results, "count"));
		$resultsCount = count($results);
		// Avoid division by 0
		$average = $resultsCount > 0 ? $resultsSum / $resultsCount : 0;
		return round($average * 10) / 10;
	}

	// Returns the percentage (0-100) of students that viewed the hint for a given question
	public function calculateViewedHintPercentageForQuestion($assessmentId, $questionNumber, $debug = false) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";

		// Aggregate, matching the verb, LRS, and object (specific question of a specific assessment), and get a count grouped by student email
		$aggregation = [
			['$match' => [
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/showed-hint',
				'lrs._id' => $config->lrs->openassessments->id,
				'statement.object.definition.name.en-US' => $questionDescription,
			] ],
			['$group' => ['_id' => '$statement.actor.mbox', 'count' => ['$sum' => 1] ] ]
		];

		$collection = $db->statements;
		// Get the results and average them
		$results = $collection->aggregate($aggregation)["result"];
		$studentCount = count($this->allStudents());
		$viewedHintCount = 0;
		foreach ($results as $result) {
			if ($result["count"] > 0) {
				$viewedHintCount++;
			}
		}
		// Avoid division by 0
		$percentage = $studentCount > 0 ? $viewedHintCount / $studentCount : 0;
		return round($percentage * 100);
	}

	// Returns the percentage (0-100) of students that viewed the answer for a given question
	public function calculateViewedAnswerPercentageForQuestion($assessmentId, $questionNumber, $debug = false) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";

		// Aggregate, matching the verb, LRS, and object (specific question of a specific assessment), and get a count grouped by student email
		$aggregation = [
			['$match' => [
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/showed-answer',
				'lrs._id' => $config->lrs->openassessments->id,
				'statement.object.definition.name.en-US' => $questionDescription,
			] ],
			['$group' => ['_id' => '$statement.actor.mbox', 'count' => ['$sum' => 1] ] ]
		];

		$collection = $db->statements;
		// Get the results and average them
		$results = $collection->aggregate($aggregation)["result"];
		$studentCount = count($this->allStudents());
		$viewedAnswerCount = 0;
		foreach ($results as $result) {
			if ($result["count"] > 0) {
				$viewedAnswerCount++;
			}
		}
		// Avoid division by 0
		$percentage = $studentCount > 0 ? $viewedAnswerCount / $studentCount : 0;
		return round($percentage * 100);
	}

	// Returns an array of all students' actor.mbox identifiers
	public function allStudents() {
		// Cache 
		if (self::$cachedStudents != null) {
			return self::$cachedStudents;
		}
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		$collection = $db->statements;
		// Get each distinct email address in the openassessments LRS
		$results = $collection->distinct('statement.actor.mbox', [
				'lrs._id' => $config->lrs->openassessments->id,
			]);
		self::$cachedStudents = $results;
		return $results;
	}

}
