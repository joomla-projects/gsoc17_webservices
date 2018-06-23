<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Helpers;

/**
 * Class ArrayHelper
 * @package Joomla\Entity\Helpers
 * @since   1.0
 */
class ArrayHelper
{
	/**
	 * Set an array item to a given value using "->" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param   array   $array array to mo modified
	 * @param   string  $key   key to be changed
	 * @param   mixed   $value new value
	 * @return array
	 */
	public static function set(&$array, $key, $value)
	{
		if (is_null($key))
		{
			return $array = $value;
		}

		$keys = explode('->', $key);

		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			/**
			 * If the key doesn't exist at this depth, we will just create an empty array
			 * to hold the next value, allowing us to create the arrays to hold final
			 * values at the correct depth. Then we'll keep digging into the array.
			 */
			if (! isset($array[$key]) || ! is_array($array[$key]))
			{
				$array[$key] = [];
			}

			$array = &$array[$key];
		}

		$array[array_shift($keys)] = $value;

		return $array;
	}


	/**
	 * Method to check whether an array is associative or sequential
	 *
	 * @param   array $arr array: associative os sequential
	 *
	 * @return boolean
	 */
	public static function isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}
