<?php

use Phalcon\Mvc\Controller;

class DashboardController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Visualization Dashboard');
		$this->view->pageTitle = 'Visualization Dashboard';

		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
		// Fetch user settings
		$currentSettings = UserSettings::query()
				->where("userId = :userId:")
				->bind(["userId" => $context->getUserKey()])
				->execute();
		$this->view->currentSettings = $currentSettings;
	}
	public function indexAction() {
		$context = $this->getDI()->getShared('ltiContext');
		// TODO this is where need to redirect users to appropriate dashboard based on their current group, or provide options

		$setting = new UserSettings();
		$setting->userId = $context->getUserKey();
		$setting->name = "test";
		$setting->value = "randomvalue";
		$setting->save();
	}
	public function content_recommenderAction() {
		$this->tag->setTitle('Content Recommender Dashboard');
		$this->view->pageTitle = 'Content Recommender Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;

		// Concept, Chapter, and Unit lists
		// TODO way to have html list default to current
		$conceptsMapping = CSVHelper::parseWithHeaders('csv/concept_chapter.csv');
		$concepts = [];
		// Make each hierarchical content category have consistent structure for view
		foreach ($conceptsMapping as $c) {
			$concepts [] = ["id" => $c["concept_number"], "title" => $c["concept_number"] . " " . $c["concept_title"]];
		}
		$this->view->concepts = $concepts;

		$units = [];
		for ($i=1; $i<=4; $i++) {
			$units []= ["id" => $i, "title" => "Unit $i"];
		}
		$this->view->units = $units;
	}
	public function student_skillsAction() {
		$this->tag->setTitle('Student Skills Dashboard');
		$this->view->pageTitle ='Student Skills Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
	}
}
