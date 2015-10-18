<?php

use Phalcon\Mvc\Controller;

class StatementsController extends Controller
{
	public function initialize() {
		$this->tag->setTitle('Statements');
	}
	public function indexAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->context = $context;
		if (!$context->valid) {
			return;
		}

		// This contains our different data elements
		$result = Array();
		$statementHelper = new StatementHelper();

		//Get all user answer attempts
		$attempts = $statementHelper->getStatements("",['statement.actor.name'=>$context->getUserName()],[]);
		// Get most recent statements first, and only get 100. (The query hasn't actually been run on the server yet, so we can add more options)
		$cursor = $attempts["cursor"]->sort(['statement.timestamp' => -1]);
		$cursor = $attempts["cursor"]->limit(100);
		$this->view->statements = $cursor;
	}
}
