<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Administrator\Table;

defined('JPATH_PLATFORM') or die;

/**
 * Trait to apply to the Joomla Entity system which allows it to implement \Joomla\CMS\Table\TableInterface
 *
 * @since  __DEPLOY_VERSION__
 */
trait EntityTableTrait
{
	protected $type_alias;

	/**
	 * @return mixed
	 */
	public function getTypeAlias()
	{
		return $this->type_alias;
	}

	/**
	 * @param mixed $type_alias
	 */
	public function setTypeAlias($type_alias)
	{
		$this->type_alias = $type_alias;
	}


	public function getKeyName()
	{
		return $this->getPrimaryKey();
	}

	// TODO: This should return in the same model instance
	public function load($keys = null, $reset = true)
	{
		if ($reset)
		{
			$this->reset();
		}

		return $this->find($keys);
	}

	public function getProperties()
	{
		return $this->getAttributes();
	}

	public function getDbo()
	{
		return $this->getDb();
	}

	public function getId()
	{
		return $this->getPrimaryKeyValue();
	}

	// TODO: Add to entities?
	public function check()
	{
		return true;
	}

	public function bind($src, $ignore = array())
	{
		if (is_string($ignore))
		{
			$ignore = explode(' ', $ignore);
		}

		// Bind the source value, excluding the ignored fields.
		foreach ($this->getAttributes() as $k => $v)
		{
			// Only process fields not in the ignore array.
			if (!in_array($k, $ignore))
			{
				if (isset($src[$k]))
				{
					$this->setAttribute($k, $src[$k]);
				}
			}
		}

		return true;
	}

	// TODO: How to deal with nulls
	public function store($updateNulls = false)
	{
		return $this->save();
	}

	// TODO This concept doesn't really exist
	public function reset()
	{

	}
}