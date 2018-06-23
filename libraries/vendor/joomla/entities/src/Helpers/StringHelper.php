<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Helpers;

/**
 * Class StringHelper
 * @package Joomla\Entity\Helpers
 * @since   1.0
 */
class StringHelper extends \Joomla\String\StringHelper
{

	/**
	 * Check if a String contains a subString
	 *
	 * @param   string   $str     String being examined
	 * @param   string   $search  String being searched for
	 *
	 * @return boolean
	 */
	public static function contains($str, $search)
	{
		return !(parent::strpos($str, $search) == false);
	}

	/**
	 * Check if a String starts with a subString
	 *
	 * @param   string   $str     String being examined
	 * @param   string   $search  String being searched for
	 *
	 * @return boolean
	 */
	public static function startWith($str, $search)
	{
		return (parent::strpos($str, $search) === 0);
	}
}
