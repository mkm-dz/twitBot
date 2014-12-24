<?php
require_once('helper.php');
require_once('twitteroauth.php');


/*
 * -------------------------------------------
 * Sends a message and suffixes the message with a trending topic
 * -------------------------------------------
 */
define("TWITTER_CHAR_LIMIT", 140);


$connection = new TwitterOAuth($myconsumerkey, $myconsumersecret, $access_token, $access_token_secret);

for($i = 0; $i < sizeof($trendingArray) ; $i++)
{
	$normalizedMessage = SplitMessage($mensajito, $trendingArray[$i], TWITTER_CHAR_LIMIT);
	
	for($j = 0; $j < sizeof($normalizedMessage) ;$j++)
	{
			$response = $connection->post('statuses/update', array('status' => $normalizedMessage[$j]));
			print("Posteando: ".$normalizedMessage[$j]."<br />");
	}
}

/*
 * Splits one string into several messages that are suffix by a trending string.
 * @param $message The message that it's going to be splitted.
 * @trending The trending topic that it's going to be put at the end of each string
 * @returns An array of strings.
 */
function SplitMessage($message, $trending, $limit)
{
	//
	// Message + trending + single space between message and trending+ hashtag.
	//
	$messageLength = strlen($message) + strlen($trending) + 2;
	$arrayLength = $messageLength / $limit;
	for($ii = 0; $ii <= $arrayLength ;$ii++)
	{
		if($messageLength > $limit)
		{		//
				// Message - trending - "..."- " "-"#"
				//
				$substrLength = strlen($message) - strlen($trending) - 5;
				$finalString[$ii] = substr($message, 0, $substrLength)."... #".$trending;
				$message = substr($message, $substrLength);
				$messageLength = strlen($message) + strlen($trending) + 2;
				
		}
		else
		{
			$finalString[$ii] = $message." #".$trending;
		}
	}

	return $finalString;	
}


?>
