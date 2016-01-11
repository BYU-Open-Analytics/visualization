<?php

use Phalcon\Mvc\Model;

class StudentMasteryHistory extends Model {
	public $unit1;
	public $unit2;
	public $unit3;
	public $unit4;
	public $recent_average;
	public $time_stored;
	public $email;

	function getSource() {
		return 'student_mastery_history';
	}
}
