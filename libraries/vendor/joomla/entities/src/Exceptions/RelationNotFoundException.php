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
 * Class RelationNotFoundException
 * @package Joomla\Entity\Exeptions
 * @since   1.0
 */
class RelationNotFoundException extends RuntimeException
{
	/**
	 * The name of the affected model.
	 *
	 * @var string
	 */
	public $model;

	/**
	 * The name of the relation.
	 *
	 * @var string
	 */
	public $relation;

	/**
	 * Create a new exception instance.
	 *
	 * @param   mixed   $model    model
	 * @param   string  $relation relation
	 * @return static
	 */
	public static function make($model, $relation)
	{
		$class = get_class($model);

		$instance = new static("Call to undefined relation [{$relation}] on model [{$class}].");

		$instance->model = $model;
		$instance->relation = $relation;

		return $instance;
	}
}
