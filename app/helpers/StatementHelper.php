<?php 

use Phalcon\Mvc\User\Module;

class StatementHelper extends Module {
	
	public function getStatements($lrs, $query = array(), $fields = array()) {
		//echo "getting statements!";
		$error = "";
		$statements = Array();

		// Pull the user's statements from the LRS (identification info from the LTI context)
		$config = $this->getDI()->getShared('config');

		// Connect to database
		$m = new MongoClient("mongodb://{$config->database->username}:{$config->database->password}@{$config->database->host}/{$config->database->dbname}");
		$db = $m->{$config->database->dbname};

		// Query statements
		$collection = $db->statements;
		//From PHP mongo docs: Please make sure that for all special query operators (starting with $) you use single quotes so that PHP doesn't try to replace "$exists" with the value of the variable $exists.
		if (!empty($lrs)) {
			$query["lrs._id"] = $config->lrs->{$lrs}->id;
		}

		$cursor = $collection->find($query, $fields);


		// Can't just include entire statement ("statement":1 in $project block), or learning locker php script will run out of memory

		// Use $lrs to support pulling statements from different LRSs (ayamel, open assessments, visualization settings)
		$request = $config->lrs->{$lrs}->endpoint.'api/v1/statements/aggregate?pipeline='.$pipeline;
		$session = curl_init($request);
		curl_setopt($session, CURLOPT_USERPWD, $config->lrs->{$lrs}->username . ":" . $config->lrs->{$lrs}->password);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($session);

		// Catch curl errors
		if (curl_errno($session)) {
			$error = "Curl error: " . curl_error($session);
		}
		$statusCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
		$error .= $statusCode;

		curl_close($session);


		$parsed = json_decode($response);
		if (isset($parsed->ok) && $parsed->ok == 1) {
			if (isset($parsed->result) && $statements = $parsed->result) {
				$error = null;
			} else {
				$error .= " Didn't receive any statements from Learning Locker with request of $request";
			}
		} elseif (isset($parsed->error) && $parsed->error == true) {
			$error .= " LL error: {$parsed->message}";
		} else {
			$error .= " Unknown error in Learning Locker response";
		}
		return ["error"=>$error, "statements"=>$statements];
	}

}
