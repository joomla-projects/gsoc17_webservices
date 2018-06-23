<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\ModelHelpers;

use DateTimeInterface;
use Joomla\Entity\Exceptions\JsonEncodingException;
use Joomla\Entity\Helpers\Collection;
use Joomla\Entity\Model;

/**
 * Trait Attributes
 * @package Joomla\Entity\Helpers
 * @since 1.0
 */
trait Serialization
{
	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array
	 */
	protected $hidden = [];

	/**
	 * The attributes that should be hidden for serialization.
	 * This is the original value that will be populated from the $hidden array
	 * in the constructor, this shall be never changed by the developer.
	 * These attributes cannot ever be serialised for security reasons.
	 *
	 * @var array
	 */
	private $originalHidden = [];

	/**
	 * Get the hidden attributes for the model.
	 *
	 * @return array
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * Adds a hidden attribute or array of attributes to the current instance.
	 *
	 * @param   string/array $value = attribute(s) to be made hidden for serialization
	 *
	 * @return void
	 */
	public function addHidden($value)
	{
		if (is_array($value))
		{
			$this->hidden = array_merge($this->hidden, $value);
		}
		else
		{
			$this->hidden[] = $value;
		}
	}

	/**
	 * Removes a hidden attribute or array of attributes to the current instance.
	 *
	 * @param   string/array $value = attribute(s) to be made hidden for serialization
	 *
	 * @return void
	 */
	public function removeHidden($value)
	{
		if (!is_array($value))
		{
			$value = [$value];
		}

		foreach ($value as $key)
		{
			if (!array_key_exists($key, $this->originalHidden))
			{
				unset($this->hidden[$key]);
			}
		}
	}

	/**
	 * Filter hidden attributes for serialisation
	 *
	 * @param   array  $assocArray attributes or relations array
	 * @return array
	 */
	protected function getSerializableItems(array $assocArray)
	{
		if (count($this->getHidden()) > 0)
		{
			$assocArray = array_diff_key($assocArray, array_flip($this->getHidden()));
		}

		return $assocArray;
	}

	/**
	 * Method used for serialization!
	 *
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array_merge($this->getAttributesAsArray(), $this->getRelationsAsArray());
	}

	/**
	 * Method used for serialization!
	 *
	 * Get all of the current processed attributes on the model.
	 * Everything is ready to be serialised.
	 * processed = dates, cast, mutations
	 *
	 * @return array
	 */
	public function getAttributesAsArray()
	{
		/** If an attribute is a date, we will cast it to a string after converting it
		 * to a DateTime / Carbon instance. This is so we will get some consistent
		 * formatting while accessing attributes vs. JSONing a model.
		 */

		$attributesRaw = $this->getAttributesRaw();

		$attributes = [];

		foreach ($attributesRaw as $key => $value)
		{
			/** First, we need to convert the raw attributes to the ones exposed by
			 * the column aliases. At this point no mutated attributes will be
			 * in the $attributes array.
			 */
			$attributes[$this->getColumnAlias($key)] = $value;
		}

		// Filter items that are hidden from serialization (e.g. password)
		$attributes = $this->getSerializableItems($attributes);

		$attributes = $this->addDateAttributes($attributes);

		$attributes = $this->addMutatedAttributes(
			$attributes, $mutatedAttributes = $this->getMutatorMethods()
		);

		/** Next we will handle any casts that have been setup for this model and cast
		 * the values to their appropriate type. If the attribute has a mutator we
		 * will not perform the cast on those attributes to avoid any confusion.
		 */
		$attributes = $this->addCastAttributes(
			$attributes, $mutatedAttributes
		);

		return $attributes;
	}

	/**
	 * Method used for serialization!
	 *
	 * Get all the loaded relations for the instance.
	 * Relations are serialised (array format).
	 *
	 * @return array
	 */
	public function getRelationsAsArray()
	{
		// Filter items that are hidden from serialization (e.g. password)
		$relations = $this->getSerializableItems($this->getRelations());

		foreach ($relations as $key => $value)
		{
			/** First, we try need to check for the instance to be converted
			 * to be of a supported type, Model or Collection. Then, we go ahead
			 * and convert it to array.
			 */
			if ($value instanceof Model || $value instanceof Collection)
			{
				$relation = $value->toArray();
			}

			/** If the value is null, we'll still go ahead and set it in this list of
			 * attributes since null is used to represent empty relationships if
			 * if it a has one or belongs to type relationships on the models.
			 */
			elseif (is_null($value))
			{
				$relation = $value;
			}

			/** If the relation value has been set, we will set it on this attributes
			 * list for returning. If its not a Model, Collection or null, we'll not set
			 * the value on the array because it is some type of invalid value.
			 */
			if (isset($relation) || is_null($value))
			{
				$relations[$key] = $relation;
			}

			unset($relation);
		}

		return $relations;
	}

	/**
	 * Prepare a date for array / JSON serialization.
	 *
	 * @param   \DateTimeInterface  $date date
	 * @return string
	 */
	protected function serializeDate(DateTimeInterface $date)
	{
		return $date->format($this->getDateFormat());
	}

	/**
	 * Convert the model instance to JSON.
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
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}
