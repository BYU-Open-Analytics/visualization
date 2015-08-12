<?php 

use Phalcon\Mvc\User\Module;

class StatementHelper extends Module {
	
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
