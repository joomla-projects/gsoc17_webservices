<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Entity\Category;
use Joomla\CMS\Pagination\Pagination;
use Joomla\Database\DatabaseDriver;
use Joomla\Entity\Helpers\Collection;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of article records.
 *
 * @since  1.6
 */
class ArticlesModel extends ArticleModel
{
	/**
	 * @var array
	 */
	protected $filterFields;

	/**
	 * Name of the filter form to load
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $filterFormName = null;

	/**
	 * Associated HTML form
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $htmlFormName = 'adminForm';

	/**
	 * @var array
	 */
	protected $list = [
		'ordering' => 'id',
		'direction' => 'DESC',
		'links' => null,
		'limit' => null,
	];

	/**
	 * @var array
	 */
	protected $filter = [
		'id' => null,
		'title' => null,
		'alias' => null,
		'checked_out' => null,
		'checked_out_time' => null,
		'catid' => null,
		'state' => null,
		'access' => null,
		'created' => null,
		'modified' => null,
		'created_by' => null,
		'created_by_alias' => null,
		'ordering' => null,
		'featured' => null,
		'language' => null,
		'hits' => null,
		'publish_up' => null,
		'publish_down' => null,
		'published' => null,
		'author_id' => null,
		'author_id.include' => true,
		'category_id' => [],
		'level' => null,
		'tag' => null,
		'rating_count' => null,
	];

	/**
	 * Context string for the model type.  This is used to handle uniqueness
	 * when dealing with the getStoreId() method and caching data structures.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $context = null;

	/**
	 * Constructor.
	 *
	 * @param   DatabaseDriver  $db  Database Driver
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws \Exception
	 */
	public function __construct($db)
	{
		$this->filterFields = [
			'id', 'a.id',
			'title', 'a.title',
			'alias', 'a.alias',
			'checked_out', 'a.checked_out',
			'checked_out_time', 'a.checked_out_time',
			'catid', 'a.catid', 'category.title',
			'state', 'a.state',
			'access', 'a.access',
			'created', 'a.created',
			'modified', 'a.modified',
			'created_by', 'a.created_by',
			'created_by_alias', 'a.created_by_alias',
			'ordering', 'a.ordering',
			'featured', 'a.featured',
			'language', 'a.language',
			'hits', 'a.hits',
			'publish_up', 'a.publish_up',
			'publish_down', 'a.publish_down',
			'published', 'a.published',
			];

		if (\JLanguageAssociations::isEnabled())
		{
			$this->filterFields[] = 'association';
		}

		$app = \JFactory::getApplication();

		// Guess the context as Option.ModelName.
		$this->context = strtolower($this->option . '.' . $this->getName());

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		$forcedLanguage = $app->input->get('forcedLanguage', '', 'cmd');

		// Adjust the context to support forced languages.
		if ($forcedLanguage)
		{
			$this->context .= '.' . $forcedLanguage;
		}

		$this->filter['search'] = $app->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');

		$this->filter['published'] = $app->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');

		$this->filter['level'] = $app->getUserStateFromRequest($this->context . '.filter.level', 'filter_level');

		$this->filter['language'] = $app->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');

		$formSubmited = $app->input->post->get('form_submited');

		$access     = $app->getUserStateFromRequest($this->context . '.filter.access', 'filter_access');
		$authorId   = $app->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
		$categoryId = $app->getUserStateFromRequest($this->context . '.filter.category_id', 'filter_category_id');
		$tag        = $app->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', '');

		if ($formSubmited)
		{
			$this->filter['access'] = $app->input->post->get('access');
			$this->filter['author_id'] = $app->input->post->get('author_id');
			$this->filter['category_id'] = $app->input->post->get('category_id');
			$this->filter['tag'] = $app->input->post->get('tag');
		}

		// List state information.
		$inputFilter = \JFilterInput::getInstance();

		// Receive & set filters
		if ($filters = $app->getUserStateFromRequest($this->context . '.filter', 'filter', array(), 'array'))
		{
			foreach ($filters as $name => $value)
			{
				$this->filter[$name] = $value;
			}
		}

		$limit = 0;

		// Receive & set list options
		if ($list = $app->getUserStateFromRequest($this->context . '.list', 'list', array(), 'array'))
		{
			foreach ($list as $name => $value)
			{
				// Extra validations
				switch ($name)
				{
					case 'fullordering':

						$orderingParts = explode(' ', $value);

						if (count($orderingParts) >= 2)
						{
							// Latest part will be considered the direction
							$fullDirection = end($orderingParts);

							if (in_array(strtoupper($fullDirection), array('ASC', 'DESC', '')))
							{
								$this->list['direction'] = $fullDirection;
							}
							else
							{
								// Fallback to the default value
								$value = $this->list['ordering'] . ' ' . $this->list['direction'];
							}

							unset($orderingParts[count($orderingParts) - 1]);

							// The rest will be the ordering
							$fullOrdering = implode(' ', $orderingParts);

							if (in_array($fullOrdering, $this->filterFields))
							{
								$this->list['ordering'] = $fullOrdering;
							}
							else
							{
								// Fallback to the default value
								$value = $this->list['ordering'] . ' ' . $this->list['direction'];
							}
						}
						else
						{
							// Fallback to the default value
							$value = $this->list['ordering'] . ' ' . $this->list['direction'];
						}

						break;

					case 'ordering':
						if (!in_array($value, $this->filterFields))
						{
							$value = $this->list['ordering'];
						}
						break;

					case 'direction':
						if (!in_array(strtoupper($value), array('ASC', 'DESC', '')))
						{
							$value = $this->list['direction'];
						}
						break;

					case 'limit':
						$value = $inputFilter->clean($value, 'int');
						$limit = $value;
						break;

					case 'select':
						$explodedValue = explode(',', $value);

						foreach ($explodedValue as &$field)
						{
							$field = $inputFilter->clean($field, 'cmd');
						}

						$value = implode(',', $explodedValue);
						break;
				}

				$this->list[$name] = $value;

			}
		}
		else
			// Keep B/C for components previous to jform forms for filters
		{
			// Pre-fill the limits
			$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'), 'uint');
			$this->list['limit'] = $limit;

			// Check if the ordering field is in the whitelist, otherwise use the incoming value.
			$value = $app->getUserStateFromRequest($this->context . '.ordercol', 'filter_order', $this->list['ordering']);

			if (!in_array($value, $this->filterFields))
			{
				$value = $this->list['ordering'];
				$app->setUserState($this->context . '.ordercol', $value);
			}

			$this->list['ordering'] = $value;

			// Check if the ordering direction is valid, otherwise use the incoming value.
			$value = $app->getUserStateFromRequest($this->context . '.orderdirn', 'filter_order_Dir', $this->list['direction']);

			if (!in_array(strtoupper($value), array('ASC', 'DESC', '')))
			{
				$value = $this->list['direction'];
				$app->setUserState($this->context . '.ordering', $value);
			}

			$this->list['direction'] = $value;
		}

		// Support old ordering field
		$oldOrdering = $app->input->get('filter_order');

		if (!empty($oldOrdering) && in_array($oldOrdering, $this->filterFields))
		{
			$this->list['ordering'] = $oldOrdering;
		}

		// Support old direction field
		$oldDirection = $app->input->get('filter_order_Dir');

		if (!empty($oldDirection) && in_array(strtoupper($oldDirection), array('ASC', 'DESC', '')))
		{
			$this->list['direction'] = $oldDirection;
		}

		$value = $app->getUserStateFromRequest($this->context . '.limitstart', 'limitstart', 0, 'int');
		$limitstart = ($limit != 0 ? (floor($value / $limit) * $limit) : 0);
		$this->list['start'] = $limitstart;

		// Force a language
		if (!empty($forcedLanguage))
		{
			$this->filter['language'] = $forcedLanguage;
			$this->filter['forcedLanguage'] = $forcedLanguage;
		}

		parent::__construct($db);
	}


	/**
	 * Gets the Articles Collection
	 *
	 * @return  Collection
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getCollection()
	{
		$this->setAlias('a');

		$columns = (isset($this->list['select'])) ? $this->list['select'] :
			['id', 'title', 'alias', 'checked_out', 'checked_out_time', 'catid',
			'state', 'access', 'created', 'created_by', 'created_by_alias', 'modified', 'ordering', 'featured', 'language', 'hits',
			'publish_up', 'publish_down', 'introtext', '`fulltext`'];

		$with = [];

		$with[] = 'lang:lang_id,lang_code,title,image';

		$with[] = 'editor:id,name';

		$with[] = 'viewLevel:id,title';

		$with[] = 'category:id,title';

		$with[] = 'author:id,name';

		if (\JPluginHelper::isEnabled('content', 'vote'))
		{
			$with[] = 'rating:content_id,ratingSum,rating_count';
		}

		$this->addFilters();

		// Add the list ordering clause.
		$orderCol  = (strpos($this->list['ordering'], '.') > 0) ? $this->list['ordering'] : $this->qualifyColumn($this->list['ordering']);
		$orderDim = $this->list['direction'];

		if (strpos($orderCol, 'a.') === 0)
		{
			$this->order($this->getDb()->escape($orderCol) . ' ' . $this->getDb()->escape($orderDim));

			return $this->with($with)->get($columns);
		}
		else
		{
			$collection = $this->with($with)->get($columns);

			return $collection->sortByOrdering($orderCol . ' ' . $orderDim);
		}
	}

	/**
	 * Funtion to add filters fot the current query in getCollection
	 *
	 * @return void
	 */
	public function addFilters()
	{
		$user  = \JFactory::getUser();

		// Filter by access level.
		$access = $this->filter['access'];

		if (is_numeric($access))
		{
			$this->where($this->qualifyColumn('access') . ' = ' . (int) $access);
		}
		elseif (is_array($access))
		{
			$access = ArrayHelper::toInteger($access);
			$this->whereIn($this->qualifyColumn('access'), $access);
		}

		// Filter by access level on categories.
		if (!$user->authorise('core.admin'))
		{
			$groups = implode(',', $user->getAuthorisedViewLevels());
			$this->whereIn($this->qualifyColumn('access'), $groups);

			$relation = 'category';
			$column = $this->qualifyRelatedColumn($relation, 'access');
			$this->filter(
				$relation,
				function ($query) use ($column, $groups)
				{
					$query->whereIn($column, $groups);
				}
			);
		}

		// Filter by published state
		$published = (string) $this->filter['published'];
		$stateColumn = $this->qualifyColumn('state');

		if (is_numeric($published))
		{
			$this->where($stateColumn . ' = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$this->where("($stateColumn = 0 OR $stateColumn = 1)");
		}

		// Filter by categories and by level
		$categoryId = $this->filter['category_id'];
		$level = $this->filter['level'];

		if (!is_array($categoryId))
		{
			$categoryId = $categoryId ? [$categoryId] : [];
		}

		// Case: Using both categories filter and by level filter
		if (count($categoryId))
		{
			$categoryId = ArrayHelper::toInteger($categoryId);
			$category = new Category($this->getDb());
			$subCatItemsWhere = [];

			$relation = 'category';
			$levelColumn = $this->qualifyRelatedColumn($relation, 'level');
			$lftColumn = $this->qualifyRelatedColumn($relation, 'lft');
			$rghColumn = $this->qualifyRelatedColumn($relation, 'rgt');

			foreach ($categoryId as $filterCatId)
			{
				$category->load($filterCatId, true);
				$subCatItemsWhere[] = '(' .
					($level ? "$levelColumn <= " . ((int) $level + (int) $category->level - 1) . ' AND ' : '') .
					"$lftColumn >= " . (int) $category->lft . ' AND ' .
					"$rghColumn <= " . (int) $category->rgt . ')';
			}

			$this->filter(
				$relation,
				function ($query) use ($subCatItemsWhere)
				{
					$query->where(implode(' OR ', $subCatItemsWhere));
				}
			);

		}

		// Case: Using only the by level filter
		elseif ($level)
		{
			$relation = 'category';
			$levelColumn = $this->qualifyRelatedColumn($relation, 'level');

			$this->filter(
				$relation,
				function ($query) use ($levelColumn, $level)
				{
					$query->where("$levelColumn <= " . (int) $level);
				}
			);
		}

		// Filter by author
		$authorId = $this->filter['author_id'];

		$createdByColumn = $this->qualifyColumn('created_by');

		if (is_numeric($authorId))
		{
			$type = $this->filter['author_id.include'] ? '=' : '<>';

			$this->where($createdByColumn . ' ' . $type . (int) $authorId);
		}
		elseif (is_array($authorId))
		{
			$authorId = ArrayHelper::toInteger($authorId);
			$this->whereIn($createdByColumn, $authorId);
		}

		// Filter by search in title.
		$search = $this->filter['search'];

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$this->where($this->qualifyColumn('id') . ' = ' . (int) substr($search, 3));
			}
			elseif (stripos($search, 'author:') === 0)
			{
				$search = $this->getDb()->quote('%' . $this->getDb()->escape(substr($search, 7), true) . '%');

				$relation = 'author';
				$nameColumn = $this->qualifyRelatedColumn($relation, 'name');
				$usernameColumn = $this->qualifyRelatedColumn($relation, 'username');

				$this->filter(
					$relation,
					function ($query) use ($nameColumn, $usernameColumn, $search)
					{
						$query->where("($nameColumn LIKE $search OR $usernameColumn LIKE $search )");
					}
				);
			}
			else
			{
				$search = $this->getDb()->quote('%' . str_replace(' ', '%', $this->getDb()->escape(trim($search), true) . '%'));
				$relation = 'author';
				$titleColumn = $this->qualifyRelatedColumn($relation, 'title');
				$aliasColumn = $this->qualifyRelatedColumn($relation, 'alias');

				$this->filter(
					$relation,
					function ($query) use ($titleColumn, $aliasColumn, $search)
					{
						$query->where("($titleColumn LIKE $search OR $aliasColumn LIKE $search )");
					}
				);
			}
		}

		// Filter on the language.
		if ($language = $this->filter['language'])
		{
			$this->where($this->qualifyColumn('language') . ' = ' . $this->getDb()->quote($language));
		}

		// TODO Filter by a single or group of tags.
	}
	/**
	 * Build a list of authors
	 *
	 * @return  \stdClass[]
	 *
	 * @since   1.6
	 */
	public function getAuthors()
	{
		/** @todo authors
		 *
		 * $authors = $this->author()->get(['id', 'name']);
		 * $authors->sortByOrdering('u.name');
		 * return $authors;
		 */

		// Create a new query object.
		$db    = $this->getDb();
		$query = $db->getQuery(true);

		// Construct the query
		$query->select('u.id AS value, u.name AS text')
			->from('#__users AS u')
			->join('INNER', '#__content AS c ON c.created_by = u.id')
			->group('u.id, u.name')
			->order('u.name');

		// Setup the query
		$db->setQuery($query);

		// Return the result
		return $db->loadObjectList();
	}

	/**
	 * Method to get a list of articles.
	 * Overridden to add a check for access levels.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   1.6.1
	 * @throws \Exception
	 */
	public function getItems()
	{
		// Get a storage key.
		$store = $this->getStoreId();

		// Try to load the data from internal storage.
		if (!isset($this->cache[$store]))
		{
			// Load the list items and add the items to the internal cache.
			$this->cache[$store] = $this->getCollection();
		}

		$items = $this->cache[$store];

		if (\JFactory::getApplication()->isClient('site'))
		{
			$groups = \JFactory::getUser()->getAuthorisedViewLevels();

			foreach (array_keys($items) as $x)
			{
				// Check the access level. Remove articles the user shouldn't see
				if (!in_array($items[$x]->access, $groups))
				{
					unset($items[$x]);
				}
			}
		}

		return $items;
	}

	/**
	 * Method to get a store id based on the model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  An identifier string to generate the store id.
	 *
	 * @return  string  A store id.
	 *
	 * @since   1.6
	 */
	protected function getStoreId($id = '')
	{
		// Add the list state to the store id.
		$id .= ':' . $this->list['start'];
		$id .= ':' . $this->list['limit'];
		$id .= ':' . $this->list['ordering'];
		$id .= ':' . $this->list['direction'];

		return md5($this->context . ':' . $id);
	}

	/**
	 * Method to get the starting number of items for the data set.
	 *
	 * @return  integer  The starting number of items available in the data set.
	 *
	 * @since   1.6
	 */
	public function getStart()
	{
		$store = $this->getStoreId('getstart');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$start = $this->list['start'];

		if ($start > 0)
		{
			$limit = $this->list['limit'];

			$total = $this->getTotal();

			if ($start > $total - $limit)
			{
				$start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
			}
		}

		// Add the total to the internal cache.
		$this->cache[$store] = $start;

		return $this->cache[$store];
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   1.6
	 */
	public function getTotal()
	{
		// Get a storage key.
		$store = $this->getStoreId('getTotal');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		// Load the total and add the total to the internal cache.
		$this->cache[$store] = (int) $this->getCount();

		return $this->cache[$store];
	}

	/**
	 * Returns the record count for the current filters.
	 *
	 * @return  integer  Number of rows for query.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function getCount()
	{
		// TODO filters

		$groupBy = false;
		$having = false;

		if ($groupBy || $having)
		{
			return $this->getCollection()->count();
		}
		else
		{
			return $this->count();
		}
	}

	/**
	 * Getter for list
	 *
	 * @return array
	 */
	public function getList()
	{
		return $this->list;
	}

	/**
	 * Getter for filter
	 *
	 * @return array
	 */
	public function getFilter()
	{
		return $this->filter;
	}

	/**
	 * Get the filter form
	 *
	 * @param   array    $data      data
	 * @param   boolean  $loadData  load current data
	 *
	 * @return  \JForm|boolean  The \JForm object or false on error
	 *
	 * @since   3.2
	 * @throws \Exception
	 */
	public function getFilterForm($data = array(), $loadData = true)
	{
		$form = null;

		// Try to locate the filter form automatically. Example: ContentModelArticles => "filter_articles"
		if (empty($this->filterFormName))
		{
			$classNameParts = explode('Model', get_called_class());

			if (count($classNameParts) >= 2)
			{
				$this->filterFormName = 'filter_' . str_replace('\\', '', strtolower($classNameParts[1]));
			}
		}

		if (!empty($this->filterFormName))
		{
			// Get the form.
			$form = $this->loadForm($this->context . '.filter', $this->filterFormName, array('control' => '', 'load_data' => $loadData));
		}

		return $form;
	}

	/**
	 * Method to get a \JPagination object for the data set.
	 *
	 * @return  \JPagination  A \JPagination object for the data set.
	 *
	 * @since   1.6
	 */
	public function getPagination()
	{
		// Get a storage key.
		$store = $this->getStoreId('getPagination');

		// Try to load the data from internal storage.
		if (isset($this->cache[$store]))
		{
			return $this->cache[$store];
		}

		$limit = (int) $this->list['limit'] - (int) $this->list['links'];

		// Create the pagination object and add the object to the internal cache.
		$this->cache[$store] = new Pagination($this->getTotal(), $this->getStart(), $limit);

		return $this->cache[$store];
	}

	/**
	 * Function to get the active filters
	 *
	 * Associative array in the format: array('filter_published' => 0)
	 *
	 * @return   array
	 *
	 * @since   3.2
	 */
	public function getActiveFilters()
	{
		$activeFilters = [];

		if (!empty($this->filterFields))
		{
			foreach ($this->filterFields as $filter)
			{
				if (array_key_exists($filter, $this->filter) && (!empty($this->filter[$filter]) || is_numeric($this->filter[$filter])))
				{
					$activeFilters[$filter] = $filter;
				}
			}
		}

		return $activeFilters;
	}

}
