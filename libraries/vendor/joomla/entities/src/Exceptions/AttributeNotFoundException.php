<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Exceptions;

use RuntimeException;

/**
 * Class AttributeNotFoundException
 * @package Joomla\Entity\Exeptions
 * @since   1.0
 */
class AttributeNotFoundException extends RuntimeException
{
	/**
	 * The name of the affected model.
	 *
	 * @var string
	 */
	public $model;

	/**
	 * The name of the attribute.
	 *
	 * @var string
	 */
	public $attribute;

	/**
	 * The action the user tries to do.
	 *
	 * @var string
	 */
	public $method;

	/**
	 * Create a new exception instance.
	 *
	 * @param   mixed  $model     model
	 * @param   string $attribute attribute's key
	 * @param   string $method    get or set
	 *
	 * @return static
	 */
	public static function make($model, $attribute, $method)
	{
		$class = get_class($model);

		$instance = new static("Trying to [{$method}] undefined attribute [{$attribute}] on model [{$class}].");

		$instance->model     = $model;
		$instance->attribute = $attribute;
		$instance->method    = $method;

		return $instance;
	}
}
