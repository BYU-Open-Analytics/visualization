<?php

return array(
	//MongoDB Database details
	'database' => array(
		'host' => 'localhost',
		'username' => 'lti',
		'password' => 'ltitest',
		'dbname' => 'lti_development'
		),
	//LTI configurations. 'launch' is required. (for launching this app from tool consumer, and for any tool providers this app will consume)
	'lti' => array(
		'launch' => array('lti_key' => 'examplekey', 'lti_secret' => 'examplesecret'),
		'ayamel' => array('launch_url' => 'http://ayamel.byu.edu/lti', 'lti_key' => 'examplekey', 'lti_secret' => 'examplesecret')
		),
	//LRS configurations (key for details array, such as ayamel, will be used in getting/sending statements
	//LRS ID will be at end of a Learning Locker Dashboard URL: https://ec2-52-26-250-81.us-west-2.compute.amazonaws.com/lrs/lrs/<LRS ID is this long thing here>
	'lrs' => array(
		'openassessments' => array(
			'endpoint' => 'http://ec2-52-26-250-81.us-west-2.compute.amazonaws.com/lrs/',
			'id'       => 'id',
			'username' => 'username',
			'password' => 'password'
			),
		'ayamel' => array(
			'endpoint' => 'http://ec2-52-26-250-81.us-west-2.compute.amazonaws.com/lrs/',
			'id'       => 'id',
			'username' => 'username',
			'password' => 'password'
			),
		'visualization' => array(
			'endpoint' => 'http://ec2-52-26-250-81.us-west-2.compute.amazonaws.com/lrs/',
			'id'       => 'id',
			'username' => 'username',
			'password' => 'password'
			)
		),
	//Open Assessments Base URL (for API calls)
	'openassessments_endpoint' => 'http://ec2-52-26-250-81.us-west-2.compute.amazonaws.com/',
	//Phalcon configuration
	'base_uri' => '/visualization/'
	);
?>
