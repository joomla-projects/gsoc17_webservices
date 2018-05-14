<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Serializer;

defined('_JEXEC') or die;

use Joomla\Registry\Registry;
use Tobscure\JsonApi\AbstractSerializer;

/**
 * Temporary serializer
 *
 * @since  __DEPLOY_VERSION__
 */
class YmlSerializer extends AbstractSerializer
{
	/**
	 * The path to the yml file to be loaded
	 *
	 * @var   string
	 * @since __DEPLOY_VERSION__
	 */
	protected $filePath = '';

	/**
	 * Constructor.
	 *
	 * @param   string  $type      The content type to be loaded
	 * @param   string  $filePath  The path to the yml file to be loaded
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function __construct(string $type, string $filePath)
	{
		$this->type = $type;
		$this->filePath = $filePath;
	}

	/**
	 * Get the attributes array.
	 *
	 * @param   mixed  $post    The model
	 * @param   array  $fields  The fields can be array or null
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getAttributes($post, array $fields = null)
	{
		$dataSource = new Registry;
		$information = $dataSource->loadFile($this->filePath, 'yaml')
			->toArray()[ucfirst(strtolower($this->type)) . 'Provider'];
		$result = [];
		$exposeProperties = true;

		if ($information['exclusion_policy'] === 'ALL')
		{
			$exposeProperties = false;
		}

		foreach ($information['properties'] as $propertyName => $property)
		{
			if (isset($property['expose']) && $property['expose'] === true
				|| !isset($property['expose']) && $exposeProperties === true)
			{
				$result[$propertyName] = $post->{$propertyName};
			}
		}

		return $result;
	}
}
