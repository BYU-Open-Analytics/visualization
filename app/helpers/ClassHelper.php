<?php 

use Phalcon\Mvc\User\Module;
include __DIR__ . "/../library/array_functions.php";

// Functions relating to the entire class of students
class ClassHelper extends Module {

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

	// Returns the percentage of students that viewed the hint for a given question
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
		$resultsCount = count($results);
		$viewedHintCount = 0;
		foreach ($results as $result) {
			if ($result["count"] > 0) {
				$viewedHintCount++;
			}
		}
		// Avoid division by 0
		$percentage = $resultsCount > 0 ? $viewedHintCount / $resultsCount : 0;
		return round($percentage * 100) / 100;
	}

	// Returns the percentage of students that viewed the answer for a given question
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
		$resultsCount = count($results);
		$viewedHintCount = 0;
		foreach ($results as $result) {
			if ($result["count"] > 0) {
				$viewedHintCount++;
			}
		}
		// Avoid division by 0
		$percentage = $resultsCount > 0 ? $viewedHintCount / $resultsCount : 0;
		return round($percentage * 100) / 100;
	}

	// Returns an array of all students' actor.mbox identifiers
	public static function allStudents() {
		
	}

	// Returns the number of attempts (answered statements) a student has made for a particular question
	public static function countAttemptsForQuestion($studentId, $assessmentId, $questionNumber, $debug ) {
		$statementHelper = new StatementHelper();

		// Regex for assessment id and question number (since url for object IDs will change, but end part will be the same format)
		$regex = new MongoRegex('/' . $assessmentId . '\.xml#' . $questionNumber . '$/');
		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";
		// Get the count of answered statements for this question for current user
		// TODO take the verb authority (adlnet/expapi/verbs/) part and put into a global constant
		$statements = $statementHelper->getStatements("openassessments",[
			'statement.actor.mbox' => 'mailto:'.$studentId,
			'statement.verb.id' => 'http://adlnet.gov/expapi/verbs/answered',
			//'statement.object.id' => $regex,
			'statement.object.definition.name.en-US' => $questionDescription,
		], [
			'statement.result.success' => true,
		]);
		if ($statements["error"]) {
			// TODO error handling
		} else {
			// My attempt at debugging slow queries to figure out how to speed them out
			//var_dump($statements["cursor"]->explain());
			return $statements["cursor"]->count();
		}
	}

	// Returns the number of correct, and better-correct attempts (Better-Correct = if there's a correct statement with no show answer statement in the minute before) a student has for a particular question
	// Returns array, e.g. {"correct" => 5, "betterCorrect" => 2}
	public static function countCorrectAttemptsForQuestion($studentId, $assessmentId, $questionNumber, $debug = false) {
		$statementHelper = new StatementHelper();

		// Regex for assessment id and question number (since url for object IDs will change, but end part will be the same format)
		$regex = new MongoRegex('/' . $assessmentId . '\.xml#' . $questionNumber . '$/');
		$questionDescription = "Question #{$questionNumber} of assessment {$assessmentId}";
		// Get the count of answered and showed-answer statements for this question for current user
		// TODO take the verb authority (adlnet/expapi/verbs/) part and put into a global constant
		$statements = $statementHelper->getStatements("openassessments",[
			'statement.actor.mbox' => 'mailto:'.$studentId,
			'statement.verb.id' => array('$in' => array(
				'http://adlnet.gov/expapi/verbs/answered',
				'http://adlnet.gov/expapi/verbs/showed-answer'
			)),
			//'statement.object.id' => $regex,
			'statement.object.definition.name.en-US' => $questionDescription,
		], [
			'statement.verb.id' => true,
			'statement.result.success' => true,
			'statement.timestamp' => true,
		]);
		if ($statements["error"]) {
			// TODO error handling
		} else {
			$correctAttempts = 0;
			$betterCorrectAttempts = 0;
			// Find the correct answered statements
			foreach ($statements["cursor"] as $statement) {
				if ($statement["statement"]["verb"]["id"] == 'http://adlnet.gov/expapi/verbs/answered' && $statement["statement"]["result"]["success"] == true) {
					if ($debug) echo "Correct answered statement!<br>";
					// Have a correct attempt
					$correctAttempts++;
					// Now see if there's a shown-answer statement in the preceding minute to check if it's a better-correct
					// Set this to false if we find a shown-answer statement in the preceding minute
					$attemptBetterCorrect = true;
					foreach ($statements["cursor"] as $possibleShowAnswerStatement) {
						if ($possibleShowAnswerStatement["statement"]["verb"]["id"] == 'http://adlnet.gov/expapi/verbs/showed-answer') {
							// Compare time 
							$timeDifference = strtotime($statement["statement"]["timestamp"]) - strtotime($possibleShowAnswerStatement["statement"]["timestamp"]);
							if ($debug) echo "Time diff (seconds): $timeDifference<br>";
							// TODO move this magic number 60
							if ($timeDifference > 0 && $timeDifference < 60) {
								$attemptBetterCorrect = false;
								break;
							}
						}
					}
					if ($attemptBetterCorrect) {
						$betterCorrectAttempts++;
					}

				}
				if ($debug) {
					echo strtotime($statement["statement"]["timestamp"]);
					echo "<pre>"; print_r($statement);
					echo "<hr>";
				}
			}
			if ($debug) echo "correct attempts: $correctAttempts, better correct attempts: $betterCorrectAttempts";
			return ["correct" => $correctAttempts, "betterCorrect" => $betterCorrectAttempts];
		}
	}

	// Calculates the percentage of video time watched for all videos associated with a given questionID (we're using ID here, since that's what we need to search for in the mappings, and we're not dealing with statements here)
	public static function calculateVideoPercentageForQuestion($studentId, $questionId, $debug = false) {
		// Find the videos related to this question
		$relatedVideos = MappingHelper::videosForQuestion($questionId);
		$totalVideoTime = 0;
		$totalVideoTimeWatched = 0;
		foreach ($relatedVideos as $video) {
			// Get each video's length
			$videoLength = $video["video_length"];
			$totalVideoTime += $videoLength;
			$videoId = $video["video_id"];

			// Calculate how much time of this video was watched
			$statementHelper = new StatementHelper();
			$statements = $statementHelper->getStatements("ayamel",[
				'statement.actor.mbox' => 'mailto:'.$studentId,
				'statement.verb.id' => 'https://ayamel.byu.edu/watched',
				'statement.object.id' => 'https://ayamel.byu.edu/content/'.$videoId,
			], [
				'statement.object.id' => true,
			]);
			if ($statements["error"]) {
				$watchStatementCount = 0;
				if ($debug) {
					echo "Error in fetching watched statements for question $questionId and video $videoId";
				}
			} else {
				$watchStatementCount = $statements["cursor"]->count();
			}
			// TODO magic number of 10
			$totalVideoTimeWatched += $watchStatementCount * 10;

			if ($debug) { echo "Video for $questionId : ID $videoId with $watchStatementCount watched statements\n"; }
		}
		// Return percentage of videos watched, avoiding division by 0
		return ($totalVideoTime != 0) ? ($totalVideoTimeWatched / $totalVideoTime) : 0;
	}
}
