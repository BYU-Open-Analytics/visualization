<?php

use Phalcon\Mvc\Controller;

class LaunchController extends Controller
{
	public function indexAction() {
		/* stuff from canvas lti dev tutorial
		$returnUrl = $this->request->getPost("launch_presentation_return_url");
		if (!empty($returnUrl)) {
			$queryString = strpos($returnUrl,'?') ? "&" : "?";
			$queryString .= "lti_errorlog=The floor's on fire... see... ".urlencode("*&*")." the chair.";
			header("Location: ".$returnUrl.$queryString);
			die();
		}
		//Defer to view
		*/
		error_reporting(E_ALL);
		ini_set('session.use_cookies', '0');
		session_start();

		$context = new BLTI(array('table' => 'blti_keys'));
		if ( $context->complete ) exit();
		if ( ! $context->valid ) {
			$this->view->message = "Could not establish context: ".$context->message."<p>\n";
			//exit();
		}
		print "line 26";
		$this->view->disable();
	}
}
