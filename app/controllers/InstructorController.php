<?php

use Phalcon\Mvc\Controller;

class InstructorController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Visualization Dashboard');
		$this->view->pageTitle = 'Visualization Dashboard';

		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
		// Fetch user settings
		//$currentSettings = UserSettings::query()
				//->where("userId = :userId:")
				//->bind(["userId" => $context->getUserKey()])
				//->execute();
		//$this->view->currentSettings = $currentSettings;
	}
	public function indexAction() {
		$context = $this->getDI()->getShared('ltiContext');
		// TODO this is where need to redirect users to appropriate dashboard based on their current group, or provide options

		//$setting = new UserSettings();
		//$setting->userId = $context->getUserKey();
		//$setting->name = "test";
		//$setting->value = "randomvalue";
		//$setting->save();
	}
	public function content_recommenderAction() {
		$this->tag->setTitle('Content Recommender Dashboard');
		$this->view->pageTitle = 'Content Recommender Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;

		$this->view->feedbackEmail = $this->getDI()->getShared('config')->feedback_email;

		// Concept, Chapter, and Unit lists
		// TODO way to have html list default to current
		// Concepts
		$conceptsMapping = MappingHelper::conceptsInChapters(MappingHelper::allChapters());
		$concepts = [];
		// Make each hierarchical content category have consistent structure for view
		foreach ($conceptsMapping as $c) {
			$concepts [] = ["id" => $c["Section Number"], "title" => $c["Section Number"] . " " . $c["Section Title"]];
		}
		$this->view->concepts = $concepts;

		// Chapters
		$chaptersMapping = CSVHelper::parseWithHeaders('csv/chapter_unit.csv');
		$chapters = [];
		foreach ($chaptersMapping as $c) {
			$chapters [] = ["id" => $c["Chapter Number"], "title" => $c["Chapter Number"] . " " . $c["Chapter Title Short"]];
		}
		$this->view->chapters = $chapters;

		// Units
		$unitsMapping = CSVHelper::parseWithHeaders('csv/unit_chapter.csv');
		$units = [];
		foreach ($unitsMapping as $u) {
			// Only show units 3 and 4 in selector
			if ($u["unit_number"] == "3" || $u["unit_number"] == "4") {
				$units [] = ["id" => $u["unit_number"], "title" => $u["unit_title"]];
			}
		}
		$this->view->units = $units;
	}
	public function scatterplot_recommenderAction() {
		$this->tag->setTitle('Content Recommender Dashboard');
		$this->view->pageTitle ='Content Recommender Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
		$this->view->feedbackEmail = $this->getDI()->getShared('config')->feedback_email;
		// Units
		$unitsMapping = CSVHelper::parseWithHeaders('csv/unit_chapter.csv');
		$units = [];
		foreach ($unitsMapping as $u) {
			// Only show units 3 and 4 in selector
			if ($u["unit_number"] == "3" || $u["unit_number"] == "4") {
				$units [] = ["id" => $u["unit_number"], "title" => $u["unit_title"]];
			}
		}
		$this->view->units = $units;
	}
	public function student_skillsAction() {
		$this->tag->setTitle('Student Skills Dashboard');
		$this->view->pageTitle ='Student Skills Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
		$this->view->feedbackEmail = $this->getDI()->getShared('config')->feedback_email;
	}
}
