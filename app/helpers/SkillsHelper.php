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

	// Percent of a student's total events between 11pm and 5am (raw percentage, that still needs to be scaled by class)
	public static function calculateTimeScore($student) {
		return rand(0,100) / 10;
	}

	public static function calculateActivityScore($student) {
		return rand(0,100) / 10;

	}

	public static function calculateConsistencyScore($student) {
		return rand(0,100) / 10;

	}

	public static function calculateAwarenessScore($student) {
		return rand(0,100) / 10;

	}

	public static function calculateDeepLearningScore($student) {
		return rand(0,100) / 10;

	}

	public static function calculatePersistenceScore($student) {
		return rand(0,100) / 10;

	}


}
