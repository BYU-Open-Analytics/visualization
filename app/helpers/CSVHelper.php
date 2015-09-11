<?php 

use Phalcon\Mvc\User\Module;

class CSVHelper extends Module {

	// Returns an associative array from a csv file, using column headers as keys
	// From http://steindom.com/articles/shortest-php-code-convert-csv-associative-array
	public static function parseWithHeaders($filename) {
		$rows = array_map('str_getcsv', file($filename));
		$header = array_shift($rows);
		$csv = array();
		foreach ($rows as $row) {
			$csv[] = array_combine($header, $row);
		}
		return $csv;
	}
}
