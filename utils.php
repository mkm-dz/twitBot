<?php
 
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

?>
