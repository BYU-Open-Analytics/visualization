<?php 

use Phalcon\Mvc\User\Module;

class StatsHelper extends Module {

	// From http://stackoverflow.com/a/19210067/702643
	public static function boxPlotValues($array)
	{
	    $return = array(
		'lower_outlier'  => 0,
		'min'            => 0,
		'q1'             => 0,
		'median'         => 0,
		'q3'             => 0,
		'max'            => 0,
		'higher_outlier' => 0,
		'iqr'            => 0,
	    );

	    $array_count = count($array);
	    sort($array, SORT_NUMERIC);

	    $return['min']            = $array[0];
	    $return['lower_outlier']  = $return['min'];
	    $return['max']            = $array[$array_count - 1];
	    $return['higher_outlier'] = $return['max'];
	    $middle_index             = floor($array_count / 2);
	    $return['median']         = $array[$middle_index]; // Assume an odd # of items
	    $lower_values             = array();
	    $higher_values            = array();

	    // If we have an even number of values, we need some special rules
	    if ($array_count % 2 == 0)
	    {
		// Handle the even case by averaging the middle 2 items
		$return['median'] = round(($return['median'] + $array[$middle_index - 1]) / 2);

		foreach ($array as $idx => $value)
		{
		    if ($idx < ($middle_index - 1)) $lower_values[]  = $value; // We need to remove both of the values we used for the median from the lower values
		    elseif ($idx > $middle_index)   $higher_values[] = $value;
		}
	    }
	    else
	    {
		foreach ($array as $idx => $value)
		{
		    if ($idx < $middle_index)     $lower_values[]  = $value;
		    elseif ($idx > $middle_index) $higher_values[] = $value;
		}
	    }

	    $lower_values_count = count($lower_values);
	    $lower_middle_index = floor($lower_values_count / 2);
	    $return['q1']       = $lower_values[$lower_middle_index];
	    if ($lower_values_count % 2 == 0)
		$return['q1'] = round(($return['q1'] + $lower_values[$lower_middle_index - 1]) / 2);

	    $higher_values_count = count($higher_values);
	    $higher_middle_index = floor($higher_values_count / 2);
	    $return['q3']        = $higher_values[$higher_middle_index];
	    if ($higher_values_count % 2 == 0)
		$return['q3'] = round(($return['q3'] + $higher_values[$higher_middle_index - 1]) / 2);

	    // Check if min and max should be capped
	    $iqr = $return['q3'] - $return['q1']; // Calculate the Inner Quartile Range (iqr)
	    $return['iqr'] = $iqr;
	    if ($return['q1'] > $iqr)                  $return['min'] = $return['q1'] - $iqr;
	    if ($return['max'] - $return['q3'] > $iqr) $return['max'] = $return['q3'] + $iqr;

	    return $return;
	}

	// From http://stackoverflow.com/a/8137455
	// Put percentile calculation function here
}
