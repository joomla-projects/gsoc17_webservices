<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Relations;

use Closure;
use Joomla\Entity\Model;
use Joomla\Entity\Query;
use Joomla\Entity\Helpers\Collection;

/**
 * Relation Abstract Class
 * @package Joomla\Entity\Relations
 * @since 1.0
 */
abstract class Relation
{
	/**
	 * The Query instance.
	 *
	 * @var Query
	 */
	protected $query;

	/**
	 * The parent model instance.
	 *
	 * @var \Joomla\Entity\Model
	 */
	protected $parent;

	/**
	 * The related model instance.
	 *
	 * @var \Joomla\Entity\Model
	 */
	protected $related;

	/**
	 * Indicates if the relation is adding constraints.
	 *
	 * @var boolean
	 */
	protected static $constraints = true;

	/**
	 * An array to map class names to their morph names in database.
	 *
	 * @var array
	 */
	public static $morphMap = [];

	/**
	 * Create a new relation instance.
	 *
	 * @param   Query   $query  query instance
	 * @param   Model   $parent model instance
	 */
	public function __construct(Query $query, Model $parent)
	{
		$this->query = $query;
		$this->parent = $parent;
		$this->related = $query->getModel();

		$this->addConstraints();
	}

	/**
	 * Run a callback with constraints disabled on the relation.
	 *
	 * @param   Closure  $callback callback function
	 * @return mixed
	 */
	public static function noConstraints(Closure $callback)
	{
		$previous = static::$constraints;

		static::$constraints = false;

		/** When resetting the relation where clause, we want to shift the first element
		 * off of the bindings, leaving only the constraints that the developers put
		 * as "extra" on the relations, and not original relation constraints.
		 */
		try
		{
			return call_user_func($callback);
		} finally
		{
			static::$constraints = $previous;
		}
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	abstract public function addConstraints();

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param   array  $models eager load the relation on the specified models
	 * @return void
	 */
	abstract public function addEagerConstraints(array $models);

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param   array   $models   the array of Model instances
	 * @param   string  $relation relation name
	 * @return array
	 */
	abstract public function initRelation(array $models, $relation);

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param   array       $models   array of model instances
	 * @param   Collection  $results  Collection of results (Relation Instances)
	 * @param   string      $relation relation name
	 * @return array
	 */
	abstract public function match(array $models, Collection $results, $relation);

	/**
	 * Get the results of the relation.
	 *
	 * @return mixed
	 */
	abstract public function getResults();

	/**
	 * Get the relation for eager loading.
	 *
	 * @return Collection
	 */
	public function getEager()
	{
		return $this->get();
	}

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @param   array  $columns columns to be selected
	 * @return Collection
	 */
	public function get($columns = ['*'])
	{
		return $this->query->get($columns);
	}

	/**
	 * Get the underlying query for the relation.
	 *
	 * @return \Joomla\Entity\Query;
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Get the parent model of the relation.
	 *
	 * @return Model
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Get the fully qualified parent key name.
	 *
	 * @return string
	 */
	public function getQualifiedParentKey()
	{
		return $this->parent->getQualifiedPrimaryKey();
	}

	/**
	 * Get the related model of the relation.
	 *
	 * @return Model
	 */
	public function getRelated()
	{
		return $this->related;
	}

	/**
	 * Get all of the keys for an array of models.
	 * The default key is the primary key
	 *
	 * @param   array   $models the array of Model instances
	 * @param   string  $key    the key name
	 * @return array
	 */
	protected function getKeys(array $models, $key = null)
	{
		$keys = [];

		foreach ($models as $model)
		{
			$keys[] = $key ? $model->getAttribute($key) : $model->getPrimaryKeyValue();
		}

		$keys = array_unique($keys);
		sort($keys);

		return $keys;
	}

	/**
	 * Handle dynamic method calls to the relation.
	 *
	 * @param   string  $method     method called dynamically
	 * @param   array   $parameters parameters to be passed to the dynamic called method
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$result = $this->query->{$method}(...$parameters);

		return $result;
	}

	/**
	 * Force a clone of the underlying query builder when cloning.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->query = clone $this->query;
	}
}
