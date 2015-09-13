<?php

use Phalcon\Mvc\Controller;

class CalculationCacherController extends Controller
{
	public function initialize() {
		$this->view->disable();
	}
	public function indexAction() {
		die("Please provide an action, e.g. http://vis.site/calculation_cacher/daily");
	}

	public function dailyAction() {
		// We want to time this
		$startTime = microtime(true);

		$config = $this->getDI()->getShared('config');
		// Connect to database
		$m = new MongoClient("mongodb://{$config->lrs_database->username}:{$config->lrs_database->password}@{$config->lrs_database->host}/{$config->lrs_database->dbname}");
		$db = $m->{$config->lrs_database->dbname};

		// Query statements
		$collection = $db->statements;

		// Get list of unique users, only from the open assessments LRS
		$userMboxes = $collection->distinct("statement.actor.mbox", ["lrs._id" => $config->lrs->openassessments->id]);


		foreach ($userMboxes as $user) {
			$inefficientCount = 0;
			// Perform some example query
			$cursor = $collection->find(["statement.actor.mbox" => $user], []);
			foreach ($cursor as $statement) {
				$inefficientCount++;
			}
			echo " : " .$inefficientCount."<br>\n";
		}


		// Print total time taken
		$endTime = microtime(true);
		echo "Execution time: " . ($endTime - $startTime) . " seconds";
	}
}
