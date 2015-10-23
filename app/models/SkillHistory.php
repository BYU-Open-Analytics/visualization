<?php

use Phalcon\Mvc\Model;

class SkillHistory extends Model {
	public $time;
	public $activity;
	public $consistency;
	public $awareness;
	public $deep_learning;
	public $persistence;
	public $time_stored;
	public $email;

	function getSource() {
		return 'skill_history';
	}
}
