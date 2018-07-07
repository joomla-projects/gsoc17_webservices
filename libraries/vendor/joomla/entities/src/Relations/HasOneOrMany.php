<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Relations;

use Joomla\Entity\Model;
use Joomla\Entity\Query;
use Joomla\Entity\Helpers\Collection;

/**
 * Class HasOneOrMany
 * @package Joomla\Entity\Relations
 * @since   1.0
 */
abstract class HasOneOrMany extends Relation
{
	/**
	 * The foreign key of the parent model.
	 *
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * The local key of the parent model.
	 *
	 * @var string
	 */
	protected $localKey;

	/**
	 * Create a new has one or many relation instance.
	 *
	 * @param   Query   $query      Query instance
	 * @param   Model   $parent     parent Model instance
	 * @param   string  $foreignKey foreign key name
	 * @param   string  $localKey   current instance primary key name
	 */
	public function __construct(Query $query, Model $parent, $foreignKey, $localKey)
	{
		$this->localKey = $localKey;
		$this->foreignKey = $foreignKey;

		parent::__construct($query, $parent);
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		if (static::$constraints)
		{
			$this->query->where($this->foreignKey . ' = ' . $this->getParentKeyValue());

			$this->query->where($this->foreignKey . ' NOT NULL');
		}
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param   array  $models array of model instances to add the eager constraints
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		$this->query->whereIn(
			$this->foreignKey, $this->getKeys($models, $this->localKey)
		);
	}

	/**
	 * Match the eagerly loaded results to their single parents.
	 *
	 * @param   array       $models   array of model instances
	 * @param   Collection  $results  Collection of results
	 * @param   string      $relation relation name
	 * @return array
	 */
	public function matchOne(array $models, Collection $results, $relation)
	{
		return $this->matchOneOrMany($models, $results, $relation, 'one');
	}

	/**
	 * Match the eagerly loaded results to their many parents.
	 *
	 * @param   array       $models   array of model instances
	 * @param   Collection  $results  Collection of results
	 * @param   string      $relation relation name
	 * @return array
	 */
	public function matchMany(array $models, Collection $results, $relation)
	{
		return $this->matchOneOrMany($models, $results, $relation, 'many');
	}

	/**
	 * Match the eagerly loaded results to their many parents.
	 *
	 * @param   array       $models   array of model instances
	 * @param   Collection  $results  Collection of results
	 * @param   string      $relation relation name
	 * @param   string      $type     'one' or 'many'
	 * @return array
	 */
	protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
	{
		$dictionary = $this->buildDictionary($results);

		/** Once we have the dictionary we can simply spin through the parent models to
		 * link them up with their children using the keyed dictionary to make the
		 * matching very convenient and easy work. Then we'll just return them.
		 */
		foreach ($models as $model)
		{
			if (isset($dictionary[$key = $model->getAttribute($this->localKey)]))
			{
				$value = $type == 'one' ? reset($dictionary[$key]) : new Collection($dictionary[$key]);

				$model->setRelation($relation, $value);
			}
		}

		return $models;
	}

	/**
	 * Build model dictionary keyed by the relation's foreign key value.
	 *
	 * @param   Collection  $results Collection of results (Relation Instances)
	 * @return array
	 */
	protected function buildDictionary(Collection $results)
	{
		$foreign = $this->getForeignKey();

		$dictionary = [];

		foreach ($results as $key => $value)
		{
			if (! isset($dictionary[$value->{$foreign}]))
			{
				$dictionary[$value->{$foreign}] = [];
			}

			$dictionary[$value->{$foreign}][] = $value;
		}

		return $dictionary;
	}

	/**
	 * Find a model by its primary key or return new instance of the related model.
	 *
	 * @param   mixed  $id      primary key value
	 * @param   array  $columns column names
	 * @return Collection|Model
	 */
	public function findOrNew($id, $columns = ['*'])
	{
		$columns = $this->convertAliasedToRaw($columns);

		if (is_null($instance = $this->find($id, $columns)))
		{
			$instance = $this->related->newInstance($this->getDb());

			$this->setForeignAttributesForCreate($instance);
		}

		return $instance;
	}

	/**
	 * Get the first related model record matching the attributes or instantiate it.
	 *
	 * @param   array  $attributes Model attributes
	 * @param   array  $values     new attributes values to be set
	 * @return Model
	 */
	public function firstOrNew(array $attributes, array $values = [])
	{
		$attributes = $this->convertAliasedToRaw($attributes);

		if (is_null($instance = $this->where($attributes)->first()))
		{
			$instance = $this->related->newInstance($this->getDb(), $attributes + $values);

			$this->setForeignAttributesForCreate($instance);
		}

		return $instance;
	}

	/**
	 * Get the first related record matching the attributes or create it.
	 *
	 * @param   array  $attributes Model  attributes
	 * @param   array  $values     new attributes values to be set
	 * @return Model
	 */
	public function firstOrCreate(array $attributes, array $values = [])
	{
		$attributes = $this->convertAliasedToRaw($attributes);

		if (is_null($instance = $this->where($attributes)->first()))
		{
			$instance = $this->create($attributes + $values);
		}

		return $instance;
	}

	/**
	 * Create or update a related record matching the attributes, and fill it with values.
	 *
	 * @param   array  $attributes Model attributes (can be aliased)
	 * @param   array  $values     new attributes values to be set
	 * @return Model
	 */
	public function updateOrCreate(array $attributes, array $values = [])
	{
		$instance = $this->firstOrNew($attributes);

		$instance->setAttributes(array_combine($attributes, $values));

		$instance->save();

		return $instance;
	}

	/**
	 * Attach a model instance to the parent model.
	 *
	 * @param   Model  $model Model to be saved with the attached relation foreign key
	 * @return Model|false
	 */
	public function save(Model $model)
	{
		$this->setForeignAttributesForCreate($model);

		return $model->save() ? $model : false;
	}

	/**
	 * Attach a collection of models to the parent instance.
	 *
	 * @param   \Traversable|array  $models Model instances to be saved with attached foreign key
	 * @return \Traversable|array
	 */
	public function saveMany($models)
	{
		foreach ($models as $model)
		{
			$this->save($model);
		}

		return $models;
	}

	/**
	 * Create a new instance of the related model.
	 *
	 * @param   array  $attributes attributes (can be aliased)
	 * @return Model
	 */
	public function create($attributes = [])
	{
		$instance = $this->related->newInstance($this->getDb(), $attributes);

		$this->setForeignAttributesForCreate($instance);

		$instance->save();

		return $instance;
	}

	/**
	 * Create a Collection of new instances of the related model.
	 *
	 * @param   array  $records array of array of attributes
	 * @return Collection
	 */
	public function createMany(array $records)
	{
		$instances = new Collection;

		foreach ($records as $record)
		{
			$instances->add($this->create($record));
		}

		return $instances;
	}

	/**
	 * Set the foreign ID for creating a related model.
	 *
	 * @param   Model  $model Model to attached the relation foreign key
	 * @return void
	 */
	protected function setForeignAttributesForCreate(Model $model)
	{
		$model->setAttribute($this->getForeignKey(), $this->getParentKeyValue());
	}

	/**
	 * Perform an update on all the related models.
	 *
	 * @param   array  $attributes attributes array (can be aliased)
	 * @return integer
	 */
	public function updateRelated(array $attributes)
	{
		if ($this->related->usesTimestamps() && $this->related->getColumnAlias("updatedAt"))
		{
			$this->related->updatedAt = $this->related->freshTimestampString();
		}

		return $this->related->update($attributes);
	}

	/**
	 * Get the key value of the parent's local key.
	 *
	 * @return mixed
	 */
	public function getParentKeyValue()
	{
		return $this->parent->getAttribute($this->localKey);
	}

	/**
	 * Get the fully qualified parent key name.
	 *
	 * @return string
	 */
	public function getQualifiedParentKey()
	{
		return $this->parent->qualifyColumn($this->localKey);
	}

	/**
	 * Get the plain foreign key.
	 *
	 * @return string
	 */
	public function getForeignKey()
	{
		$segments = explode('.', $this->foreignKey);

		return end($segments);
	}
}
