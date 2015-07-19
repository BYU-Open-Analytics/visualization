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
		session_start();

		$context = new BLTI(array('table' => 'blti_keys'));
		if ( $context->valid ) {
		    print "<pre>\n";
		    print "Context Information:\n\n";
		    print $context->dump();
		    print "</pre>\n";
		} else {
		    print "<p style=\"color:red\">Could not establish context: ".$context->message."<p>\n";
		}
		print "line 26";
		$this->view->disable();
	}

	public function toolAction() {
		$this->view->disable();
		//print_r(scandir("../app/library"));
		// Load up the Basic LTI Support code
		$context = LTIContext::getContext();
		if ( $context->valid ) {
		    print "<pre>\n";
		    print "Context Information:\n\n";
		    print $context->dump();
		    print "</pre>\n";
		} else {
		    print "<p style=\"color:red\">Invalid LTI context. Could not establish context: ".$context->message."<p>\n";
		}
	}
}
