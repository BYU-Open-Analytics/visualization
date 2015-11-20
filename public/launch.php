<?php 
session_start();
// Load up the Basic LTI Support code
require_once '../app/library/ims_lti/blti.php';

//Get lti key and secret from app's config file
$config = require('../app/config/config.php');
$context = new BLTI($config["lti"]["launch"], true, true);
if ( $context->valid ) {
	// If a dashboard is specified, go specifically to that one.
	if (isset($_GET["dashboard"])) {
		Header("Location: ./dashboard/".$_GET["dashboard"]);
	} else {
		// Otherwise go to the index page, which will determine the dashboard to load based on the research groups
		Header("Location: ./");
	}
}
?>
<html>
<head>
  <title>Dashboard LTI Launch</title>
</head>
<body style="font-family:sans-serif">
<h2>Dashboard LTI Launch</h2>
<?php

if ( $context->valid ) {
    //Dumps all info (post params are stored in here for later use)
    //print_r($context->info);
    if (isset($_GET["debug"])) {
	    print "<pre>\n";
	    print "Context Information:\n\n";
	    print $context->dump();
	    print "</pre>\n";
    }
} else {
    print "<p style=\"color:red\">Could not establish context: ".$context->message.". Try launching this app again.<p>\n";
}

if (isset($_GET["debug"])) {
	print "<pre>\n";
	print "Raw POST Parameters:\n\n";
	foreach($_POST as $key => $value ) {
	    print "$key=$value\n";
	}
	print "</pre>";
}

?>
