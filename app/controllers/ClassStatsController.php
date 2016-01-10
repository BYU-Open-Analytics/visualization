<?php

use Phalcon\Mvc\Controller;

// Stats and calculations loaded via ajax and used in the class dashboard for the instructor
//
class ClassStatsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Instructor Class Stats');
	}

	// For examples of getting data from mongo and postgres and different calculations, see ScatterplotRecommenderStatsController.php and StudentSkillsStatsController.php in this folder

}


