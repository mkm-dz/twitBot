<?php
require_once('utils.php');

// Fill in the following variables.
$access_token = 'PUT ACCESS TOKEN';
$access_token_secret ='PUT ACCESS TOKEN SECRET';
$myconsumerkey       = 'PUT CONSUMER KEY';
$myconsumersecret    = 'INSERT CONSUMER KEY SECRET';

//your user
$user="your username";
$user_whose_followers_to_follow = 'other username';
$trendingTopic = GetTrending();

// Needed for TopicSender
$mensajito = "Insert a message in here";
$trendingArray = GetTrendings(5);
?>

