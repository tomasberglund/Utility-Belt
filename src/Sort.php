<?php

/**
 * The class in this file attempts to put an end to having to define sorting
 * functions for arbitrtay data with various dedicated and generic methods.
 * 
 * @package Utilities
 * @author Nev Stokes <mail@nevstokes.com>
 */

namespace nevstokes\Utilities;

/**
 * This class defines common sorting routines to be used as callbacks by
 * PHP's native functions like usort which accept callbacks.
 * 
 * @package Utilities
 */
class Sort
{

	/**
	 * Generic sorting callback
	 * 
	 * Used by other methods in the class to perform simple sorting.
	 * 
	 * @param mixed $a
	 * @param mixed $b
	 * @return int
	 */
	public function cmp($a, $b)
	{
		if ($a == $b) {
			return 0;
		}

		return ($a < $b) ? -1 : 1;
	}

	/**
	 * Magic method to sort an associative array by a named key
	 * 
	 * Sorts an associative array on a single key in ascending order only.
	 * Simply prefix the key on which you want to order with "_by". Note that
	 * this is case-sensitive due to reliance on the array keys themselves.
	 * 
	 * @param string $method
	 * @param array $arguments
	 * @return int
	 */
	public function __call($method, $arguments)
	{
		$a = array_shift($arguments);
		$b = array_shift($arguments);

		if (preg_match('/^_by([a-zA-Z0-9_]*)/', $method, $matches)) {
			$field = $matches[1];
			$order = $this->cmp($a[$field], $b[$field]);

			return $order;
		}
	}

	/**
	 * Utility method to sort an associative array by named keys
	 * 
	 * Sorts an associated array on multiple keys with ordering specified at
	 * individual levels (defaulting to ascending). Can be used to add many
	 * levels of ordering. (e.g. Sort on surname, then forename and then age.)
	 * 
	 * @param array $args
	 * @return function
	 */
	public function by($args)
	{
		// Assign the generic callback to a variable to use in the closure
		$fn = array($this, 'cmp');

		// A closure to actually perform the sort
		return function($a, $b) use ($args, $fn) {
			$keys = array_keys($args);
			$values = array_values($args);

			// Loop over each key to sort on
			do {
				if (is_numeric($keys[0])) {
					// Standard indexed array element
					$index = array_shift($keys);
					$field = array_shift($values);

					// Default ordering is ascending
					$order = call_user_func($fn, $a[$field], $b[$field]);
				} else {
					// Associative array element
					$field = array_shift($keys);
					$dir = array_shift($values);

					// Order the elements appropriately
					$order = ('desc' == $dir) ? 
						call_user_func($fn, $b[$field], $a[$field]) : call_user_func($fn, $a[$field], $b[$field]);
				}
			} while ((0 == $order) && !empty($keys));

			return $order;
		};
	}

	/**
	 * Callback method to sort by element length
	 * 
	 * If the elements are both arrays then the sort will be performed
	 * with respect to cardinality.
	 * 
	 * @param mixed $a
	 * @param mixed $b
	 * @return integer
	 */
	public function byLength($a, $b)
	{
		if (is_array($a) && is_array($b)) {
			return count($b) - count($a);
		}

		return strlen($b) - strlen($a);
	}

}

?>