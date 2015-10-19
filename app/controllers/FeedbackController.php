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
      
			$to = $this->getDI()->getShared('config')->feedback_email;
			$subject = 'Dashboard Feedback: ' . $_POST["feedback"] . " " . date(DATE_RFC2822);
			$message = $_POST["feedback"];
			$headers = 'From: admin@byuopenanalytics-dashboard.com' . "\r\n" .
				'Reply-To: admin@byuopenanalytics-dashboard.com' . "\r\n" .
				'X-Mailer: PHP/' . phpversion();

			mail($to, $subject, $message, $headers);
		} else {
			echo "You must be signed in to submit feedback";
		}
	}
}
