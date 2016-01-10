<?php

use Phalcon\Mvc\Controller;

// The actions here mainly just set up the LTI context and pass it to the view (some of the views need a list of concepts/units, which is also done here). The view is largely boilerplate, and then actual data and calculations (coming from ScatterplotRecommenderStatsController or StudentSkillsStatsController) are loaded by the javascript via ajax.

class DashboardController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Visualization Dashboard');
		$this->view->pageTitle = 'Visualization Dashboard';

		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
	}

	public function indexAction() {
		$context = $this->getDI()->getShared('ltiContext');
		// TODO this is where need to redirect users to appropriate dashboard based on their current group, or provide options
	}

	public function content_recommenderAction() {
		$this->tag->setTitle('Test Help | Student Dashboard');
		$this->view->pageTitle = 'Test Help | Student Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;

		$this->view->feedbackEmail = $this->getDI()->getShared('config')->feedback_email;

		// Concept and Unit lists
		// TODO way to have html list default to current
		// Concepts
		$conceptsMapping = MappingHelper::allConcepts();
		$concepts = [];
		// Make each hierarchical content category have consistent structure for view
		foreach ($conceptsMapping as $c) {
			$concepts [] = ["id" => $c["Lecture Number"], "title" => $c["Concept Title"]];
		}
		$this->view->concepts = $concepts;

		// Units
		$unitsMapping = MappingHelper::allUnits();
		$units = [];
		foreach ($unitsMapping as $u) {
			$units [] = ["id" => $u["Unit Number"], "title" => $u["Unit Title"]];
		}
		$this->view->units = $units;
	}

	public function scatterplot_recommenderAction() {
		$this->tag->setTitle('Test Help | Student Dashboard');
		$this->view->pageTitle ='Test Help | Student Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
		$this->view->feedbackEmail = $this->getDI()->getShared('config')->feedback_email;

		// Concept and Unit lists
		// TODO way to have html list default to current
		// Concepts
		$conceptsMapping = MappingHelper::allConcepts();
		$concepts = [];
		// Make each hierarchical content category have consistent structure for view
		foreach ($conceptsMapping as $c) {
			$concepts [] = ["id" => $c["Lecture Number"], "title" => $c["Concept Title"]];
		}
		$this->view->concepts = $concepts;

		// Units
		$unitsMapping = MappingHelper::allUnits();
		$units = [];
		foreach ($unitsMapping as $u) {
			$units [] = ["id" => $u["Unit Number"], "title" => $u["Unit Title"]];
		}
		$this->view->units = $units;
	}

	public function student_skillsAction() {
		$this->tag->setTitle('Improve my Learning | Student Dashboard');
		$this->view->pageTitle ='Improve my Learning Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
		$this->view->feedbackEmail = $this->getDI()->getShared('config')->feedback_email;
	}

	public function resourcesAction() {
		$this->tag->setTitle('Course Resources | Student Dashboard');
		$this->view->pageTitle ='Course Resources | Student Dashboard';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		// Get list of concepts
		$conceptsMapping = MappingHelper::allConcepts();
		$concepts = [];
		$resources = [];
		// Get list of resources for each concept
		foreach ($conceptsMapping as $c) {
			// Formatting for view
			$concepts [] = ["id" => $c["Lecture Number"], "title" => $c["Concept Title"], "date" => $c["Date"]];
			// Get resources for each of the concepts
			$conceptId = $c["Lecture Number"];
			$resourceLists[$conceptId] = MappingHelper::resourcesForConcept($conceptId);
			// Get videos for each of the concepts
			$videos = MappingHelper::videosForConcept($conceptId);
			// Format videos to be the same format as ayamel links from the resources.csv mapping
			foreach ($videos as $video) {
				$resourceLists[$conceptId] []= ["Lecture Number" => $video["Lecture Number"], "Resource Type" => "ayamel", "Resource Title" => $video["Video Title"], "Resource Link" => $video["Video ID"]];
			}
		}
		// Figure out which concept to position the list at (based on the current day)
		$currentConceptID = $conceptsMapping[0]["Date"];
		// This is assuming that every concept has a date, and that they are listed in concepts.csv in chronological non-descending order
		// Find the first concept that's past today, and then use the previous concept
		$today = strtotime("today");
		foreach ($conceptsMapping as $concept) {
			if (strtotime($concept["Date"]) > $today) {
				break;
			} else {
				$currentConceptID = $concept["Lecture Number"];
				// If this concept has a date of today, then stop
				if (strtotime($concept["Date"]) == $today) {
					break;
				}
			}
		}
		$this->view->concepts = $concepts;
		$this->view->resources = $resourceLists;
		$this->view->currentConceptID = $currentConceptID;

	}

	public function selectAction() {
		$this->tag->setTitle('Dashboard Selection');
		$this->view->pageTitle ='Dashboard Selection';
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		// Send a statement tracking that they viewed this page
		$statementHelper = new StatementHelper();
		$statement = $statementHelper->buildStatement([
			"statementName" => "dashboardLaunched",
			"dashboardID" => "dashboard_select",
			"dashboardName" => "Dashboard Selector",
			"verbName" => "launched",
		], $context);
		if ($statement) {
			$statementHelper->sendStatements("visualization", [$statement]);
		}
	}
}
