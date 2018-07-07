<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\Relations;

use Joomla\Entity\Helpers\Collection;
use Joomla\Entity\Model;
use Joomla\Entity\Query;


/**
 * Class BelongsTo
 * @package Joomla\Entity\Relations
 * @since   1.0
 */
class BelongsTo extends Relation
{
	/**
	 * The child model instance of the relation.
	 *
	 * @var Model
	 */
	protected $child;

	/**
	 * The foreign key of the parent model.
	 *
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * The associated key on the parent model.
	 *
	 * @var string
	 */
	protected $ownerKey;

	/**
	 * The name of the relation.
	 *
	 * @var string
	 */
	protected $relation;

	/**
	 * Create a new belongs to relation instance.
	 *
	 * @param   Query   $query      Query instance
	 * @param   Model   $child      child Model instance
	 * @param   string  $foreignKey foreign key name
	 * @param   string  $ownerKey   the associated key on the parent model.
	 * @param   string  $relation   relation name
	 */
	public function __construct(Query $query, Model $child, $foreignKey, $ownerKey, $relation)
	{
		$this->ownerKey = $ownerKey;
		$this->relation = $relation;
		$this->foreignKey = $foreignKey;
		$this->child = $child;

		parent::__construct($query, $child);
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
			/** For belongs to relationships, which are essentially the inverse of has one
			 * or has many relation, we need to actually query on the primary key
			 * of the related models matching on the foreign key that's on a parent.
			 */
			$table = $this->related->getTable();

			$this->query->where($table . '.' . $this->ownerKey . '=' . $this->child->{$this->foreignKey});
		}
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param   array $models eager load the relation on the specified models
	 *
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		$key = $this->related->getTable() . '.' . $this->ownerKey;

		$keys = $this->getKeys($models, $this->foreignKey);

		$keys = array_diff($keys, [null]);

		if (count($keys) > 0)
		{
			$this->query->whereIn($key, $keys);
		}
	}

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param   array  $models   the array of Model instances
	 * @param   string $relation relation name
	 *
	 * @return array
	 */
	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, null);
		}

		return $models;
	}

	/**
	 * Match the eagerly loaded results to their parents(read children).
	 *
	 * @param   array      $models   array of model instances (children)
	 * @param   Collection $results  Collection of results (parents)
	 * @param   string     $relation relation name
	 *
	 * @return array
	 */
	public function match(array $models, Collection $results, $relation)
	{
		$dictionary = $this->buildDictionary($results);

		/** Once we have the dictionary constructed, we can loop through all the parents
		 * and match back onto their children using these keys of the dictionary and
		 * the primary key of the children to map them onto the correct instances.
		 */
		foreach ($models as $model)
		{
			if (isset($dictionary[$model->{$this->foreignKey}]))
			{
				$model->setRelation($relation, $dictionary[$model->{$this->foreignKey}]);
			}
		}

		return $models;
	}

	/**
	 * Build model dictionary keyed by the parents primary key.
	 *
	 * @param   Collection  $results Collection of results
	 * @return array
	 */
	protected function buildDictionary(Collection $results)
	{
		$dictionary = [];

		foreach ($results as $result)
		{
			$dictionary[$result->getAttribute($this->ownerKey)] = $result;
		}

		return $dictionary;
	}

	/**
	 * Get the results of the relation.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->query->first();
	}
}
