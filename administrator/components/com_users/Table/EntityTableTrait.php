<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Administrator\Table;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\Database\DatabaseDriver;
use Joomla\Entity\Model;
use Joomla\Event\DispatcherAwareTrait;

defined('JPATH_PLATFORM') or die;

/**
 * Trait to apply to the Joomla Entity system which allows it to implement \Joomla\CMS\Table\TableInterface
 *
 * @since  __DEPLOY_VERSION__
 */
trait EntityTableTrait
{
	use DispatcherAwareTrait;

	/**
	 * @var mixed
	 */
	protected $type_alias;

	/**
	 * @var array
	 */
	public $newTags;

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
	 *
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
	 * Load a row in the current insance
	 *
	 * @param   mixed    $key    primary key, if there is no key, then this is used for a new item, therefore select last
	 * @param   boolean  $reset  reset flag
	 *
	 * @return boolean
	 */
	public function load($key = null, $reset = true)
	{
		$query = $this->newQuery();

		if ($reset)
		{
			$this->reset();
		}

		$this->setAttributes($query->selectRaw($key));

		$this->exists = true;

		return true;
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
	 * @return DatabaseDriver
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
	 * Method to check a row in if the necessary properties/fields exist.
	 *
	 * Checking a row in will allow other users the ability to edit the row.
	 *
	 * @param   mixed  $pk  An optional primary key value to check out.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 * @throws  \UnexpectedValueException
	 */
	public function checkIn($pk = null)
	{
		// Pre-processing by observers
		$event = AbstractEvent::create(
			'onTableBeforeCheckin',
			array(
				'subject'	=> $this,
				'pk'		=> $pk,
			)
		);
		$this->getDispatcher()->dispatch('onTableBeforeCheckin', $event);

		$checkedOutField = $this->getColumnAlias('checked_out');
		$checkedOutTimeField = $this->getColumnAlias('checked_out_time');

		$key = $this->getPrimaryKey();

		if (is_null($pk))
		{
			$pk = array();

			$pk[$key] = $this->getPrimaryKeyValue();
		}
		elseif (!is_array($pk))
		{
			$pk = array($key => $pk);
		}

		$pk[$key] = empty($pk[$key]) ? $this->$key : $pk[$key];

		if ($pk[$key] === null)
		{
			throw new \UnexpectedValueException('Null primary key not allowed.');
		}

		// Check the row in by primary key.
		$query = $this->getDb()->getQuery(true)
			->update($this->getTable())
			->set($this->getDb()->quoteName($checkedOutField) . ' = 0')
			->set($this->getDb()->quoteName($checkedOutTimeField) . ' = ' . $this->getDb()->quote($this->getDb()->getNullDate()));

		$query->where($key . '=' . $pk[$key]);

		$this->getDb()->setQuery($query);

		// Check for a database error.
		$this->getDb()->execute();

		// Set table values in the object.
		$this->$checkedOutField      = 0;
		$this->$checkedOutTimeField = '';

		// Post-processing by observers
		$event = AbstractEvent::create(
			'onTableAfterCheckin',
			array(
				'subject'	=> $this,
				'pk'		=> $pk,
			)
		);
		$this->getDispatcher()->dispatch('onTableAfterCheckin', $event);

		return true;
	}

	/**
	 * Method to check a row out if the necessary properties/fields exist.
	 *
	 * To prevent race conditions while editing rows in a database, a row can be checked out if the fields 'checked_out' and 'checked_out_time'
	 * are available. While a row is checked out, any attempt to store the row by a user other than the one who checked the row out should be
	 * held until the row is checked in again.
	 *
	 * @param   integer  $userId  The Id of the user checking out the row.
	 * @param   mixed    $pk      An optional primary key value to check out.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 * @throws  \UnexpectedValueException
	 */
	public function checkOut($userId, $pk = null)
	{
		// Pre-processing by observers
		$event = AbstractEvent::create(
			'onTableBeforeCheckout',
			array(
				'subject'	=> $this,
				'userId'	=> $userId,
				'pk'		=> $pk,
			)
		);
		$this->getDispatcher()->dispatch('onTableBeforeCheckout', $event);

		$checkedOutField = $this->getColumnAlias('checked_out');
		$checkedOutTimeField = $this->getColumnAlias('checked_out_time');

		$key = $this->getPrimaryKey();

		if (is_null($pk))
		{
			$pk = array();

			$pk[$key] = $this->getPrimaryKeyValue();
		}
		elseif (!is_array($pk))
		{
			$pk = array($key => $pk);
		}

		$pk[$key] = empty($pk[$key]) ? $this->$key : $pk[$key];

		if ($pk[$key] === null)
		{
			throw new \UnexpectedValueException('Null primary key not allowed.');
		}

		// Get the current time in the database format.
		$time = \JFactory::getDate()->toSql();

		// Check the row in by primary key.
		$query = $this->getDb()->getQuery(true)
			->update($this->getTable())
			->set($this->getDb()->quoteName($checkedOutField) . ' = ' . (int) $userId)
			->set($this->getDb()->quoteName($checkedOutTimeField) . ' = ' . $this->getDb()->quote($time));
		$query->where($key . '=' . $pk[$key]);
		$this->getDb()->setQuery($query);
		$this->getDb()->execute();

		// Set table values in the object.
		$this->$checkedOutField      = (int) $userId;
		$this->$checkedOutTimeField = $time;

		// Post-processing by observers
		$event = AbstractEvent::create(
			'onTableAfterCheckout',
			array(
				'subject'	=> $this,
				'userId'	=> $userId,
				'pk'		=> $pk,
			)
		);
		$this->getDispatcher()->dispatch('onTableAfterCheckout', $event);

		return true;
	}

	/**
	 * Method to determine if a row is checked out and therefore uneditable by a user.
	 *
	 * If the row is checked out by the same user, then it is considered not checked out -- as the user can still edit it.
	 *
	 * @param   integer  $with     The user ID to preform the match with, if an item is checked out by this user the function will return false.
	 * @param   integer  $against  The user ID to perform the match against when the function is used as a static function.
	 *
	 * @return  boolean  True if checked out.
	 *
	 * @since   11.1
	 */
	public function isCheckedOut($with = 0, $against = null)
	{
		// Handle the non-static case.
		if (isset($this) && ($this instanceof Model) && is_null($against))
		{
			$checkedOutField = $this->getColumnAlias('checked_out');
			$against = $this->$checkedOutField;
		}

		// The item is not checked out or is checked out by the same user.
		if (!$against || ($against == $with))
		{
			return false;
		}

		// This last check can only be relied on if tracking session metadata
		if (\JFactory::getConfig()->get('session_metadata', true))
		{
			$db = $this->getDb();
			$query = $db->getQuery(true)
				->select('COUNT(userid)')
				->from($db->quoteName('#__session'))
				->where($db->quoteName('userid') . ' = ' . (int) $against);
			$db->setQuery($query);
			$checkedOut = (boolean) $db->loadResult();

			// If a session exists for the user then it is checked out.
			return $checkedOut;
		}

		// Assume if we got here that there is a value in the checked out column but it doesn't match the given user
		return true;
	}
}
