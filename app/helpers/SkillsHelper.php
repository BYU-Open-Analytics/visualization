<?php 

use Phalcon\Mvc\User\Module;
include __DIR__ . "/../library/array_functions.php";

class SkillsHelper extends Module {

	// Perform (or retrieve) calculations for a given skill for a given student. Return a score scaled by class, 0-10.
	// NOTE that studentId should be name, not email address.

	// Percent of a student's total events between 11pm and 5am (raw percentage, that still needs to be scaled by class)
	public function calculateTimeScore($studentId, $raw = false, $debug = false) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		// Aggregate, fetching the student's statements in the past two weeks for open assessments and ayamel
		// http://www.saturngod.net/articles/group-by-date-in-mongodb/
		$aggregation = [
			['$match' => [
				'timestamp' => array('$gte' => new MongoDate(strtotime('midnight -2 weeks'))),
				'statement.actor.name' => $studentId,
				'lrs._id' => array('$in' => array( 
					$config->lrs->openassessments->id, 
					$config->lrs->ayamel->id,
				)),
			]],
			['$project' => [
				'h' => ['$hour' => '$timestamp'],
			]],
			['$group' => [
				'_id' => [
					'hour' => '$h',
				],
				'count' => [
					'$sum' => 1
				]
			]],
			['$sort' => [
				'_id.hour' => 1,
			]]
		];

		$collection = $db->statements;
		$results = $collection->aggregate($aggregation)["result"];

		// Now get into Utah time
		// TODO Make sure this takes daylight saving time into account
		$localTimeZone = new DateTimeZone("America/Denver");
		$secondOffset = $localTimeZone->getOffset(new DateTime("now", new DateTimeZone("UTC")));
		$hourOffset = $secondOffset / 3600;

		// These are Utah time shifted into UTC
		// Make sure we don't go above 23 or below 0 hours (on October 17, these were 17:00 and 23:00 for 11pm and 5am)
		$UTC11pm = 23 + $hourOffset;
		$UTC11pm = $UTC11pm > 23 ? $UTC11pm - 24 : $UTC11pm;
		$UTC5am = 5 + $hourOffset;
		$UTC5am = $UTC5am < 0 ? $UTC5am + 24 : $UTC5am;

		// Get total number of statements 
		$totalStatementCount = array_sum(array_column($results, "count"));
		
		// Get number of statements between Utah's 11pm and 5am in UTC
		$procrastinatedStatementCount = 0;
		foreach ($results as $r) {
			// TODO if the time period we're calculating changes, it might go across a midnight line, in which case this if wouldn't work right.
			if ($r["_id"]["hour"] >= $UTC11pm && $r["_id"]["hour"] <= $UTC5am) {
				$procrastinatedStatementCount += $r["count"];
			}
		}

		if ($debug) {
			echo "<pre>";
			echo "11pm and 5am in UTC: $UTC11pm, $UTC5am\n";
			print_r($results);
			echo "Total statement count: $totalStatementCount\n";
			echo "Total procrastinated statement count: $procrastinatedStatementCount\n";
		}
		// store raw, and then return scaled
		// Do 1 - percentage so that fewer statements in 11pm-5am gives a higher score
		//
		// Avoid division by 0
		$rawScore = ($totalStatementCount != 0) ? (1 - ( $procrastinatedStatementCount / $totalStatementCount) ) : 0;
		$this->saveRawSkillScore($studentId, "time", $rawScore);
		if ($raw) { return $rawScore; }
		return $this->getScaledSkillScore($studentId, "time");
	}

	public function calculateActivityScore($studentId, $raw = false, $debug = false) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		// Aggregate, fetching the student's statements in the past two weeks for open assessments and ayamel
		$aggregation = [
			['$match' => [
				'timestamp' => array('$gte' => new MongoDate(strtotime('-2 weeks'))),
				'statement.actor.name' => $studentId,
				'lrs._id' => array('$in' => array( 
					$config->lrs->openassessments->id, 
					$config->lrs->ayamel->id,
				)),
			] ],
			['$group' => ['_id' => '$statement.actor.name', 'count' => ['$sum' => 1] ] ]
		];

		$collection = $db->statements;
		$results = $collection->aggregate($aggregation)["result"];
		// store raw, and then return scaled
		$rawScore = $results[0]["count"];
		$this->saveRawSkillScore($studentId, "activity", $rawScore);
		if ($raw) { return $rawScore; }
		return $this->getScaledSkillScore($studentId, "activity");
	}

	public function calculateConsistencyScore($studentId, $raw = false, $debug = false) {
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		// Aggregate, fetching the student's statements in the past two weeks for open assessments and ayamel
		// http://www.saturngod.net/articles/group-by-date-in-mongodb/
		$aggregation = [
			['$match' => [
				'timestamp' => array('$gte' => new MongoDate(strtotime('midnight -2 weeks'))),
				'statement.actor.name' => $studentId,
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
		// store raw, and then return scaled
		$rawScore = count($results);
		$this->saveRawSkillScore($studentId, "consistency", $rawScore);
		if ($raw) { return $rawScore; }
		return $this->getScaledSkillScore($studentId, "consistency");

	}

	public function calculateAwarenessScore($studentId, $raw = false, $debug = false) {
		// Get a score from the table for each question attempt, add those up, divide by number of attempts, and scale this raw score by class.
		//			  Low Medium High
		//	  Correct 0   1      1
		//	Incorrect 1   0      0

		$scoreTable = [
			"correct" => ["low" => 0, "medium" => 1, "high" => 1],
			"incorrect" => ["low" => 1, "medium" => 0, "high" => 0],
		];

		// Query is simple enough for this one (no aggregation) that we can use StatementHelper
		$statementHelper = new StatementHelper();

		// Get the count of answered statements for this question for current user
		// TODO take the verb authority (adlnet/expapi/verbs/) part and put into a global constant
		$statements = $statementHelper->getStatements("openassessments",[
			'statement.actor.name' => $studentId,
			'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
			// Timeframe of past two weeks
			'timestamp' => array('$gte' => new MongoDate(strtotime('midnight -2 weeks'))),
		], [
			'_id' => false,
			'statement.context.extensions' => true,
			'statement.result.success' => true,
		]);
		if ($statements["error"]) {
			// TODO error handling
			return 0;
		} else {
			// Calculate score for each question
			$questionAwarenessTotal = 0;
			$nonEssayAttemptCount = 0;
			foreach ($statements["cursor"] as $statement) {
				$statement = StatementHelper::replaceHtmlEntity($statement, true);
				if ($debug) { print_r($statement); }
				if (isset($statement['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level'])) {
					// Exclude essay questions (we didn't do this in the query, because the key for the extension contains periods, which are problematic with mongo)
					if ($statement['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/question_type'] != "essay") {
						$level = $statement['statement']['context']['extensions']['http://byuopenanalytics.byu.edu/expapi/extensions/confidence_level'];
						$questionScore = $statement['statement']['result']['success'] == true ? $scoreTable["correct"][$level] : $scoreTable["incorrect"][$level];
						$questionAwarenessTotal += $questionScore;
						$nonEssayAttemptCount++;
						if ($debug) { echo "Question score: $questionScore<hr>"; }
					}
				}
			}
			if ($debug) { echo "Score for $nonEssayAttemptCount non-essay attempts: $questionAwarenessTotal"; }

			// store raw, and then return scaled
			// Avoid division by 0
			$rawScore = ($nonEssayAttemptCount != 0) ? ( $questionAwarenessTotal / $nonEssayAttemptCount ) : 0;
			$this->saveRawSkillScore($studentId, "awareness", $rawScore);
			if ($raw) { return $rawScore; }
			return $this->getScaledSkillScore($studentId, "awareness");
		}
	}

	public function calculateDeepLearningScore($studentId, $raw = false, $debug = false) {
		// TODO implement
		// ( # of (questions that answer was viewed on) and (only 2 attempts per question) ) / (total question count)

		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		// Aggregate, fetching the student's attempts, grouped by questions (to give us number of questions they attempted)
		$questionCountAggregation = [
			['$match' => [
				'timestamp' => array('$gte' => new MongoDate(strtotime('-2 weeks'))),
				'statement.actor.name' => $studentId,
				'lrs._id' => $config->lrs->openassessments->id, 
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
			] ],
			['$group' => ['_id' => '$statement.object.id', 'count' => ['$sum' => 1] ] ]
		];

		$collection = $db->statements;
		$questionCountResults = $collection->aggregate($questionCountAggregation)["result"];

		// Total number of questions attempted
		$totalQuestionCount = count($questionCountResults);
 
		// Now get the number of questions that the answer was viewed on and they had 2 attempts on (gamed questions)
		$gamedQuestionCount = 0;
		// First get the questions that they viewed the answer on
		$viewedAnswerAggregation = [
			['$match' => [
				'timestamp' => array('$gte' => new MongoDate(strtotime('-2 weeks'))),
				'statement.actor.name' => $studentId,
				'lrs._id' => $config->lrs->openassessments->id, 
				'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/showed-answer'
			] ],
			['$group' => ['_id' => '$statement.object.id', 'count' => ['$sum' => 1] ] ]
		];
		$viewedAnswerResults = $collection->aggregate($viewedAnswerAggregation)["result"];
		// Now go through each of those and see if they had only 2 attempts on that question
		foreach ($viewedAnswerResults as $viewedAnswerQuestion) {
			$id = $viewedAnswerQuestion["_id"];
			foreach ($questionCountResults as $q) {
				// See if it's the same as this question, and if there's two attempts
				if ($q["count"] == 2 && $q["_id"] == $id) {
					// TODO put essay question filtering back in later.
					// Now get the question type (since we don't care about essay)
					//preg_match('/assessments\/(.*)\.xml#(.*)$/', $id, $matches);
					//$assessmentId = $matches[0];
					//$questionNumber = $matches[1];
					// Load question information mapping
					//$questionInfo = CSVHelper::parseWithHeaders('csv/questions.csv');
					//$questionRow = $questionInfo[multi_array_search($questionInfo, ["OA Quiz ID" => $assessmentId, "Question Number" => $questionNumber])[0]];
					//$questionType = $questionRow["Type"];
					//if ($questionType != "essay") {
						// Then we've got a non-essay question with two attempts and a shown answer. Gamed question.
						$gamedQuestionCount++;
						if ($debug) { echo "gamed question:"; print_r($viewedAnswerQuestion); }
					//}
				}
			}
		}

		if ($debug) {
			echo "<pre>Total questions attempted count: $totalQuestionCount\n";
			print_r($questionCountResults);
			echo "<hr>questions viewed answer on:\n";
			print_r($viewedAnswerResults);
			echo "<hr>Gamed answer question count: $gamedQuestionCount\n";
		}

		// store raw, and then return scaled
		// Avoid division by 0
		$rawScore = ($totalQuestionCount != 0) ? ( $gamedQuestionCount / $totalQuestionCount ) : 0;
		// Do 1 - percentage so that more gamed questions gives a lower score
		$rawScore = 1 - $rawScore;
		$this->saveRawSkillScore($studentId, "deep_learning", $rawScore);
		if ($raw) { return $rawScore; }
		return $this->getScaledSkillScore($studentId, "deep_learning");
	}

	public function calculatePersistenceScore($studentId, $raw = false, $debug = false) {
		// Calculate and store both parts of this skill score

		$statementHelper = new StatementHelper();

		// Calculate number of watched statements for student
		$statements = $statementHelper->getStatements("ayamel",[
			'statement.actor.name' => $studentId,
			'statement.verb.id' => 'https://ayamel.byu.edu/watched',
			// Timeframe of past two weeks
			'timestamp' => array('$gte' => new MongoDate(strtotime('midnight -2 weeks'))),
		], [
			'statement.object.id' => true,
		]);
		if ($statements["error"]) {
			$watchStatementCount = 0;
		} else {
			$watchStatementCount = $statements["cursor"]->count();
		}

		// Calculate number of question attempt (answered) statements for student
		$statements = $statementHelper->getStatements("openassessments",[
			'statement.actor.name' => $studentId,
			'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
			// Timeframe of past two weeks
			'timestamp' => array('$gte' => new MongoDate(strtotime('midnight -2 weeks'))),
		], [
			'statement.object.id' => true,
		]);
		if ($statements["error"]) {
			$attemptCount = 0;
		} else {
			$attemptCount = $statements["cursor"]->count();
		}

		// store raw, and then return scaled
		$rawAttemptsScore = $attemptCount;
		$this->saveRawSkillScore($studentId, "persistence_attempts", $rawAttemptsScore);
		$rawWatchedScore = $watchStatementCount;
		$this->saveRawSkillScore($studentId, "persistence_watched", $rawWatchedScore);

		if ($raw) { return "$rawAttemptsScore , $rawWatchedScore"; }
		return $this->getScaledSkillScore($studentId, "persistence");
	}


	// Stores the given raw score for a specific skill for the given student in the PostgreSQL students table
	function saveRawSkillScore($studentId, $skillId, $rawScore) {
		// See if this student is already in the database
		if ($existingStudent = Students::findFirst("email = '$studentId'")) {
			// If they do, update the existing database row
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
	
	// Retrieves a scaled score 0-10 for a skill for the given student
	function getScaledSkillScore($studentId, $skillId, $debug = false) {
		// Special case for persistence, which has two parts that need to be scaled independently
		if ($skillId == "persistence") {
			// Scale the two parts indpendently, and weight them equally
			return ($this->getScaledSkillScore($studentId, "persistence_attempts", $debug) + $this->getScaledSkillScore($studentId, "persistence_watched", $debug)) / 2;
		}

		$scoreResults = Students::find([
			"columns" => "$skillId"
		]);
		$rawScores = array_column($scoreResults->toArray(), "$skillId");
		$rawScore = Students::findFirst("email = '$studentId'")->{$skillId};
		$scaledScore = StatsHelper::calculateScaledScore($rawScores, $rawScore);
		
		if ($debug) {
			echo "All scores for skill $skillId: \n";
			foreach ($rawScores as $s) {
				echo "$s\n";
			}
			echo "Raw $skillId score for student $studentId is $rawScore, and scaled score is $scaledScore\n";
		}
		return round($scaledScore * 100) / 10;
	}

}
