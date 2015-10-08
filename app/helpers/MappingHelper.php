<?php 

use Phalcon\Mvc\User\Module;

class MappingHelper extends Module {

	// Returns an array of all chapter numbers
	static public function allChapters() {
		// Get unit -> chapter mapping
		$units = CSVHelper::parseWithHeaders('csv/unit_chapter.csv');
		$chapters = [];
		foreach ($units as $unit) {
			$chapters = array_merge($chapters, explode(",", $unit["chapters"]));
		}
		return $chapters;
	}

	// Returns an array of chapter numbers for a given unit
	static public function chaptersInUnit($unitNumber) {
		// Get unit -> chapter mapping
		$units = CSVHelper::parseWithHeaders('csv/unit_chapter.csv');
		$unit = $units[array_search($unitNumber, array_column($units, 'unit_number'))];
		// Get chapters that are in this unit
		$correspondingChapters = explode(",", $unit["chapters"]);
		return $correspondingChapters;

	}

	// Returns an array of concepts for the given array of chapter numbers
	static public function conceptsInChapters($chapters) {
		// TODO
	}

	// Returns an array of questions (format is {quiz number}.{question number}) for the given chapter
}
