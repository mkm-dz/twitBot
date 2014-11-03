<?php
 
 /*
  * Makes a binary search.
  */
 if (!function_exists ( "binarySearch" ))
 {
     function binarySearch(array $array, $search)
        {
            $begin  = $mid = 0;
            $end    = count($array) - 1;
            $found  = FALSE;
         
            while ( ! $found && $begin <= $end)
            {
                $mid = (int)(($begin + $end) / 2);
         
                if ($array[$mid] > $search)         $end    = $mid - 1;
                else if ($array[$mid] < $search)    $begin  = $mid + 1;
                else                                $found  = TRUE;
            }
            return ($found) ? $mid : FALSE;
        }
 }
 
 
 /*
 * Retrieves an array of the trending topics by parsing a web page.
 *
 * @param $numberOfTopics Specifies the number of topics that will be retrieved from the webpage.
 * @returns An array containing the trending topics.
 */ 
if (!function_exists ( "GetTrendings" ))
{
    function GetTrendings($numberOfTopics)
    {
	    $html = file_get_contents("http://www.trendinalia.com/twitter-trending-topics/mexico");
	    $first_token  = split("#", $html);
	    
	    for($i=0;$i<$numberOfTopics;$i++)
	    {
	        $topicArray[$i] = split("\"", $first_token[$i])[0];
	    }

        return $topicArray;
    }
}

/*
 * Retrieves a random trending topic.
 */
if (!function_exists ( "GetTrending" ))
{
    function GetTrending()
    {
	    $topicArray = GetTrendings(7);
	    if(sizeof($topicArray) > 0)
	    {
    	    $randomIndex = rand(0, sizeof($topicArray));
    	    return $topicArray[$randomIndex];
	    }
	    else
	    {
	        return "";
	    }
    }
}

?>
