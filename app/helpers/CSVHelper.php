<?php 

use Phalcon\Mvc\User\Module;

class CSVHelper extends Module {

	// Cache any used mappings in memory, to reduce filesystem usage
	static $cachedMappings = array();

	// Returns an associative array from a csv file, using column headers as keys
	// From http://steindom.com/articles/shortest-php-code-convert-csv-associative-array
	public static function parseWithHeaders($filename) {
		// Check if we've cached this one before
		if (isset(self::$cachedMappings[$filename])) {
			return self::$cachedMappings[$filename];
		}
		$rows = array_map('str_getcsv', file($filename));
		$header = array_shift($rows);
		$csv = array();
		foreach ($rows as $row) {
			$csv[] = array_combine($header, $row);
		}
		self::$cachedMappings[$filename] = $csv;
		return $csv;
	}
}
