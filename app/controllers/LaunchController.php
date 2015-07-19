<?php

use Phalcon\Mvc\Controller;

class LaunchController extends Controller
{
	public function indexAction() {
		$returnUrl = $this->request->getPost("launch_presentation_return_url");
		if (!empty($returnUrl)) {
			$queryString = strpos($returnUrl,'?') ? "&" : "?";
			$queryString .= "lti_errorlog=The floor's on fire... see... ".urlencode("*&*")." the chair.";
			header("Location: ".$returnUrl.$queryString);
			die();
		}
		//Defer to view
	}
}
