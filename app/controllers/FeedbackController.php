<?php

use Phalcon\Mvc\Controller;

class FeedbackController extends Controller
{
	public function submitAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->disable();
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		if ($context->valid) {
			// Store user feedback
			$feedback = new Feedback();
			$feedback->type = $_POST["feedbackType"];
			$feedback->feedback = $_POST["feedback"];
			$feedback->student_email = $context->getUserEmail();
			$feedback->student_name = $context->getUserName();

			if ($feedback->save()) {
				echo "Thank you for your feedback!";
			} else {
				echo "There was an error saving your feedback";
			}
		} else {
			echo "You must be signed in to submit feedback";
		}
	}
}
