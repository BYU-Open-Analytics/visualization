<?php

use Phalcon\Mvc\Controller;

// This controller is called when the dashboard frontend sends statements to the LRS

class XapiController extends Controller
{

	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->disable();

		// Use the StatementHelper class
		$statementHelper = new StatementHelper();
		// Make an actual xAPI statement from the post params
		$statement = $statementHelper->buildStatement($_POST, $context);
		if ($statement) {
			// If we've got a valid statement, send it to the visualization LRS (endpoint, username, password are defined in config.php)
			echo $statementHelper->sendStatements("visualization", [$statement]);
		} else {
			echo "Invalid statement parameters";
		}
	}
}
