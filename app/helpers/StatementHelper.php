<?php 

use Phalcon\Mvc\User\Module;

class StatementHelper extends Module {

	// Build statements specific to the visualization app
	public function buildStatement($params, $ltiContext) {
		// $params will be an array if info needed for the statement type, sent from the frontend
		$verbAuthority = "http://adlnet.gov/expapi/verbs/";
		$objectAuthority = "http://byuopenanalytics.byu.edu/";


		if (!isset($params["statementName"])) {
			return false;
		}

		// Set up the componenents of our statement
		// Can't use use $context = [], because if we don't add anything and it gets encoded as empty, Learning Locker doesn't like that the empty context is empty array [] instead of empty object {}.
		$actor = new ArrayObject();
		$verb = new ArrayObject();
		$verbName = "";
		$object = new ArrayObject();
		$context = new ArrayObject();
		$result = new ArrayObject();

		switch ($params['statementName']) {
			case "dashboardLaunched":
				$verbName = "launched";
				$object = [
					"id"		=> $objectAuthority.$params["dashboardID"],
					"definition"	=> ["name" => ["en-US" => $params["dashboardName"]]]
				];
				break;
		}

		// The actor will be the same for all our statement types
		$actor["name"] = $ltiContext->getUserName();
		$actor["mbox"] = "mailto:" . $ltiContext->getUserEmail();
		$actor["objectType"] = "Agent";

		$verb["id"] = $verbAuthority . $verbName;
		$verb["display"] = ["en-US"=>ucfirst($verbName)];

		// Include timestamp in all statements
		$timestamp = isset($params["timestamp"]) ? $params["timestamp"] : date('c');

		$statement = [
			"actor"		=> $actor,
			"verb"		=> $verb,
			"object"	=> $object,
			"context"	=> $context,
			"result"	=> $result,
			"timestamp" 	=> $timestamp
		];

		return $statement;
	}

	// Sends statements to the given LRS. $lrs should be one of the detail arrays in config.php
	public function sendStatements($lrs, $statements) {
		// Get the LRS details
		$config = $this->getDI()->getShared('config');
		$lrsConfig = $config->lrs->{$lrs};

		$request = $lrsConfig->endpoint.'data/xAPI/statements';
		$session = curl_init($request);
		// TODO check for https
		curl_setopt($session, CURLOPT_USERPWD, $lrsConfig->username . ":" . $lrsConfig->password);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_POST, 1);
		curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($statements));
		curl_setopt($session, CURLOPT_HTTPHEADER, array('X-Experience-API-Version: 1.0.0', 'Content-Type: application/json'));

		$response = curl_exec($session);

		// Catch curl errors
		if (curl_errno($session)) {
			$error = "Curl error: " . curl_error($session);
		}

		curl_close($session);

		$parsed = json_decode($response);
		return $response;
	}
	
	// Retrieves statements from the given LRS (or all LRSs if no $lrs specified). $lrs should be one of the detail arrays in config.php. $query should be a mongo aggregate pipeline. $fields is an array of fields to return.
	public function getStatements($lrs, $query = array(), $fields = array()) {
		//echo "getting statements!";
		$error = "";
		$statements = Array();

		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->database->username}:{$config->database->password}@{$config->database->host}/{$config->database->dbname}");
		$db = $m->{$config->database->dbname};

		// Query statements
		$collection = $db->statements;
		// Use $lrs to support pulling statements from specific LRSs (ayamel, open assessments, visualization settings)
		if (!empty($lrs)) {
			// TODO create mongodb index for this
			$query["lrs._id"] = $config->lrs->{$lrs}->id;
		}

		//From PHP mongo docs: Please make sure that for all special query operators (starting with $) you use single quotes so that PHP doesn't try to replace "$exists" with the value of the variable $exists.
		$cursor = $collection->find($query, $fields);

		// TODO error handling
		//$error .= " Didn't receive any statements from Learning Locker with request of $request";
		return ["error"=>$error, "cursor"=>$cursor];
	}

	// Lifted directly from Learning Locker source: https://github.com/LearningLocker/learninglocker/blob/develop/app/locker/helpers/Helpers.php
	/*
	|----------------------------------------------------------------------------
	| scan array and replace &46; with . (This is a result of . being
	| reserved in Mongo) convert array to json as this is faster for
	| multi-dimensional arrays (?) @todo check this out.
	|----------------------------------------------------------------------------
	*/
	static function replaceHtmlEntity( $array, $toArray = false ){
		return json_decode(str_replace('&46;','.', json_encode($array)), $toArray);
	}

}
