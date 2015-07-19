<?php

use Phalcon\Mvc\User\Module;

require_once 'ims_lti/blti.php';

class LTIContext extends Module {

	//Returns existing LTI context stored in session. Must be called before output sent.
	//New LTI launches must be routed through public/launch.php before this will successfully fetch contexts.
	static function getContext($config) {
		session_start();
		//Get consumer key and secret key from global config. This config object is passed in from controller.
		//Relevant array keys in config are lti_key and lti_secret
		$context = new BLTI($config, true, false);
		return $context;
	}

}
