<?php

use Phalcon\Mvc\Model;

class MasteryHistory extends Model {
	public $unit3;
	public $unit4;
	public $time_stored;
	public $email;

	function getSource() {
		return 'mastery_history';
	}
}
