<?php 
session_start();
// Load up the Basic LTI Support code
require_once '../app/library/ims_lti/blti.php';

//Get lti key and secret from app's config file
$config = require('../app/config/config.php');
print_r($config);
$context = new BLTI($config["lti"]["launch"], true, true);
if ( $context->valid ) {
	Header("Location: ./");
}
?>
<html>
<head>
  <title>IMS Basic Learning Tools Interoperability</title>
</head>
<body style="font-family:sans-serif">
<p><b>IMS BasicLTI PHP Provider</b></p>
<p>This is a very simple Basic LTI Tool.  If the message is a Basic LTI Launch,
it checks the signature and if the signature is OK,  it establishes context.
All secrets are "secret".
</p>
<?php

if ( $context->valid ) {
    //Dumps all info (post params are stored in here for later use)
    //print_r($context->info);
    print "<pre>\n";
    print "Context Information:\n\n";
    print $context->dump();
    print "</pre>\n";
} else {
    print "<p style=\"color:red\">Could not establish context: ".$context->message."<p>\n";
}

print "<pre>\n";
print "Raw POST Parameters:\n\n";
foreach($_POST as $key => $value ) {
    print "$key=$value\n";
}
print "</pre>";

?>
