<?php

use Phalcon\Mvc\Controller;

class XapiController extends Controller
{
	public function testAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');

		$testStatement = json_decode('[{ "actor": { "name": "Statement Tester", "account": { "homePage": "http://twitter.com", "name": "sallyglider434" } }, "verb": { "id": "http://adlnet.gov/expapi/verbs/experienced", "display": {"en-US": "experienced"} }, "object": { "id": "http://example.com/activities/solo-hang-gliding", "definition": { "name": { "en-US": "Solo Hang Gliding" }, "extensions" : { "https://ayamel.byu.edu/playerTime" : "1.00" } } } }, { "actor": { "name": "Statement Tester", "account": { "homePage": "http://twitter.com", "name": "sallyglider434" } }, "verb": { "id": "http://adlnet.gov/expapi/verbs/experienced", "display": {"en-US": "experienced"} }, "object": { "id": "http://example.com/activities/solo-hang-gliding", "definition": { "name": { "en-US": "Solo Hang Gliding" } } } }]');

		$statementHelper = new StatementHelper();
		echo $statementHelper->sendStatements("visualization", $testStatement);

		$this->view->disable();
	}

	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->disable();

		$statementHelper = new StatementHelper();
		$statement = $statementHelper->buildStatement($_POST, $context);
		if ($statement) {
			echo $statementHelper->sendStatements("visualization", [$statement]);
		} else {
			echo "Invalid statement parameters";
		}
	}
}
