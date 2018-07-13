<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity;

use ArrayAccess;
use BadMethodCallException;
use Joomla\Entity\Helpers\ArrayHelper;
use JsonSerializable;
use Joomla\Database\DatabaseDriver;
use Joomla\String\Inflector;
use Joomla\Entity\Exceptions\JsonEncodingException;
use Joomla\Entity\Helpers\StringHelper;
use Joomla\String\Normalise;

/**
 * Base Entity class for items
 *
 * @method find()       find(mixed $id, array $columns = ['*'])
 * @method findLast()   findLast(array $columns = ['*'])
 * @method first()      first(array $columns = ['*'])
 * @method exists()     exists(mixed $id)
 * @method select()     select(array $columns)
 * @method where()      where(array $conditions, string $glue = 'AND')
 * @method get()        get(array $columns = ['*'])
 *
 * @package Joomla\Entity
 * @since 1.0
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
	use ModelHelpers\Attributes;
	use ModelHelpers\Timestamps;
	use ModelHelpers\Relations;
	use ModelHelpers\Serialization;

	/**
	 * The connection name for the model.
	 *
	 * @var DatabaseDriver
	 */
	protected $db;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * The "type" of the auto-incrementing ID.
	 *
	 * @var string
	 */
	protected $primaryKeyType = 'int';

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var boolean
	 */
	public $incrementing = true;

	/**
	 * Indicates if the model exists.
	 *
	 * @var boolean
	 */
	public $exists = false;

	/**
	 * The relations to eager load on every query.
	 *
	 * @var array
	 */
	protected $with = [];

	/**
	 * The methods that should be returned from the Query.
	 *
	 * @var array
	 */
	protected $passThrough = array(
		'find', 'findLast', 'first', 'exists', 'select', 'where', 'whereIn', 'get'
	);

	/**
	 * The cache of the columns attributes for each table.
	 *
	 * @var array
	 */
	public static $fieldsCache = [];

	/**
	 * Create a new Joomla entity model instance.
	 *
	 * @param   DatabaseDriver $db          database driver instance
	 * @param   array          $attributes  pre loads any attributed for the model (user friendly format)

	 */
	public function __construct(DatabaseDriver $db, array $attributes = [])
	{
		$this->db = $db;

		$this->originalHidden = $this->hidden;

		if (!isset($this->table))
		{
			$this->setDefaultTable();
		}

		$this->setAttributes($attributes);

		$this->syncOriginal();

		$this->casts[$this->primaryKey] = $this->primaryKeyType;
	}

	/**
	 * @return DatabaseDriver
	 */
	public function getDb()
	{
		return $this->db;
	}

	/**
	 * @return string
	 */
	public function getTableName()
	{
		return $this->table;
	}

	/**
	 * @return string
	 */
	public function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	/**
	 * @return string
	 */
	public function getQualifiedPrimaryKey()
	{
		return $this->qualifyColumn($this->primaryKey);
	}

	/**
	 * Gets the primary key value
	 * although it should not have mutators or aliases, we use getAttributeValue for consistency.
	 *
	 * @return string
	 */
	public function getPrimaryKeyValue()
	{
		return $this->getAttributeValue($this->primaryKey);
	}

	/**
	 * @param   string $value model's primary key
	 * @return void
	 */
	public function setPrimaryKeyValue($value)
	{
		$this->setAttribute($this->primaryKey, $value);
	}

	/**
	 * @return string
	 */
	public function getPrimaryKeyType(): string
	{
		return $this->primaryKeyType;
	}

	/**
	 * @param   string $primaryKeyType primary key type
	 * @return void
	 */
	public function setPrimaryKeyType(string $primaryKeyType)
	{
		$this->primaryKeyType = $primaryKeyType;
	}

	/**
	 * Qualify the given column name by the model's table.
	 * If table alias is specified, but does not contain the '#__' keyword,
	 * we add it manually because it is needed for prefix replacement in the Query
	 *
	 * @param   string  $column column name to by qualifies
	 * @return string
	 */
	public function qualifyColumn($column)
	{
		if (StringHelper::contains($column, '.'))
		{
			if (!StringHelper::startWith($column, '#__'))
			{
				$column = "#__" . $column;
			}

			return $column;
		}

		return $this->getTableName() . '.' . $column;
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param   string  $key model's attribute name
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param   string  $key   model's attribute name
	 * @param   mixed   $value model's attribute value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * @return boolean
	 */
	public function isIncrementing()
	{
		return $this->incrementing;
	}

	/**
	 * Update the model in the database.
	 *
	 * @param   array  $attributes model's attributes
	 *
	 * @return  boolean
	 */
	public function update(array $attributes = [])
	{
		if (!$this->exists)
		{
			 return false;
		}

		return $this->setAttributes($attributes)->persist();
	}

	/**
	 * Delete the model from the database.
	 *
	 * @param   mixed  $pk  The primary key to delete (optional - deletes the current model)
	 *
	 * @return  boolean|null
	 */
	public function delete($pk = null)
	{
		if (!is_null($pk))
		{
			$this->setPrimaryKeyValue($pk);
		}
		else
		{
			if (!$this->exists)
			{
				return false;
			}
		}

		/** Here, we'll touch the owning models, ensuring relations consistency.
		 * Only after that we will delete the model instance.
		 */
		$this->touchOwners();

		$query = $this->newQuery();

		return $this->performDelete($query);
	}

	/**
	 * Save the model to the database.
	 *
	 * @param   boolean  $nulls   True to insert or update null fields or false to ignore them.
	 *
	 * @return boolean
	 */
	public function persist($nulls = false)
	{
		$query = $this->newQuery();

		// First we update the timestamps on the model if needed.
		if ($this->usesTimestamps())
		{
			$this->updateTimestamps();
		}

		/** If the model already exists in the database we can just update our record
		 * that is already in this database using the current IDs in this "where"
		 * clause to only update this model. Otherwise, we'll just insert them.
		 */
		if ($this->exists)
		{
			$saved = $this->isDirty() ?
				$this->performUpdate($query, $nulls) : true;
		}

		/** If the model is brand new, we'll insert it into our database and set the
		 * ID attribute on the model to the value of the newly inserted row's ID
		 * which is typically an auto-increment value managed by the database.
		 */
		else
		{
			$saved = $this->performInsert($query, $nulls);
		}

		/** If the model is successfully saved, we need to sync the original array
		 * with the new persisted changes, in case there are further actions on the current model.
		 */
		if ($saved)
		{
			$this->syncOriginal();
		}

		return $saved;
	}

	/**
	 * Perform a model insert operation.
	 *
	 * @param   Query    $query   instance of query
	 * @param   boolean  $nulls   True to insert null fields or false to ignore them.
	 * @return boolean
	 */
	protected function performInsert(Query $query, $nulls = false)
	{
		if (empty($this->attributesRaw))
		{
			 return true;
		}

		$success = $query->insert($nulls);

		if ($success)
		{
			 $this->exists = true;
		}

		return $success;
	}

	/**
	 * Perform a model insert operation.
	 *
	 * @param   Query    $query   istance of query
	 * @param   boolean  $nulls   True to update null fields or false to ignore them.
	 * @return boolean
	 */
	protected function performUpdate($query, $nulls = false)
	{
		if (empty($this->attributesRaw))
		{
			 return true;
		}

		$success = $query->update($nulls);

		return $success;
	}

	/**
	 * Perform a model insert operation.
	 *
	 * @param   Query  $query istance of query
	 * @return boolean
	 */
	protected function performDelete($query)
	{
		$success = $query->delete();

		if ($success)
		{
			$this->exists = false;
		}

		return $success;
	}


	/**
	 * Get a new query builder for the model's table.
	 *
	 * @return Query
	 */
	public function newQuery()
	{
		$query = new Query($this->db->getQuery(true), $this->db, $this);

		// We are adding the eager loading constrains in every query.
		return $query->with($this->with);
	}

	/**
	 * Handle dynamic method calls into the model.
	 *
	 * @param   string  $method     method called dynamically
	 * @param   array   $parameters parameters to be passed to the dynamic called method
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (!in_array($method, $this->passThrough))
		{
			throw new BadMethodCallException(
				sprintf(
					'Method %s does not exist or is not exposed from \Joomla\Entity\Query.',
					$method
				)
			);
		}

		foreach ($parameters as &$param)
		{
			/** @todo this quite hacky, alternative is to implement this in all query methods
			 * or implement wrappers for all query methods in the Model to avoid
			 * calling the model from the Query every time.
			 */
			if (is_array($param) && is_string($param[0]))
			{
				$param = $this->convertAliasedToRaw($param);
			}
		}

		return $this->newQuery()->$method(...$parameters);
	}

	/**
	 * Create a new model instance that is existing.
	 *
	 * @param   array $attributesRaw attributes in raw format to be set on the new model instance
	 *
	 * @internal
	 * @return static
	 */
	public function newFromBuilder($attributesRaw = [])
	{
		$model = $this->newInstance($this->db, [], true);

		$model->setAttributesRaw((array) $attributesRaw, true);

		return $model;
	}

	/**
	 * Create a new instance of the given model.
	 *
	 * @param   DatabaseDriver $db         database driver
	 * @param   array          $attributes attributes to be set on the new model instance
	 * @param   bool           $exists     true if the model is already in the database
	 * @return static
	 */
	public function newInstance(DatabaseDriver $db, $attributes = [], $exists = false)
	{
		/** This method just provides a convenient way for us to generate fresh model
		 * instances of this current model. It is particularly useful during the
		 * hydration of new objects via the Query instances.
		 */
		$model = new static($db, (array) $attributes);

		$model->exists = $exists;

		$model->db = $this->getDb();

		return $model;
	}

	/**
	 * Sets the default value of the table name based on Model class name.
	 * Pluralise the last word in the Model Class name(CamelCase).
	 * Adds underscode between words in table name.
	 *
	 * Examples:
	 * User -> users
	 * UserProfile -> user_profiles
	 *
	 * @return void
	 */
	private function setDefaultTable()
	{
		$className = basename(str_replace('\\', '/', get_class($this)));

		$tableArray = explode(" ", strtolower(Normalise::fromCamelCase($className)));

		$plural = Inflector::pluralize(end($tableArray));

		$tableArray[key($tableArray)] = $plural;

		$table = Normalise::toUnderscoreSeparated(implode(" ", $tableArray));

		$this->table = '#__' . $table;
	}

	/**
	 * Determine if the given attribute exists.
	 *
	 * @param   mixed  $offset key position in array
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return ! is_null($this->getAttribute($offset));
	}

	/**
	 * Get the value for a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->getAttribute($offset);
	}

	/**
	 * Set the value for a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @param   mixed  $value  value to be set
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->setAttribute($offset, $value);
	}

	/**
	 * Unset the value for a given offset.
	 *
	 * @param   mixed  $offset key position in array
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->attributesRaw[$offset], $this->relations[$offset]);
	}

	/**
	 * Determine if an attribute or relation exists on the model.
	 *
	 * @param   string  $key attribute name
	 * @return boolean
	 */
	public function __isset($key)
	{
		return $this->offsetExists($key);
	}

	/**
	 * Unset an attribute on the model.
	 *
	 * @param   string  $key attribute name
	 * @return void
	 */
	public function __unset($key)
	{
		$this->offsetUnset($key);
	}

	/**
	 * Determine if two models have the same ID, belong to the same table and use the same DatabaseDriver.
	 *
	 * @param   Model|null  $model model to be compared with
	 * @return boolean
	 */
	public function is($model)
	{
		return ! is_null($model) &&
			$this->getPrimaryKeyValue() === $model->getPrimaryKeyValue() &&
			$this->getTableName() === $model->getTableName() &&
			$this->getDb() === $model->getDb();
	}

	/**
	 * Increment a column's value by a given amount.
	 * $lazy must be set to true when further actions will be taken on to the model before persisting is desired.
	 *
	 * @param   string     $column column to be incremented
	 * @param   float|int  $amount amount to be added to the column value
	 * @param   boolean    $lazy   lazy increment if true
	 * @return integer
	 */
	public function increment($column, $amount = 1, $lazy = false)
	{
		return $this->incrementOrDecrement($column, $amount, $lazy, 'increment');
	}

	/**
	 * Decrement a column's value by a given amount.
	 *
	 * @param   string     $column column to be decremented
	 * @param   float|int  $amount amount to be subtracted from the column value
	 * @param   boolean    $lazy   lazy increment if true
	 * @return integer
	 */
	public function decrement($column, $amount = 1, $lazy = false)
	{
		return $this->incrementOrDecrement($column, $amount, $lazy, 'decrement');
	}

	/**
	 * Run the increment or decrement method on the model.
	 *
	 * @param   string     $column column altered in the operation
	 * @param   float|int  $amount amount value
	 * @param   float|int  $lazy   lazy operation if true
	 * @param   string     $method specify increment or decrement operation
	 * @return integer|Model
	 */
	protected function incrementOrDecrement($column, $amount, $lazy, $method)
	{
		$column = $this->getColumnAlias($column);

		$amount = $method == 'increment' ? $amount : $amount * -1;

		$amount = $amount + $this->$column;

		$this->setAttribute($column, $amount);

		if ($lazy)
		{
			return $this;
		}

		if ($this->exists)
		{
			return $this->update();
		}

		return $this->persist();
	}

	/**
	 * Method to return the real name of a "special" column such as ordering, hits, published
	 * etc etc. In this way you are free to follow your db naming convention and use the
	 * built in \Joomla functions.
	 *
	 * @param   string  $column  Name of the "special" column (ie ordering, hits)
	 *
	 * @return  string  The string that identify the special
	 */
	public function getColumnAlias($column)
	{
		// Get the column data if set
		if (isset($this->columnAlias[$column]))
		{
			$return = $this->columnAlias[$column];
		}
		else
		{
			$return = $column;
		}

		if ($return === '*')
		{
			return $return;
		}

		// Sanitize the name
		$return = preg_replace('#[^`A-Z0-9_]#i', '', $return);

		return $return;
	}

	/**
	 * Method to register a column alias for a "special" column.
	 *
	 * @param   string  $column       The "special" column (ie ordering)
	 * @param   string  $columnAlias  The real column name (ie foo_ordering)
	 *
	 * @return  void
	 */
	public function setColumnAlias($column, $columnAlias)
	{
		// Santize the column name alias
		$column = strtolower($column);
		$column = preg_replace('#[^A-Z0-9_]#i', '', $column);

		// Set the column alias internally
		$this->columnAlias[$column] = $columnAlias;
	}


	/**
	 * Method to convert an array of  columns to their aliased version if it exists.
	 * UnAliased version can be used by the developers when using the model,
	 * but it will not be recognised by the database.
	 * To be used internally everywhere we interact with the Query.
	 *
	 * @param   array $array array of column names or attributes
	 *
	 * @return array
	 * @internal
	 */
	public function convertAliasedToRaw($array)
	{
		$aliased = [];

		if (ArrayHelper::isAssoc($array))
		{
			foreach ($array as $key => $value)
			{
				$aliased[$this->getColumnAlias($key)] = $value;
			}
		}
		else
		{
			foreach ($array as $column)
			{
				$aliased[] = $this->getColumnAlias($column);
			}
		}

		return $aliased;
	}

	/**
	 * Begin querying a model with eager loading.
	 *
	 * @param   array|string  $relations relations that should be eager loaded
	 * @return Query|static
	 */
	public function with($relations)
	{
		return $this->newQuery()->with(
			is_string($relations) ? func_get_args() : $relations
		);
	}

	/**
	 * Eager load relations on the model.
	 *
	 * @param   array|string  $relations relations that should be eager loaded
	 * @return $this
	 */
	public function eagerLoad($relations)
	{
		$query = $this->newQuery()->with(
			is_string($relations) ? func_get_args() : $relations
		);

		$query->eagerLoadRelations(array($this));

		return $this;
	}

	/**
	 * Eager load relations on the model if they are not already eager loaded.
	 *
	 * @param   array|string  $relations relations that should be eager loaded
	 * @return $this
	 */
	public function loadMissing($relations)
	{
		$relations = is_string($relations) ? func_get_args() : $relations;

		return $this->eagerLoad(
			array_filter($relations,
				function ($relation)
				{
					return ! $this->relationLoaded($relation);
				}
			)
		);
	}

	/**
	 * Get the columns from database table.
	 *
	 * @param   boolean         $reload  flag to reload cache
	 *
	 * @return  mixed  An array of the field names, or false if an error occurs.
	 *
	 * @throws  \UnexpectedValueException
	 */
	public function getDefaultFields($reload = false)
	{
		// Lookup the fields for this table only once.
		if (!isset(static::$fieldsCache[$this->getTableName()]) || $reload)
		{
			$fields = $this->db->getTableColumns($this->getTableName());

			if (empty($fields))
			{
				throw new \UnexpectedValueException(sprintf('No columns found for %s table', $this->getTableName()));
			}

			$fields = array_map(
				function ($field)
				{
					return null;
				},
				$fields
			);

			static::$fieldsCache[$this->getTableName()] = array_keys($fields);
		}

		return static::$fieldsCache[$this->getTableName()];
	}
}
