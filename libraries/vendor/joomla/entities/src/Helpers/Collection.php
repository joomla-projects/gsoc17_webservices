<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Helpers;

use ArrayAccess;
use Joomla\Entity\Exceptions\JsonEncodingException;
use JsonSerializable;
use IteratorAggregate;
use ArrayIterator;
use Joomla\Entity\Model;


/**
 * Class Collection
 * @package Joomla\Entity\Helpers
 * @since   1.0
 */
class Collection implements ArrayAccess, IteratorAggregate, JsonSerializable
{
	/**
	 * The items contained in the collection.
	 *
	 * @var Model[]
	 */
	protected $items = [];

	/**
	 * Create a new collection.
	 *
	 * @param   Model[]  $items array of Models
	 */
	public function __construct($items = [])
	{
		$this->items = $items;
	}

	/**
	 * Get an iterator for the items.
	 *
	 * @return \ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->items);
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->items[$offset];
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @param   mixed  $value  value to be set
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		if (is_null($offset))
		{
			$this->items[] = $value;
		}
		else
		{
			$this->items[$offset] = $value;
		}
	}

	/**
	 * Unset the item at a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->items[$offset]);
	}


	/** Method to convert the Collection to array format.
	 * @return array
	 */
	public function toArray()
	{
		return array_map(
			function ($value)
			{
				if ($value instanceof Model || $value instanceof Collection)
				{
					return $value->toArray();
				}
				else
				{
					// We suppose that the value is a serializable data type
					return $value;
				}

			},
			$this->items
		);
	}
	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	/**
	 * Convert the collection instance to JSON.
	 *
	 * @param   int  $options json_encode Bitmask
	 *
	 * @return string
	 *
	 * @throws JsonEncodingException
	 */
	public function toJson($options = 0)
	{
		$json = json_encode($this->jsonSerialize(), $options);

		if (JSON_ERROR_NONE !== json_last_error())
		{
			throw JsonEncodingException::forModel($this, json_last_error_msg());
		}

		return $json;
	}

	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		return empty($this->items);
	}

	/**
	 * Get the first item in the collection
	 *
	 * @param   mixed $default default value to be returned when empty Collection
	 * @return mixed
	 */
	public function first($default = false)
	{
		if ($this->isEmpty())
		{
			return $default;
		}

		return $this->items[0];
	}

	/**
	 * Get the all the items in the collection as an array
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Find a model in the collection by key.
	 *
	 * @param   mixed  $key     key to be found
	 * @param   mixed  $default default value to be returned when key not found
	 * @return mixed
	 */
	public function find($key, $default = false)
	{
		if ($key instanceof Model)
		{
			$key = $key->getPrimaryKeyValue();
		}

		foreach ($this->items as $item)
		{
			if ($item->getPrimaryKeyValue() == $key)
			{
				return $item;
			}
		}

		return $default;
	}

	/**
	 * Adds an item to the collection
	 *
	 * @param   mixed $item item
	 * @return void
	 */
	public function add($item)
	{
		$this->items[] = $item;
	}
}
