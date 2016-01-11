<?php

use Phalcon\Mvc\Model;

class ClassConceptHistoty extends Model {
	public $time_stored;
	public $concept_id;
	public $average_mastery;

	function getSource() {
		return 'class_concept_history';
	}
}
