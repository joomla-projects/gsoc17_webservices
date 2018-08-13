<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Users\Administrator\Table;

defined('_JEXEC') or die;

use Joomla\CMS\Entity\EntityTableFormTrait;
use Joomla\CMS\Entity\EntityTableTrait;
use Joomla\CMS\Entity\UserNote;
use Joomla\Database\DatabaseDriver;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Table\TableInterface;

/**
 * User notes table class
 *
 * @note In the user_note table you absolutely need the checked_out and checked_out_time columns
 *
 * @since  2.5
 */
class NoteTable extends UserNote implements TableInterface
{
	use EntityTableFormTrait;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db          Database object
	 * @param   boolean         $loadFields  true if model is preloaded with table columns (null values)
	 *
	 * @throws \Exception
	 * @since  2.5
	 */
	public function __construct(DatabaseDriver $db, $loadFields = true)
	{
		$this->setTypeAlias('com_users.note');

		$dispatcher = \JFactory::getApplication()->getDispatcher();

		$this->setDispatcher($dispatcher);

		parent::__construct($db);
	}

	/**
	 * Overloaded store method for the notes table.
	 *
	 * @param   boolean  $updateNulls  Toggle whether null values should be updated.
	 *
	 * @return  boolean  True on success, false on failure.
	 *
	 * @since   2.5
	 */
	public function store($updateNulls = false)
	{
		$userId = \JFactory::getUser()->get('id');

		if ($this->id)
		{
			$this->modified_user_id = $userId;
		}
		else
		{
			$this->modified_user_id = 0;
			$this->created_user_id = $userId;
		}

		// Attempt to store the data.
		return $this->persist($updateNulls);
	}

	/**
	 * Method to set the publishing state for a row or list of rows in the database
	 * table.  The method respects checked out rows by other users and will attempt
	 * to check-in rows that it can after adjustments are made.
	 *
	 * @param   mixed    $pks     An optional array of primary key values to update.  If not set the instance property value is used.
	 * @param   integer  $state   The publishing state. eg. [0 = unpublished, 1 = published]
	 * @param   integer  $userId  The user id of the user performing the operation.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 * @throws \Exception
	 */
	public function publish($pks = null, $state = 1, $userId = 0)
	{
		$k = $this->getPrimaryKey();

		// Sanitize input.
		$pks = ArrayHelper::toInteger($pks);
		$userId = (int) $userId;
		$state  = (int) $state;

		// If there are no primary keys set check to see if the instance key is set.
		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array($this->$k);
			}
			// Nothing to set publishing state on, return false.
			else
			{
				throw new \Exception(\JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
			}
		}

		$query = $this->getDb()->getQuery(true);
		$fields = array($this->getColumnAlias('state') . '=' . (int) $state);

		$query->update($this->getTable())
			->set($fields);

		// Build the WHERE clause for the primary keys.
		$query->where($k . '=' . implode(' OR ' . $k . '=', $pks));

		// Determine if there is checkin support for the table.
		if (!$this->hasField('checked_out') || !$this->hasField('checked_out_time'))
		{
			$checkin = false;
		}
		else
		{
			$query->where('(checked_out = 0 OR checked_out = ' . (int) $userId . ')');
			$checkin = true;
		}

		// Update the publishing state for rows with the given primary keys.
		$this->getDb()->setQuery($query)->execute();

		// If checkin is supported and all rows were adjusted, check them in.
		if ($checkin && count($pks) == $this->getDb()->getAffectedRows())
		{
			// Checkin the rows.
			foreach ($pks as $pk)
			{
				$this->checkIn($pk);
			}
		}

		// If the Entity instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks))
		{
			$this->state = $state;
		}

		return true;
	}
}
