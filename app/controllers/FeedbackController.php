<?php

use Phalcon\Mvc\Controller;

// This controller is called when a student sends feedback from one of the dashboards

class FeedbackController extends Controller
{
	// Save feedback submitted via an ajax call from the dashboard frontend
	public function submitAction() {
		// Get our context (this takes care of starting the session, too)
		$context = $this->getDI()->getShared('ltiContext');
		$this->view->disable();
		$this->view->ltiContext = $context;
		$this->view->userAuthorized = $context->valid;
		// Check for valid LTI context
		if ($context->valid) {
			// Store user feedback in the Feedback Phalcon model
			$feedback = new Feedback();
			$feedback->type = $_POST["feedbackType"];
			$feedback->feedback = $_POST["feedback"];
			$feedback->student_email = $context->getUserEmail();
			$feedback->student_name = $context->getUserName();
			// The Phalcon model code takes care of creating a new row in the PostgreSQL database
			if ($feedback->save()) {
				echo "Thank you for your feedback!";
			} else {
				echo "There was an error saving your feedback";
			}
      		// Send an email with this feedback to the developer
			$to = $this->getDI()->getShared('config')->feedback_email;
			$subject = 'Dashboard Feedback: ' . $feedback->type . " " . date(DATE_RFC2822);
			$message = $feedback->feedback . "\n Sent by " . $feedback->student_name . ", " . $feedback->student_email;
			$headers = 'From: admin@byuopenanalytics-dashboard.com' . "\r\n" .
				'Reply-To: admin@byuopenanalytics-dashboard.com' . "\r\n" .
				'X-Mailer: PHP/' . phpversion();

			mail($to, $subject, $message, $headers);
		} else {
			echo "You must be signed in to submit feedback";
		}
	}
}
