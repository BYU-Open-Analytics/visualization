<?php
session_start();
// Load up the Basic LTI Support code
require_once '../app/library/ims_lti/blti.php';
require_once '../app/library/ims_lti/blti_util.php';

//Get lti key and secret from app's config file
$config = require('../app/config/config.php');
$context = new BLTI($config["lti"]["launch"], true, false);
// Make sure we've got a valid session from the Visualization launch first
if (!$context->valid) {
	die("Invalid LTI context. This must be launched from another tool consumer before it can be a tool consumer. Confused? Me too.");
}

    $lmsdata = array(
      "resource_link_id" => "120988f929-274612",
      "resource_link_title" => "Weekly Blog",
      "resource_link_description" => "A weekly blog.",
      "user_id" => "292832126",
      "roles" => "Learner",  // or Learner
      "lis_person_name_full" => 'Jane Q. Public',
      "lis_person_contact_email_primary" => "user@school.edu",
      "lis_person_sourcedid" => "school.edu:user",
      "context_id" => "456434513",
      "context_title" => "Design of Personal Environments",
      "context_label" => "SI182",
      "tool_consumer_instance_guid" => "lmsng.school.edu",
      "tool_consumer_instance_description" => "University of School (LMSng)",
      );

  foreach ($lmsdata as $k => $val ) {
      if ($context->info[$k] && strlen($context->info[$k]) > 0 ) {
          $lmsdata[$k] = $context->info[$k];
      }
  }

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
  if ( ! $key ) $key = "12345";
  $secret = $_REQUEST["secret"];
  if ( ! $secret ) $secret = "secret";
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
