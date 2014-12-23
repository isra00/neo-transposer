<?php

/**
 * Functions for extended array manipulation.
 */

/**
 * Reads an element of an array, supporting negative indexes and cyclic index.
 * 
 * @param  array $array Any indexed array.
 * @param  integer $index Index to read
 * @return mixed The array element
 */
function array_index($array, $index)
{
	if ($index > count($array) - 1)
	{
		$index = $index % count($array);
	}

	return ($index < 0)
		? $array[count($array) + $index]
		: $array[$index];
}