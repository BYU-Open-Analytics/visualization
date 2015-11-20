<?php
session_start();
// Load up the Basic LTI Support code
require_once '../app/library/ims_lti/blti.php';
require_once '../app/library/ims_lti/blti_util.php';

//Get lti key and secret from app's config file
$config = require('../app/config/config.php');
$context = new BLTI($config["lti"]["launch"], true, false);

    $lmsdata = array(
      "resource_link_id" => "429785226",
      "resource_link_title" => "Resource title",
      "resource_link_description" => "Resource description",
      "user_id" => "29123",
      "roles" => "Instructor",  // Learner or Instructor 
      "lis_person_name_full" => "John Logie Baird",
      "lis_person_contact_email_primary" => "john@example.com",
      "lis_person_sourcedid" => "sis:942a8dd9",
      "context_id" => "S3294476",
      "context_title" => "Context Title",
      "context_label" => "Context Label",
      "tool_consumer_instance_guid" => "demo.byuopenanalytics",
      "tool_consumer_instance_description" => "Description",
      );

   function curHost() {
      $pageURL = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
             ? 'http'
             : 'https';
      $pageURL .= "://";
      $pageURL .= $_SERVER['HTTP_HOST'];
      return $pageURL;
   }

  $endpoint = curHost().$config["base_uri"]."launch.php?dashboard=".$_GET["dashboard"];
  $key = $config["lti"]["launch"]["lti_key"];
  $secret = $config["lti"]["launch"]["lti_secret"];

  $tool_consumer_instance_guid = $lmsdata['tool_consumer_instance_guid'];
  $tool_consumer_instance_description = $lmsdata['tool_consumer_instance_description'];

?>
<html>
<head>
  <title>Tool Launch</title>
</head>
<body style="font-family:sans-serif">
<?php
  echo("<form method=\"post\" id=\"lmsDataForm\" style=\"display:none\">\n");
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
  print($content);

?>
<script>
document.getElementsByName("ext_submit")[0].click();
</script>
