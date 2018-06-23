<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Exceptions;

use RuntimeException;

/**
 * Class JsonEncodingException
 * @package Joomla\Entity
 * @since   1.0
 */
class JsonEncodingException extends RuntimeException
{
	/**
	 * Create a new JSON encoding exception for the model
	 *
	 * @param   mixed   $model   ?
	 * @param   string  $message ?
	 * @return static
	 */
	public static function forModel($model, $message)
	{
		return new static('Error encoding model [' . get_class($model) . '] with ID [' . $model->getPrimaryKeyValue() . '] to JSON: ' . $message);
	}

	/**
	 * Create a new JSON encoding exception for an attribute.
	 *
	 * @param   mixed  $model   ?
	 * @param   mixed  $key     ?
	 * @param   string $message ?
	 * @return static
	 */
	public static function forAttribute($model, $key, $message)
	{
		$class = get_class($model);

		return new static("Unable to encode attribute [{$key}] for model [{$class}] to JSON: {$message}.");
	}
}
