<?php
// Load up the Basic LTI Support code
require_once '../app/library/ims_lti/blti.php';

//Get lti key and secret from app's config file
$config = require('../app/config/config.php');
$context = new BLTI($config["lti"]["launch"], false, false);
if ( ! $context->valid ) {
    print "<p style=\"color:red\">Could not establish context: ".$context->message."<p>\n";
    die();
}

$qualtrix_survey_id = $_GET['SID'];
$user_token = $_POST['user_id']; // note: this is an "anonymized" 320bit hash
$survey_version = $_GET['version'];
$link_label = "Launch Survey";

$get_array = array(
    'SID' => $qualtrix_survey_id,
    'a' => $user_token,
    'version' => $survey_version,
);

$redirect_url = 'https://qtrial2015az1.az1.qualtrics.com/SE/';
?>
<html>
<head>
    <style>
        body { background-color:#aaa;height:130px;border:0;margin:0;padding:0;overflow:hidden; }
        input#submit_button { display:block;width:98%;height:108px;margin:10px 1%;padding:10px; }
    </style>
</head>
<body>
    <form method="GET" action="<?php echo $redirect_url; ?>">
        <?php
            foreach ($get_array as $key => $val) {
                echo "<input type=\"hidden\" name=\"$key\" value=\"$val\">";
            }
        ?>
        <input id="submit_button" name="ext_submit" type="submit" value="<?php echo $link_label; ?>">
    </form>
    <script>
	document.getElementsByName("ext_submit")[0].click();
    </script>
</body>
</html>
