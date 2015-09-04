<?php 

use Phalcon\Mvc\User\Module;

class SkillsHelper extends Module {

	// Return an array of the five skill scores for a given student
	public function calculateStudentSkillScores($student) {
		return [
			"time" => calculateTimeScore($student),
			"activity" => calculateActivityScore($student),
			"regulation" => calculateRegulationScore($student),
			"efficacy" => calculateEfficacyScore($student),
			"consistency" => calculateConsistencyScore($student),
		];
	}

	// Perform (or retrieve) calculations for a given skill for a given student

	public static function calculateTimeScore($student) {
		return rand(0,100) / 10;
	}

	public static function calculateActivityScore($student) {
		return rand(0,100) / 10;

	}

	public static function calculateRegulationScore($student) {
		return rand(0,100) / 10;

	}

	public static function calculateEfficacyScore($student) {
		return rand(0,100) / 10;

	}

	public static function calculateConsistencyScore($student) {
		return rand(0,100) / 10;

	}

}
