<?php
require_once('../main_helper.php');
require_once('twitteroauth.php');

//
// We destroy any open session then we start a new one again.
//
session_start();
session_destroy();

session_start();

//
// Create a connection to get the verifier.
// 
$connection = new TwitterOAuth($myconsumerkey, $myconsumersecret);
$request_token = $connection->getRequestToken("oob");

//
// Save temporary credentials to session.
//
$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
 
// 
// If last connection failed don't display authorization link.
//
switch ($connection->http_code) {
  case 200:
    print("Copy and paste the following link, the put the verifier number inside the Verifier_Step2.php file<br />");
    $url = $connection->getAuthorizeURL($token);
    print('Location: ' . $url); 
    break;
  default:
    /* Show notification if something went wrong. */
    echo 'Could not connect to Twitter. Refresh the page or try again later.';
}

?>
