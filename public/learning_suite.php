<?php
session_start();
// Load up the Basic LTI Support code
require_once '../app/library/ims_lti/blti.php';
require_once '../app/library/ims_lti/blti_util.php';

//Get lti key and secret from app's config file
//$config = require('../app/config/config.php');
//$context = new BLTI($config["lti"]["launch"], true, false);
// Make sure we've got a valid session from the Visualization launch first
//if (!$context->valid) {
	//die("Invalid LTI context. This must be launched from another tool consumer before it can be a tool consumer. Confused? Me too.");
//}

    $lmsdata = array(
      "context_id" => "7RW0sR1PLoj_",
      "context_label" => "CHEM 105",
      "context_title" => "General College Chemistry",
      "context_type" => "CourseSection",
      "launch_presentation_document_target" => "window",
      "launch_presentation_locale" => "en-US",
      "launch_presentation_return_url" => "https://learningsuite.byu.edu/LTI/ltiEnd.php",
      "lis_outcome_service_url" => "https://learningsuite.byu.edu/LTI/ltiLis.php?id=aEt-wDgM5oN3&appId=lti_view",
      "lis_person_contact_email_primary" => "bodilyrobert@gmail.com",
      "lis_person_name_full" => "Bob Bodily",
      "lis_result_sourcedid" => "_T_SE3K9u321:122262432",
      "lti_message_type" => "basic-lti-launch-request",
      "lti_version" => "LTI-1p0",
      "oauth_callback" => "about:blank",
      "oauth_consumer_key" => "byuopenassessments",
      "oauth_nonce" => "9501f452bc32a7e38b6a7524d0a65da4",
      "oauth_signature_method" => "HMAC-SHA1",
      "oauth_timestamp" => "1439478687",
      "oauth_version" => "1.0",
      "resource_link_id" => "aEt-wDgM5oN3:_T_SE3K9u321",
      "roles" => "Instructor",
      "user_id" => "122262432",


      //"resource_link_id" => "120988f929-274612",
      //"resource_link_title" => "Weekly Blog",
      //"resource_link_description" => "A weekly blog.",
      //"user_id" => "292832126",
      //"roles" => "Learner",  // or Learner
      //"lis_person_name_full" => 'Jane Q. Public',
      //"lis_person_contact_email_primary" => "user@school.edu",
      //"lis_person_sourcedid" => "school.edu:user",
      //"tool_consumer_instance_guid" => "lmsng.school.edu",
      //"tool_consumer_instance_description" => "University of School (LMSng)",
      );

  //foreach ($lmsdata as $k => $val ) {
      //if ($context->info[$k] && strlen($context->info[$k]) > 0 ) {
          //$lmsdata[$k] = $context->info[$k];
      //}
  //}

   function curPageURL() {
      $pageURL = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
             ? 'http'
             : 'https';
      $pageURL .= "://";
      $pageURL .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
      return $pageURL;
   }



  $cur_url = curPageURL();
  $key = $_REQUEST["key"];
  if ( ! $key ) $key = "examplekey";
  $secret = $_REQUEST["secret"];
  if ( ! $secret ) $secret = "examplesecret";
  $endpoint = $_REQUEST["endpoint"];

  if ( ! $endpoint ) $endpoint = str_replace("lms.php","tool.php",$cur_url);
  $urlformat = true;
  $tool_consumer_instance_guid = $lmsdata['tool_consumer_instance_guid'];
  $tool_consumer_instance_description = $lmsdata['tool_consumer_instance_description'];

?>
<html>
<head>
  <title>IMS Basic Learning Tools Interoperability</title>
</head>
<body style="font-family:sans-serif">
<script language="javascript"> 
  //<![CDATA[ 
function lmsdataToggle() {
    var ele = document.getElementById("lmsDataForm");
    if(ele.style.display == "block") {
        ele.style.display = "none";
    }
    else {
        ele.style.display = "block";
    }
} 
  //]]> 
</script>
<a id="displayText" href="javascript:lmsdataToggle();">Toggle Resource and Launch Data</a>
<?php
  echo("<form method=\"post\" id=\"lmsDataForm\" style=\"display:block\">\n");
  echo("<input type=\"submit\" value=\"Recompute Launch Data\">\n");
  echo("(To set a value to 'empty' - set it to a blank)");
  echo("<fieldset><legend>BasicLTI Resource</legend>\n");
  echo("Launch URL: <input size=\"60\" type=\"text\" name=\"endpoint\" value=\"$endpoint\">\n");
  echo("<br/>Key: <input type\"text\" name=\"key\" value=\"$key\">\n");
  echo("<br/>Secret: <input type\"text\" name=\"secret\" value=\"$secret\">\n");
  echo("</fieldset><p>");
  echo("<fieldset><legend>Launch Data</legend>\n");
  foreach ($lmsdata as $k => $val ) {
      echo($k.": <input type=\"text\" name=\"".$k."\" value=\"");
      echo(htmlspecialchars($val));
      echo("\"><br/>\n");
  }
  echo("</fieldset><p>");
  echo("</form>");
  echo("<hr>");

  if ( ! $lmspw ) unset($tool_consumer_instance_guid);

    $parms = $lmsdata;

  // Cleanup parms before we sign
  foreach( $parms as $k => $val ) {
    if (strlen(trim($parms[$k]) ) < 1 ) {
       unset($parms[$k]);
    }
  }

  // Add oauth_callback to be compliant with the 1.0A spec
  $parms["oauth_callback"] = "about:blank";

  $parms = signParameters($parms, $endpoint, "POST", $key, $secret, "Press to Launch", $tool_consumer_instance_guid, $tool_consumer_instance_description);

  $content = postLaunchHTML($parms, $endpoint, true, false);
     //"width=\"100%\" height=\"900\" scrolling=\"auto\" frameborder=\"1\" transparency");
  print($content);

?>
