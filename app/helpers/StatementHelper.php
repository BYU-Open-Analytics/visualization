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

}
