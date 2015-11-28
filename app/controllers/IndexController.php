<?php

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Home');
	}
	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		// Figure out where this student should go: Content Recommender, Student Skills, or Consent page
		//
		// Period 1: October 22nd through November 13th
			// For Exam 3 time period, Group 0 is control group, Group 1 is Content Dashboard, and Group 2 is Skills Dashboard. 

		// Period 2: November 14th through December 4th
			// For Exam 4 period, Group 0 is Skills Dashboard, Group 1 is control group, Group 2 is Content Dashboard.

		// Period 3: December 5th through December 16th
			// All groups have access to Skills Dashboard and Content Dashboard.
		
		$students = CSVHelper::parseWithHeaders(__DIR__ . "/../config/student_groups.csv");
		$group = -1;
		foreach ($students as $s) {
			if ($s["LTI Name"] == $context->getUserName()) {
				$group = $s["realGroup"];
				break;
			}
		}

		// Group -1 means no consent
		if ($group == -1) {
				$_SESSION["group"] = "noconsent";
				$this->response->redirect('./info/consent');
				$this->view->disable();
				return;
		}

		$current = Date('Y-m-d');
		$period1Start = Date('Y-m-d', strtotime("October 22"));
		$period2Start = Date('Y-m-d', strtotime("November 14"));
		$period3Start = Date('Y-m-d', strtotime("December 5"));
		// Figure out what the student is allowed to access
		if ($current >= $period3Start) {
			// Period 3: all groups have access to both
			// TODO use this somewhere to add the links back in to the nav bar
			$_SESSION["group"] = "both";
		} else if ($current >= $period2Start) {
			// Period 2
			if ($group == 0) {
				$_SESSION["group"] = "skills";
			} else if ($group == 1) {
				$_SESSION["group"] = "control";
			} else if ($group == 2) {
				$_SESSION["group"] = "recommender";
			}
		} else {
			// Period 1
			// $this->view->disable();
			if ($group == 0) {
				$_SESSION["group"] = "control";
			} else if ($group == 1) {
				$_SESSION["group"] = "recommender";
			} else if ($group == 2) {
				$_SESSION["group"] = "skills";
			}
		}
		//echo "Group: $group, session: ".$_SESSION["group"];
		// Now redirect them there
		if ($_SESSION["group"] == "control") {
			$this->response->redirect("./info/control");
		} else if ($_SESSION["group"] == "skills") {
			$this->response->redirect("./dashboard/student_skills");
		} else if ($_SESSION["group"] == "recommender") {
			$this->response->redirect("./dashboard/content_recommender");
		} else {
			// Redirect to menu choice for both
			$this->response->redirect("./dashboard/select");
		}
	}
}
