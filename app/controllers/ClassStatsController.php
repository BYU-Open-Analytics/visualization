<?php

use Phalcon\Mvc\Controller;
include __DIR__ . "/../library/array_functions.php";

class ClassStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Instructor Class Stats');
	}

	// For examples of getting data from mongo and postgres and different calculations, see ScatterplotRecommenderStatsController.php and StudentSkillsStatsController.php in this folder

}


