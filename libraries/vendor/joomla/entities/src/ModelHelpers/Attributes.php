<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\ModelHelpers;

use Joomla\Entity\Exceptions\AttributeNotFoundException;
use Joomla\String\Normalise;
use Carbon\Carbon;
use DateTimeInterface;
use LogicException;
use Joomla\Entity\Exceptions\JsonEncodingException;
use Joomla\Entity\Helpers\ArrayHelper;
use Joomla\Entity\Helpers\StringHelper;
use Joomla\Entity\Relations\Relation;

/**
 * Trait Attributes
 * @package Joomla\Entity\Helpers
 * @since 1.0
 */
trait Attributes
{
	/**
	 * The model's attributes. Mapped to column names.
	 * Raw data, exactly mapped to the database columns.
	 *
	 * @var array
	 */
	protected $attributesRaw = [];

	/**
	 * The model's attribute keys that can be nulls.
	 *
	 * @var array
	 */
	protected $nullables = [];

	/**
	 * The model's original attributes.
	 *
	 * @var array
	 */
	protected $original = [];

	/**
	 * The attributes that should be cast to native types. Already aliased!
	 *
	 * @var array
	 */
	protected $casts = [];

	/**
	 * The attributes that should be mutated to dates. Already aliased!
	 *
	 * @var array
	 */
	protected $dates = [];

	/**
	 * The storage format of the model's date columns. Already aliased!
	 *
	 * @var string
	 */
	protected $dateFormat;

	/**
	 * The cache of the mutated attributes for each class.
	 *
	 * @var array
	 * @TODO this is not used, we compute the getMutators every time
	 */
	protected static $getMutatorCache = [];

	/**
	 * Array with alias for "special" columns such as ordering, hits etc etc
	 *
	 * @var    array
	 * @todo add docs for this (createdAt, updatedAt)
	 */
	protected $columnAlias = [
		'createdAt' => null,
		'updatedAt' => null
	];

	/**
	 * Set a given attribute on the model.
	 * Key must be in raw format(exact column name)
	 * Value must be in raw format(exact value stored in the database)
	 *
	 * @param   string  $key   model's attribute name
	 * @param   mixed   $value model's attribute value
	 *
	 * @internal
	 * @return $this
	 */
	public function setAttributeRaw($key, $value)
	{
		$this->attributesRaw[$key] = $value;

		return $this;
	}

	/**
	 * Set a given attribute on the model.
	 *
	 * @param   string  $key          attribute name
	 * @param   mixed   $value        model's attribute value
	 *
	 * @return $this
	 *
	 * @throws AttributeNotFoundException
	 */
	public function setAttribute($key, $value)
	{
		/** First we check if the key has a column alias,
		 * if no column alias is found, the same value is returned
		 */
		$key = $this->getColumnAlias($key);

		/** Then, if mutator exists for the set operation of the key
		 * which simply lets the developers tweak the attribute as it is set on
		 * the model, such as "json_encoding" an listing of data for storage.
		 */
		if ($this->hasSetMutator($key))
		{
			$method = 'set' . Normalise::toCamelCase($key) . 'Attribute';

			return $this->{$method}($value);
		}

		/** If the aliased attribute does not exist as a column in the table and
		 * if a set mutator is not defined for this key, we throw an exception.
		 */
		if (!$this->hasField($key))
		{
			throw AttributeNotFoundException::make($this, $key, 'set');
		}

		/** If an attribute is listed as a "date", we'll convert it from a DateTime
		 * instance into the database date format from the DatabaseDriver's date format.
		 */
		if ($this->isDateAttribute($key))
		{
			$value = $this->fromDateTime($value);
		}

		if ($this->isJsonCastable($key) && ! is_null($value))
		{
			$value = $this->castAttributeAsJson($key, $value);
		}

		/** If this attribute contains a JSON ->, we'll set the proper value in the
		 * attribute's underlying array. This takes care of properly nesting an
		 * attribute in the array's value in the case of deeply nested items.
		 *
		 * JSON usage should be like this: $key='info->name'
		 */
		if (StringHelper::contains($key, '->'))
		{
			return $this->setJsonAttribute($key, $value);
		}

		$this->attributesRaw[$key] = $value;

		return $this;
	}

	/**
	 * Get an attribute from the model. (including mutations)
	 *
	 * @param   string  $key attribute name
	 *
	 * @return mixed
	 *
	 * @throws AttributeNotFoundException
	 */
	public function getAttribute($key)
	{
		if (!$key)
		{
			return null;
		}

		/** First we check if the key has a column alias,
		 * if no column alias is found, the same value is returned
		 */
		$key = $this->getColumnAlias($key);

		/** If the attribute exists in the attribute array or has a "get" mutator we will
		 * get the attribute's value. Otherwise, we will proceed as if the developers
		 * are asking for a relation's value. This covers both types of values.
		 */
		if ($this->hasField($key) || $this->hasGetMutator($key))
		{
			// Pass in the original key so we don't get the alias of an alias
			return $this->getAttributeValue($key);
		}

		/** Here we will determine if the model base class itself contains this given key
		 * since we don't want to treat any of those methods as relations because
		 * they are all intended as helper methods and none of these are relations.
		 */
		if (method_exists(self::class, $key))
		{
			return null;
		}

		return $this->getRelationValue($key);
	}

	/**
	 * Get a plain attribute from the model (not a relation).
	 *
	 * @param   string  $key attribute name
	 * @return mixed
	 */
	public function getAttributeValue($key)
	{
		/**
		 * First we check if the key has a column alias,
		 * if no column alias is found, the same value is returned
		 */
		$key = $this->getColumnAlias($key);

		/**
		 * If the attribute has a get mutator, there are two possible cases:
		 * 1. The mutator is designed for an existing attribute,
		 * case in which we have to have the column in the attriubtesRaw
		 * 2. The mutator returns a completely new attribute,
		 * case in which there is no $value to be passes to the mutator
		 */
		if ($this->hasGetMutator($key))
		{
			$mutatorValue = array_key_exists($key, $this->attributesRaw) ? $this->attributesRaw[$key] : null;

			return $this->mutateAttribute($key, $mutatorValue);
		}

		/** If the aliased attribute does not exist as a column in the table and
		 * if a get mutator is not defined for this key, we throw an exception.
		 */
		if (!$this->hasField($key))
		{
			throw AttributeNotFoundException::make($this, $key, 'get');
		}

		$value = $this->attributesRaw[$key];

		/** If the attribute exists within the cast array, we will convert it to
		 * an appropriate native PHP type dependant upon the associated value
		 * given with the key in the pair.
		 */
		if ($this->hasCast($key))
		{
			return $this->castAttribute($key, $value);
		}

		/** If the attribute is listed as a date, we will convert it to a DateTime
		 * instance on retrieval, which makes it quite convenient to work with
		 * date fields without having to create a mutator for each property.
		 */

		if (in_array($key, $this->getDates()) && ! is_null($value))
		{
			return $this->asDateTime($value);
		}

		return $value;
	}

	/**
	 * Get a relation.
	 *
	 * @param   string  $key relation name
	 *
	 * @return mixed
	 *
	 * @throws AttributeNotFoundException
	 */
	public function getRelationValue($key)
	{
		/** If the key already exists in the relations array, it just means the
		 * relation has already been loaded, so we'll just return it out of
		 * here because there is no need to query within the relations twice.
		 */
		if ($this->relationLoaded($key))
		{
			return $this->relations[$key];
		}

		/** If the "attribute" exists as a method on the model, we will just assume
		 * it is a relation and will load and return results from the query
		 * and hydrate the related model on the "relations" array.
		 *
		 * If we get at this point and the relation does not exist, implies that
		 * the attribute itself does not exist, therefore we throw an exception.
		 */

		if (method_exists($this, $key))
		{
			return $this->getRelationFromMethod($key);
		}
		else
		{
			throw AttributeNotFoundException::make($this, $key, 'get');
		}
	}

	/**
	 * Get a relation hydrated model from a method.
	 *
	 * @param   string  $method relation name
	 *
	 * @return mixed
	 *
	 * @throws \LogicException
	 */
	protected function getRelationFromMethod($method)
	{
		$relation = $this->$method();

		if (! $relation instanceof Relation)
		{
			throw new LogicException(
				sprintf(
					'%s::%s must return a relation instance.', static::class, $method
				)
			);
		}

		$results = $relation->getResults();

		$this->setRelation($method, $results);

		return $results;
	}

	/**
	 * Fill the model with an array of attributes.
	 *
	 * @param   array   $attributes   model's attributes
	 *
	 * @return $this
	 *
	 */
	public function setAttributes(array $attributes)
	{
		foreach ($attributes as $key => $value)
		{
			$this->setAttribute($key, $value);
		}

		return $this;
	}

	/**
	 * Set the array of model attributes. No checking is done.
	 *
	 * @param   array    $attributesRaw model's attributes
	 * @param   boolean  $sync          true if the data has been persisted
	 *
	 * @internal
	 * @return $this
	 */
	public function setAttributesRaw(array $attributesRaw, $sync = false)
	{
		$this->attributesRaw = $attributesRaw;

		if ($sync)
		{
			$this->syncOriginal();
		}

		return $this;
	}

	/**
	 * Get all of the current processed attributes on the model.
	 * processed = dates, cast, mutations
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		$attributesRaw = $this->getAttributesRaw();

		$attributes = [];

		foreach ($attributesRaw as $key => $value)
		{
			/** We need to convert the raw attributes to the ones exposed by
			 * the column aliases. At this point no mutated attributes will be
			 * in the $attributes array.
			 */
			$attributes[$this->getColumnAlias($key)] = $value;
		}

		$attributes = $this->addMutatedAttributes(
			$attributes, $mutatedAttributes = $this->getMutatorMethods()
		);

		return $attributes;
	}

	/**
	 * Add the date attributes to the attributes array.
	 * $attributes need to be already aliased!
	 *
	 * @param   array  $attributes model attributes - may have been already processed
	 * @return array
	 */
	protected function addDateAttributes(array $attributes)
	{
		foreach ($this->getDates() as $key)
		{
			if (! isset($attributes[$key]))
			{
				continue;
			}

			if ($attributes[$key] !== $this->db->getNullDate())
			{
				$date  = $this->asDateTime($attributes[$key]);

				$attributes[$key] = $this->serializeDate($date);
			}
		}

		return $attributes;
	}

	/**
	 * Add the mutated attributes to the attributes array.
	 * $attributes need to be already aliased!
	 *
	 * @param   array  $attributes        model attributes
	 * @param   array  $mutatedAttributes model mutated attributes
	 * @return array
	 */
	protected function addMutatedAttributes(array $attributes, array $mutatedAttributes)
	{
		foreach ($mutatedAttributes as $key)
		{
			/** @todo having the possibility to load the object partially will break the mutators
			 * when serialising the Model. Maybe we should remove it if we do not need it.
			 * Mutators can access properties which are normally loaded. Users Should check if the
			 * field is loaded before using it.
			 */
			try
			{
				if (!array_key_exists($key, $attributes))
				{
					$attributes[$key] = $this->mutateAttribute($key, null);
				}
				else
				{
					$attributes[$key] = $this->mutateAttribute($key, $attributes[$key]);
				}
			}
			catch (\Exception $e)
			{
			}
		}

		return $attributes;
	}

	/**
	 * Add the casted attributes to the attributes array.
	 * $attributes need to be already aliased!
	 *
	 * @param   array  $attributes        model attributes
	 * @param   array  $mutatedAttributes model mutated attributes
	 * @return array
	 */
	protected function addCastAttributes(array $attributes, array $mutatedAttributes)
	{
		foreach ($this->getCasts() as $key => $value)
		{
			if (! array_key_exists($key, $attributes) || in_array($key, $mutatedAttributes))
			{
				continue;
			}

			/** Here we will cast the attribute. Then, if the cast is a date or datetime cast
			 * then we will serialize the date for the array. This will convert the dates
			 * to strings based on the date format specified for these models.
			 */
			$attributes[$key] = $this->castAttribute(
				$key, $attributes[$key]
			);

			/** If the attribute cast was a date or a datetime, we will serialize the date as
			 * a string. This allows the developers to customize how dates are serialized
			 * into an array without affecting how they are persisted into the storage.
			 */
			if ($attributes[$key] && ($value === 'date' || $value === 'datetime'))
			{
				if ((int) $attributes[$key]->date <= 0)
				{
					$attributes[$key] = $this->db->getNullDate();
				}
				else
				{
					$attributes[$key] = $this->serializeDate($attributes[$key]);
				}
			}

			if ($attributes[$key] && $this->isCustomDateTimeCast($value))
			{
				$attributes[$key] = $attributes[$key]->format(explode(':', $value, 2)[1]);
			}
		}

		return $attributes;
	}

	/**
	 * Get all of the current attributes on the model in raw format.
	 *
	 * @internal
	 * @return array
	 */
	public function getAttributesRaw()
	{
		return $this->attributesRaw;
	}

	/**
	 * Sync the original attributes with the current.
	 *
	 * @internal
	 * @return $this
	 */
	public function syncOriginal()
	{
		$this->original = $this->attributesRaw;

		return $this;
	}

	/**
	 * Determine if the model or given attribute(s) have been modified.
	 *
	 * @param   array|string|null  $attributes model's attributes
	 * @return boolean
	 */
	public function isDirty($attributes = null)
	{
		return $this->hasChanges(
			$this->getDirty(), is_array($attributes) ? $attributes : func_get_args()
		);
	}

	/**
	 * Get the attributes that have been changed since last sync.
	 *
	 * @return array
	 */
	public function getDirty()
	{
		$dirty = [];

		foreach ($this->getAttributesRaw() as $key => $value)
		{
			if (!($this->original[$key] == $value))
			{
				$dirty[$key] = $value;
			}
		}

		return $dirty;
	}

	/**
	 * Cast the given attribute to JSON.
	 *
	 * @param   string  $key   attribute name
	 * @param   mixed   $value value
	 * @return string
	 */
	protected function castAttributeAsJson($key, $value)
	{
		$value = $this->asJson($value);

		if ($value === false)
		{
			throw JsonEncodingException::forAttribute(
				$this, $key, json_last_error_msg()
			);
		}

		return $value;
	}

	/**
	 * Encode the given value as JSON.
	 *
	 * @param   mixed  $value array
	 * @return string
	 */
	protected function asJson($value)
	{
		return json_encode($value);
	}

	/**
	 * Decode the given JSON back into an array or object.
	 *
	 * @param   string  $value    value
	 * @param   boolean $asObject When TRUE returned objects will be converted into associative arrays.
	 * @return mixed
	 */
	public function fromJson($value, $asObject = false)
	{
		return json_decode($value, ! $asObject);

	}

	/**
	 * Set a given JSON attribute on the model.
	 *
	 * @param   string  $key   attribute of json type key
	 * @param   mixed   $value json
	 * @return $this
	 */
	public function setJsonAttribute($key, $value)
	{
		list($key, $path) = explode('->', $key, 2);

		$this->attributesRaw[$key] = $this->asJson(
			$this->getNewJsonAttributeArray(
				$path, $key, $value
			)
		);

		return $this;
	}

	/**
	 * Get an array attribute with the given key and value set.
	 *
	 * @param   string  $path  path in Json (e.g. 'params->public')
	 * @param   string  $key   Json attribute name
	 * @param   mixed   $value new value to be set
	 * @return array
	 */
	protected function getNewJsonAttributeArray($path, $key, $value)
	{
		$array = $this->getJsonAttributeAsArray($key);

		ArrayHelper::set($array, $path, $value);

		return $array;
	}

	/**
	 * Get an array attribute or return an empty array if it is not set.
	 *
	 * @param   string  $key attribute name
	 * @return array
	 */
	protected function getJsonAttributeAsArray($key)
	{
		return isset($this->attributesRaw[$key]) ?
			$this->fromJson($this->attributesRaw[$key]) : [];
	}

	/**
	 * Return a timestamp as DateTime object with time set to 00:00:00.
	 *
	 * @param   mixed  $value value
	 * @return \Carbon\Carbon
	 */
	protected function asDate($value)
	{
		return $this->asDateTime($value)->startOfDay();
	}

	/**
	 * Return a timestamp as DateTime object.
	 *
	 * @param   mixed  $value value
	 * @return \Carbon\Carbon
	 */
	protected function asDateTime($value)
	{
		/** If this value is already a Carbon instance, we shall just return it as is.
		 * This prevents us having to re-instantiate a Carbon instance when we know
		 * it already is one, which wouldn't be fulfilled by the DateTime check.
		 */
		if ($value instanceof Carbon)
		{
			return $value;
		}

		/** If the value is already a DateTime instance, we will just skip the rest of
		 * these checks since they will be a waste of time, and hinder performance
		 * when checking the field. We will just return the DateTime right away.
		 */
		if ($value instanceof DateTimeInterface)
		{
			return new Carbon(
				$value->format('Y-m-d H:i:s.u'), $value->getTimezone()
			);
		}

		/** If this value is an integer, we will assume it is a UNIX timestamp's value
		 * and format a Carbon object from this timestamp. This allows flexibility
		 * when defining your date fields as they might be UNIX timestamps here.
		 */
		if (is_numeric($value))
		{
			return Carbon::createFromTimestamp($value);
		}

		/** If the value is in simply year, month, day format, we will instantiate the
		 * Carbon instances from that format. Again, this provides for simple date
		 * fields on the database, while still supporting Carbonized conversion.
		 */
		if ($this->isStandardDateFormat($value))
		{
			return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
		}

		/** Finally, we will just assume this date is in the format used by default on
		 * the database connection and use that format to create the Carbon object
		 * that is returned back out to the developers after we convert it here.
		 */

		return Carbon::createFromFormat(
			$this->getDateFormat(), $value
		);
	}

	/**
	 * Determine if the given value is a standard date format.
	 *
	 * @param   string  $value value
	 * @return boolean
	 */
	protected function isStandardDateFormat($value)
	{
		return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
	}

	/**
	 * Convert a DateTime to a storable string.
	 *
	 * @param   \DateTime|int  $value value
	 * @return string
	 */
	public function fromDateTime($value)
	{
		if ($value === $this->db->getNullDate())
		{
			return $value;
		}

		return (!$value) ? $this->db->getNullDate() : $this->asDateTime($value)->format(
			$this->getDateFormat()
		);
	}

	/**
	 * Return a timestamp as unix timestamp.
	 *
	 * @param   mixed  $value value
	 * @return integer
	 */
	protected function asTimestamp($value)
	{
		if ($value === $this->db->getNullDate())
		{
			return -1;
		}

		return $this->asDateTime($value)->getTimestamp();
	}

	/**
	 * Get the attributes that should be converted to dates.
	 *
	 * @return array
	 */
	public function getDates()
	{
		$defaults = [];

		if ($date = $this->getColumnAlias('createdAt'))
		{
			$defaults[] = $date;
		}

		if ($date = $this->getColumnAlias('updatedAt'))
		{
			$defaults[] = $date;
		}

		return $this->usesTimestamps()
			? array_unique(array_merge($this->dates, $defaults))
			: $this->dates;
	}

	/**
	 * Get the format for database stored dates.
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return $this->dateFormat ?: $this->db->getDateFormat();
	}

	/**
	 * Set the date format used by the model.
	 *
	 * @param   string  $format date format
	 * @return $this
	 */
	public function setDateFormat($format)
	{
		$this->dateFormat = $format;

		return $this;
	}

	/**
	 * Determine if the given attributes were changed.
	 *
	 * @param   array              $changes    changes in attributes
	 * @param   array|string|null  $attributes attributes, optional
	 * @return boolean
	 */
	protected function hasChanges($changes, $attributes = [])
	{
		/** If no specific attributes were provided, we will just see if the dirty array
		 * already contains any attributes. If it does we will just return that this
		 * count is greater than zero. Else, we need to check specific attributes.
		 */
		if (empty($attributes))
		{
			return count($changes) > 0;
		}

		/** Here we will spin through every attribute and see if this is in the array of
		 * dirty attributes. If it is, we will return true and if we make it through
		 * all of the attributes for the entire array we will return false at end.
		 */
		foreach ($attributes as $attribute)
		{
			if (array_key_exists($attribute, $changes))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine if the given attribute is a date or date castable.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	protected function isDateAttribute($key)
	{
		return in_array($key, $this->getDates()) || $this->isDateCastable($key);
	}

	/**
	 * Determine whether a value is Date / DateTime castable for inbound manipulation.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	protected function isDateCastable($key)
	{
		return $this->hasCast($key, array('date', 'datetime'));
	}

	/**
	 * Determine whether a value is JSON castable for inbound manipulation.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	protected function isJsonCastable($key)
	{
		return $this->hasCast($key, array('array', 'json', 'object', 'collection'));
	}

	/**
	 * Determine if a get mutator exists for an attribute.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	public function hasGetMutator($key)
	{
		return method_exists($this, 'get' . Normalise::toCamelCase($key) . 'Attribute');
	}

	/**
	 * Determine if a set mutator exists for an attribute.
	 *
	 * @param   string  $key ?
	 * @return boolean
	 */
	public function hasSetMutator($key)
	{
		return method_exists($this, 'set' . Normalise::toCamelCase($key) . 'Attribute');
	}

	/**
	 * Get the value of an attribute using its mutator.
	 *
	 * @param   string $key   model's attribute name
	 * @param   mixed  $value value to be mutated
	 * @return mixed
	 */
	protected function mutateAttribute($key, $value)
	{
		return $this->{'get' . Normalise::toCamelCase($key) . 'Attribute'}($value);
	}

	/**
	 * Get the mutated attributes for a given instance.
	 *
	 * @return array
	 */
	public function getMutatedAttributes()
	{
		$class = static::class;

		if (! isset(static::$getMutatorCache[$class]))
		{
			static::cacheMutatedAttributes($class);
		}

		return static::$getMutatorCache[$class];
	}

	/**
	 * Extract and cache all the mutated attributes of a class.
	 *
	 * @param   string  $class Model class, used as key for the getMutatorMethods cache
	 * @return void
	 */
	public static function cacheMutatedAttributes($class)
	{
		$mutatedAttributes = static::getMutatorMethods($class);

		$cache = [];

		foreach ($mutatedAttributes as $mutatedAttribute)
		{
			$cache[] = lcfirst(Normalise::toCamelCase($mutatedAttribute));
		}

		static::$getMutatorCache[$class] = $cache;
	}

	/**
	 * Get all of the attribute mutator methods.
	 *
	 * @return array
	 */
	protected static function getMutatorMethods()
	{
		$class = static::class;

		preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches);

		$result = [];

		foreach ($matches[1] as $match)
		{
			$result[] = lcfirst(Normalise::toCamelCase($match));
		}

		return $result;
	}

	// TODO add cached Set Mutators, still in debate

	/**
	 * Determine whether an attribute should be cast to a native type.
	 *
	 * @param   string            $key   attribute name
	 * @param   array|string|null $types types of cast to be checked for
	 * @return boolean
	 */
	public function hasCast($key, $types = null)
	{
		if (array_key_exists($key, $this->getCasts()))
		{
			return $types ? in_array($this->getCastType($key), (array) $types, true) : true;
		}

		return false;
	}

	/**
	 * Get the casts array.
	 *
	 * @return array
	 */
	public function getCasts()
	{
		if ($this->isIncrementing())
		{
			return array_merge(array($this->getPrimaryKey() => $this->getPrimaryKeyType()), $this->casts);
		}

		return $this->casts;
	}

	/**
	 * Cast an attribute to a native PHP type.
	 *
	 * @param   string $key   model's attribute name
	 * @param   mixed  $value value that will be casted
	 * @return mixed
	 */
	protected function castAttribute($key, $value)
	{
		if (is_null($value))
		{
			return $value;
		}

		switch ($this->getCastType($key))
		{
			case 'int':
			case 'integer':
				return (int) $value;
			case 'real':
			case 'float':
			case 'double':
				return (float) $value;
			case 'string':
				return (string) $value;
			case 'bool':
			case 'boolean':
				return (bool) $value;
			case 'object':
				return $this->fromJson($value, true);
			case 'array':
			case 'json':
				return $this->fromJson($value);
			case 'date':
				return $this->asDate($value);
			case 'datetime':
			case 'custom_datetime':
				return $this->asDateTime($value);
			case 'timestamp':
				return $this->asTimestamp($value);
			default:
				return $value;
		}
	}

	/**
	 * Get the type of cast for a model attribute.
	 *
	 * @param   string $key model's attribute name
	 * @return string
	 */
	protected function getCastType($key)
	{
		if ($this->isCustomDateTimeCast($this->getCasts()[$key]))
		{
			return 'custom_datetime';
		}

		return trim(strtolower($this->getCasts()[$key]));
	}

	/**
	 * Determine if the cast type is a custom date time cast.
	 *
	 * @param   string  $cast ?
	 * @return boolean
	 */
	protected function isCustomDateTimeCast($cast)
	{
		return strncmp($cast, 'date:', 5) === 0 ||
			strncmp($cast, 'datetime:', 9) === 0;
	}


	/**
	 * @param   string $key attribute name to be check if nullable
	 *
	 * @return boolean
	 */
	public function isNullable($key)
	{
		$key = $this->getColumnAlias($key);

		return array_key_exists($key, $this->nullables);
	}

	/**
	 * Check if the field exist in the model
	 *
	 * @param   string  $key key to be checked
	 *
	 * @return boolean
	 */
	public function hasField($key)
	{
		$key = $this->getColumnAlias($key);

		return in_array($key, $this->getDefaultFields());
	}
}
