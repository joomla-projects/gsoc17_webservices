<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Entity;

use Joomla\CMS\Event\AbstractEvent;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Entity\Model;

defined('JPATH_PLATFORM') or die;

/**
 * Trait to apply to the Joomla Entity system which allows it to implement \Joomla\CMS\Table\TableInterface
 *
 * @since  __DEPLOY_VERSION__
 */
trait EntityTableFormTrait
{
	use DispatcherAwareTrait;

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

		if (is_array($pk))
		{
			$pk = empty($pk[$key]) ? null : $pk[$key];
		}

		$this->load($pk);

		$this->$checkedOutField = '0';
		$this->$checkedOutTimeField = $this->getDb()->getNullDate();
		$this->save();

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

		if (is_array($pk))
		{
			$pk = empty($pk[$key]) ? null : $pk[$key];
		}

		// Get the current time in the database format.
		$time = \JFactory::getDate()->toSql();

		$this->load($pk);

		$this->$checkedOutField = (int) $userId;
		$this->$checkedOutTimeField = $time;
		$this->save();

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