<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\ModelHelpers;

use Joomla\Entity\Query;
use Joomla\Entity\Model;
use Joomla\Entity\Helpers\Collection;
use Joomla\Entity\Relations\HasOne;
use Joomla\Entity\Relations\HasMany;
use Joomla\Entity\Relations\BelongsTo;
use Joomla\String\Inflector;
use Joomla\String\Normalise;

/**
 * Trait Relations
 * @package Joomla\Entity\Helpers
 * @since 1.0
 */
trait Relations
{
	/**
	 * The loaded relations for the model.
	 *
	 * @var array
	 */
	protected $relations = [];

	/**
	 * The relations that should be touched on save.
	 *
	 * @var array
	 */
	protected $touches = [];

	/**
	 * Determine if the model touches a given relation.
	 *
	 * @param   string  $relation relation name
	 * @return boolean
	 */
	public function touches($relation)
	{
		return in_array($relation, $this->touches);
	}

	/**
	 * Touch the owning relations of the model.
	 *
	 * @return void
	 */
	public function touchOwners()
	{
		foreach ($this->touches as $relation)
		{
			$this->$relation()->touch();

			if ($this->$relation instanceof self)
			{
				$this->$relation->touchOwners();
			}
			elseif ($this->$relation instanceof Collection)
			{
				$this->$relation->each(
					function (Model $relation)
					{
						$relation->touchOwners();
					}
				);
			}
		}
	}

	/**
	 * Create a new model instance for a related model.
	 *
	 * @param   string  $class Model class name
	 * @return mixed
	 */
	protected function newRelatedInstance($class)
	{
		return new $class($this->getDb());
	}

	/**
	 * Get all the loaded relations for the instance.
	 *
	 * @return array
	 */
	public function getRelations()
	{
		return $this->relations;
	}

	/**
	 * Get a specified relation.
	 *
	 * @param   string  $relation relation name
	 * @return mixed
	 */
	public function getRelation($relation)
	{
		return $this->relations[$relation];
	}

	/**
	 * Determine if the given relation is loaded.
	 *
	 * @param   string  $key relation name
	 * @return boolean
	 */
	public function relationLoaded($key)
	{
		return array_key_exists($key, $this->relations);
	}

	/**
	 * Set the specific relation in the model.
	 *
	 * @param   string $relation relation name
	 * @param   mixed  $value    Relation instance
	 * @return $this
	 */
	public function setRelation($relation, $value)
	{
		$this->relations[$relation] = $value;

		return $this;
	}

	/**
	 * Get the relations that are touched on save.
	 *
	 * @return array
	 */
	public function getTouchedRelations()
	{
		return $this->touches;
	}

	/**
	 * Define a one-to-one relation.
	 *
	 * @param   string  $related    related Model
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $localKey   local primary key name
	 * @return \Joomla\Entity\Relations\HasOne
	 */
	public function hasOne($related, $foreignKey = null, $localKey = null)
	{
		$instance = $this->newRelatedInstance($related);

		$foreignKey = $foreignKey ?: Inflector::singularize($this->table) . '_id';

		$localKey = $localKey ?: $this->getPrimaryKey();

		return $this->newHasOne($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
	}

	/**
	 * Instantiate a new HasOne relation.
	 *
	 * @param   Query   $query      just a query instance
	 * @param   Model   $parent     $this model instance
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $localKey   local primary key name
	 * @return HasOne
	 */
	protected function newHasOne($query, $parent, $foreignKey, $localKey)
	{
		return new HasOne($query, $parent, $foreignKey, $localKey);
	}

	/**
	 * Define a one-to-many relation.
	 *
	 * @param   string  $related    related Model
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $localKey   local primary key name
	 * @return \Joomla\Entity\Relations\HasMany
	 */
	public function hasMany($related, $foreignKey = null, $localKey = null)
	{
		$instance = $this->newRelatedInstance($related);

		$foreignKey = $foreignKey ?: Inflector::singularize($this->table) . '_id';

		$localKey = $localKey ?: $this->getPrimaryKey();

		return $this->newHasMany($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
	}

	/**
	 * Instantiate a new HasMany relation.
	 *
	 * @param   Query   $query      just a query instance
	 * @param   Model   $parent     $this model instance
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $localKey   local primary key name
	 * @return \Joomla\Entity\Relations\HasMany
	 */
	protected function newHasMany($query, $parent, $foreignKey, $localKey)
	{
		return new HasMany($query, $parent, $foreignKey, $localKey);
	}

	/**
	 * Define an inverse one-to-one or many relation.
	 *
	 * @param   string  $related    related Model
	 * @param   string  $relation   relation name, must be the same as the caller function
	 * @param   string  $foreignKey foreign key name in current mode
	 * @param   string  $ownerKey   the associated key on the parent model.
	 * @return \Joomla\Entity\Relations\BelongsTo
	 */
	public function belongsTo($related, $relation, $foreignKey = null, $ownerKey = null)
	{
		$instance = $this->newRelatedInstance($related);

		$foreignKey = $foreignKey ?: Normalise::toUnderscoreSeparated($relation) . '_' . $instance->getPrimaryKey();

		$ownerKey = $ownerKey ?: $instance->getPrimaryKey();

		return $this->newBelongsTo(
			$instance->newQuery(), $this, $foreignKey, $ownerKey, $relation
		);
	}

	/**
	 * Instantiate a new BelongsTo relation.
	 *
	 * @param   Query   $query      Query instance
	 * @param   Model   $child      child Model instance
	 * @param   string  $foreignKey foreign key name
	 * @param   string  $ownerKey   the associated key on the parent model.
	 * @param   string  $relation   relation name
	 * @return \Joomla\Entity\Relations\BelongsTo
	 */
	protected function newBelongsTo(Query $query, Model $child, $foreignKey, $ownerKey, $relation)
	{
		return new BelongsTo($query, $child, $foreignKey, $ownerKey, $relation);
	}
}
