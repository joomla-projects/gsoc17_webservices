<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Controller;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Mvc\Factory\MvcFactoryInterface;
use Joomla\Utilities\ArrayHelper;

/**
 * Base class for a Joomla API Controller
 *
 * Controller (controllers are where you put all the actual code) Provides basic
 * functionality, such as rendering views (aka displaying templates).
 *
 * @since  __DEPLOY_VERSION__
 */
class Api extends Controller
{
	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $option;

	/**
	 * The prefix to use with controller messages.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $text_prefix;

	/**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 * Recognized key values include 'name', 'default_task', 'model_path', and
	 * 'view_path' (this list is not meant to be comprehensive).
	 * @param   MvcFactoryInterface  $factory  The factory.
	 * @param   CmsApplication       $app      The JApplication for the dispatcher
	 * @param   \JInput              $input    Input
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($config = array(), MvcFactoryInterface $factory = null, $app = null, $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		// Guess the option as com_NameOfController
		if (empty($this->option))
		{
			$this->option = \JComponentHelper::getComponentName($this, $this->getName());
		}

		// Guess the \JText message prefix. Defaults to the option.
		if (empty($this->text_prefix))
		{
			$this->text_prefix = strtoupper($this->option);
		}
	}

	/**
	 * Removes an item.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function delete()
	{
		$id = $this->input->get('id', 0, 'int');

		// Get the model.
		$model = $this->getModel();

		// Remove the item.
		if ($model->delete($id))
		{
			$this->setMessage(\JText::plural($this->text_prefix . '_N_ITEMS_DELETED', count($id)));
		}
		else
		{
			$this->setMessage($model->getError(), 'error');
		}

		// Invoke the postDelete method to allow for the child class to access the model.
		$this->postDeleteHook($model, $id);
	}

	/**
	 * Typical view method for MVC based architecture
	 *
	 * This function is provide as a default implementation, in most cases
	 * you will need to override it in your own controllers.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function display($cachable = false, $urlparams = array())
	{

	}

	/**
	 * Method to add a new record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if the record can be added, false if not.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function add($key = null, $urlVar = null)
	{
		// Access check.
		if (!$this->allowAdd())
		{
			// Set the internal error.
			$this->setMessage(\JText::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');

			return false;
		}

		else
		{
			if (empty($urlVar))
			{
				$urlVar = 'id';
			}

			return $this->save($key, $urlVar);
		}
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 *                           (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if access level check and checkout passes, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function edit($key = null, $urlVar = null)
	{
		$model = $this->getModel();
		$table = $model->getTable();
		$id   = $this->input->post->get('id', array(), 'array');

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$urlVar = $key;
		}

		// Get the previous record id (if any) and the current record id.
		$recordId = (int) (count($id) ? $id[0] : $this->input->getInt($urlVar));
		$checkin = property_exists($table, $table->getColumnAlias('checked_out'));

		// Access check.
		if (!$this->allowEdit(array($key => $recordId), $key))
		{
			$this->setMessage(\JText::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');

			return false;
		}

		// Attempt to check-out the new record for editing and redirect.
		if ($checkin && !$model->checkout($recordId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setMessage(\JText::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');

			return false;
		}
		else
		{
			// Check-out succeeded, push the new record id into the session.
			$this->holdEditId($context, $recordId);
			\JFactory::getApplication()->setUserState($context . '.data', null);

			return true;
		}
	}

	/**
	 * Method to save a record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function save($key = null, $urlVar = null)
	{
		$app   = \JFactory::getApplication();
		$model = $this->getModel();
		$table = $model->getTable();
		$data  = $this->input->post->get('data', array(), 'array');
		$checkin = property_exists($table, $table->getColumnAlias('checked_out'));
		$context = "$this->option.edit.$this->context";

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$urlVar = $key;
		}

		$recordKey = $this->input->get($urlVar);
		$data[$key] = $recordKey;

		if (!$this->allowSave($data, $key))
		{
			$this->setMessage(\JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');

			return false;
		}

		// Validate the posted data.
		// Sometimes the form needs some posted data, such as for plugins and modules.
		$form = $model->getForm($data, false);

		if (!$form)
		{
			$app->enqueueMessage($model->getError(), 'error');

			return false;
		}

		// Test whether the data is valid.
		$validData = $model->validate($form, $data);

		// Check for validation errors.
		if ($validData === false)
		{
			// Get the validation messages.
			$errors = $model->getErrors();

			// Push up to three validation messages out to the user.
			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
			{
				if ($errors[$i] instanceof \Exception)
				{
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$app->setUserState($context . '.data', $data);

			return false;
		}

		if (!isset($validData['tags']))
		{
			$validData['tags'] = array();
		}

		// Attempt to save the data.
		if (!$model->save($validData))
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $data);

			$this->setMessage(\JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'error');

			return false;
		}
		// Save succeeded, so check-in the record.
		if ($checkin && $model->checkin($validData[$key]) === false)
		{
			// Save the data in the session.
			$app->setUserState($context . '.data', $validData);

			// Check-in failed, so go back to the record and display a notice.
			$this->setMessage(\JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');

			return false;
		}

		// Clear the record id and data from the session.
		$this->releaseEditId($context, $recordId);
		$app->setUserState($context . '.data', null);

		// Invoke the postSave method to allow for the child class to access the model.
		$this->postSaveHook($model, $validData);

		return true;
	}

	/**
	 * Method to check if you can save a new or existing record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function allowSave($data, $key = 'id')
	{
		$recordId = isset($data[$key]) ? $data[$key] : '0';

		if ($recordId)
		{
			return $this->allowEdit($data, $key);
		}
		else
		{
			return $this->allowAdd($data);
		}
	}

	/**
	 * Method to check if you can edit an existing record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key; default is id.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		return \JFactory::getUser()->authorise('core.edit', $this->option);
	}

	/**
	 * Method to check if you can add a new record.
	 *
	 * Extended classes can override this if necessary.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function allowAdd($data = array())
	{
		$user = \JFactory::getUser();

		return $user->authorise('core.create', $this->option) || count($user->getAuthorisedCategories($this->option, 'core.create'));
	}

	/**
	 * Function that allows child controller access to model data
	 * after the data has been saved.
	 *
	 * @param   \JModelLegacy  $model      The data model object.
	 * @param   array          $validData  The validated data.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function postSaveHook(\JModelLegacy $model, $validData = array())
	{
	}
}
