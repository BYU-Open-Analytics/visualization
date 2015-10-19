<?php

use Phalcon\Mvc\Model;

class Feedback extends Model {
	public $type;
	public $feedback;
	public $student_email;
	public $student_name;
}
