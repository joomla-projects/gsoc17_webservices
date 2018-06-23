<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Administrator\Table;

use Joomla\Database\DatabaseDriver;

defined('JPATH_PLATFORM') or die;

/**
 * Trait to apply to the Joomla Entity system which allows it to implement \Joomla\CMS\Table\TableInterface
 *
 * @since  __DEPLOY_VERSION__
 */
trait EntityTableTrait
{
	/**
	 * @var mixed
	 */
	protected $type_alias;

	/**
	 * @var array
	 */
	public $newTags;

	/**
	 * The cache of the columns attributes for each table.
	 *
	 * @var array
	 */
	public static $fieldsCache = [];

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db          Database object
	 * @param   boolean         $loadFields  true if model is preloaded with table columns (null values)
	 *
	 * @since  2.5
	 */
	public function __construct(DatabaseDriver $db, $loadFields = false)
	{
		// TODO hack: Initialise the table properties. Needed for loading data from forms.
		if ($loadFields)
		{
			$fields = $this->getFields($db);
			parent::__construct($db, $fields);
		}
		else
		{
			parent::__construct($db);
		}

	}

	/**
	 * Getter for Type Alias
	 *
	 * @return mixed
	 */
	public function getTypeAlias()
	{
		return $this->type_alias;
	}

	/**
	 * Setter for type alias
	 *
	 * @param   mixed  $type_alias  type alias
	 * @return void
	 */
	public function setTypeAlias($type_alias)
	{
		$this->type_alias = $type_alias;
	}

	/**
	 * Wrapper for getPrimaryKey
	 *
	 * @return mixed
	 */
	public function getKeyName()
	{
		return $this->getPrimaryKey();
	}

	/**
	 * Load function
	 *
	 * @param   array    $keys   keys to be loaded
	 * @param   boolean  $reset  reset flag
	 *
	 * @return mixed
	 *
	 * @todo: This should return in the same model instance. Still to be discussed.
	 */
	public function load($keys = null, $reset = true)
	{
		if ($reset)
		{
			$this->reset();
		}

		return $this->find($keys);
	}

	/**
	 * Wrapper for getAttributes
	 *
	 * @return mixed
	 */
	public function getProperties()
	{
		return $this->getAttributes();
	}

	/**
	 * Wrapper for getDb
	 *
	 * @return mixed
	 */
	public function getDbo()
	{
		return $this->getDb();
	}

	/**
	 * Wrapper for getPrimaryKeyValue
	 *
	 * @return mixed
	 */
	public function getId()
	{
		return $this->getPrimaryKeyValue();
	}

	/**
	 * Check function
	 *
	 * @return boolean
	 *
	 * @todo add to entities
	 */
	public function check()
	{
		return true;
	}

	/**
	 * Bind function
	 *
	 * @param   array  $src     assoc array of values for binding
	 * @param   array  $ignore  keys to be ignored
	 *
	 * @return boolean
	 */
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

	/**
	 * Store function
	 *
	 * @param   boolean  $nulls  save nulls flag
	 *
	 * @return mixed
	 */
	public function store($nulls = false)
	{
		return $this->save($nulls);
	}

	/**
	 * Reset function
	 *
	 * @return void
	 *
	 * @todo This concept doesn't really exist
	 */
	public function reset()
	{

	}

	/**
	 * Set function
	 *
	 * @param   string  $key    attribute name
	 * @param   mixed   $value  attribute value
	 *
	 * @return boolean
	 */
	public function set($key, $value)
	{
		if (property_exists($this, $key))
		{
			$this->$key = $value;

			return true;
		}

		$this->setAttribute($key, $value);

		return true;
	}

	/**
	 * Get the columns from database table.
	 *
	 * @param   DatabaseDriver  $db      database driver instance
	 * @param   boolean         $reload  flag to reload cache
	 *
	 * @return  mixed  An array of the field names, or false if an error occurs.
	 *
	 * @throws  \UnexpectedValueException
	 */
	public function getFields($db, $reload = false)
	{
		// Lookup the fields for this table only once.
		if (!isset(static::$fieldsCache[$this->getTable()]) || $reload)
		{
			$fields = $db->getTableColumns($this->getTable());

			if (empty($fields))
			{
				throw new \UnexpectedValueException(sprintf('No columns found for %s table', $name));
			}

			if (empty($fields))
			{
				throw new \UnexpectedValueException(sprintf('No columns found for %s table', $this->getTable()));
			}

			$fields = array_map(
				function ($field)
				{
					return null;
				},
				$fields
			);

			static::$fieldsCache[$this->getTable()] = $fields;
		}

		return static::$fieldsCache[$this->getTable()];
	}
}
