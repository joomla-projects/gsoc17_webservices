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

use Joomla\Component\Users\Administrator\Table\EntityTableTrait;
use Joomla\Component\Users\Administrator\Entity\UserNote;
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
	use EntityTableTrait;

	/**
	 * Constructor
	 *
	 * @param   DatabaseDriver  $db          Database object
	 * @param   boolean         $loadFields  true if model is preloaded with table columns (null values)
	 *
	 * @since  2.5
	 */
	public function __construct(DatabaseDriver $db, $loadFields = true)
	{
		$this->setTypeAlias('com_users.note');

		$dispatcher = \JFactory::getApplication()->getDispatcher();

		$this->setDispatcher($dispatcher);

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
		return $this->save($updateNulls);
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
				// $this->setError(\JText::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));

				return false;
			}
		}

		$query = $this->getDb()->getQuery(true);
		$fields = array($this->getColumnAlias('state') . '=' . (int) $state);

		$query->update($this->getTable())
			->set($fields);

		// Build the WHERE clause for the primary keys.
		$query->where($k . '=' . implode(' OR ' . $k . '=', $pks));

		$query->where('(checked_out = 0 OR checked_out = ' . (int) $userId . ')');

		// Update the publishing state for rows with the given primary keys.
		$this->getDb()->setQuery($query);

		try
		{
			$this->getDb()->execute();
		}
		catch (\RuntimeException $e)
		{
			// $this->setError($this->_db->getMessage());

			return false;
		}

		// If checkin is supported and all rows were adjusted, check them in.
		if (count($pks) == $this->getDb()->getAffectedRows())
		{
			// Checkin the rows.
			foreach ($pks as $pk)
			{
				$this->checkIn($pk);
			}
		}

		// If the \JTable instance value is in the list of primary keys that were set, set the instance.
		if (in_array($this->$k, $pks))
		{
			$this->state = $state;
		}

		// $this->setError('');

		return true;
	}
}
