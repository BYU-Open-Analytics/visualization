<?php

use Phalcon\Mvc\User\Module;

require_once 'ims_lti/blti.php';

class LTIContext extends Module {

	//Returns existing LTI context stored in session. Must be called before output sent.
	//New LTI launches must be routed through public/launch.php before this will successfully fetch contexts.
	static function getContext() {
		session_start();
		//TODO Get secret key from global config
		$secret = "secret";
		$context = new BLTI($secret, true, false);
		return $context;
	}

}
