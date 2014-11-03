<?php
//
// Start the session to retrieve the variables
//
session_start();
require_once('../main_helper.php');
require_once('twitteroauth.php');

$verifierValue="insert_value_here";

//
// Create the new connection to exchange the ouath_token and secret for the access_token and secret
//
$connection = new TwitterOAuth($myconsumerkey, $myconsumersecret, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

$access_token = $connection->getAccessToken($verifierValue);
$content = $connection->get('account/verify_credentials');

print("Please save the following data:<br />Access token:". $access_token['oauth_token']."<br />Access token secret:".$access_token['oauth_token_secret']);

session_destroy();
?>
