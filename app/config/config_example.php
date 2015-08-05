<?php

return array(
	//MySQL Database details
	'database' => array(
		'host' => 'localhost',
		'username' => 'lti',
		'password' => 'ltitest',
		'name' => 'lti_development'
		),
	//LTI configurations. 'launch' is required. (for launching this app from tool consumer, and for any tool providers this app will consume)
	'lti' => array(
		'launch' => array('lti_key' => 'examplekey', 'lti_secret' => 'examplesecret'),
		'ayamel' => array('lti_key' => 'examplekey', 'lti_secret' => 'examplesecret')
		),
	//LRS configurations (key for details array, such as ayamel, will be used in getting/sending statements
	'lrs' => array(
		'openassessments' => array(
			'endpoint' => 'http://ec2-52-26-250-81.us-west-2.compute.amazonaws.com/lrs/',
			'username' => 'username',
			'password' => 'password'
			),
		'ayamel' => array(
			'endpoint' => 'http://ec2-52-26-250-81.us-west-2.compute.amazonaws.com/lrs/',
			'username' => 'username',
			'password' => 'password'
			),
		'visualization' => array(
			'endpoint' => 'http://ec2-52-26-250-81.us-west-2.compute.amazonaws.com/lrs/',
			'username' => 'username',
			'password' => 'password'
			)
		),
	//Phalcon configuration
	'base_uri' => '/lti_php/'
	);
?>
