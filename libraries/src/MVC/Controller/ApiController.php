<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\MVC\Controller;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MvcFactoryInterface;
use Joomla\CMS\Language\Text;

/**
 * Base class for a Joomla API Controller
 *
 * Controller (controllers are where you put all the actual code) Provides basic
 * functionality, such as rendering views (aka displaying templates).
 *
 * @since  __DEPLOY_VERSION__
 */
class ApiController extends BaseController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $contentType;

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
	 * The context for storing internal data, e.g. record.
	 *
	 * @var    string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $context;

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
	 * @throws  \Exception
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

		// Guess the context as the suffix, eg: OptionControllerContent.
		if (empty($this->context))
		{
			$r = null;

			if (!preg_match('/(.*)Controller(.*)/i', get_class($this), $r))
			{
				throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_CONTROLLER_GET_NAME'), 500);
			}

			$this->context = str_replace('\\', '', strtolower($r[2]));
		}
	}

	/**
	 * Basic display of an item view
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function displayItem()
	{
		$id = $this->input->get('id', 0, 'int');

		$document = \JFactory::getDocument();
		$viewType = $document->getType();
		$viewName = $this->input->get('view', 'ItemJsonView');
		$viewLayout = $this->input->get('layout', 'default', 'string');

		try
		{
			$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]);
		}
		catch (\Exception $e)
		{
			return $this;
		}

		// Get/Create the model
		if ($model = $this->getModel())
		{
			// Push the model into the view (as default)
			$view->setModel($model, true);
		}

		try
		{
			$modelName = $model->getName();
		}
		catch (\Exception $e)
		{
			return $this;
		}

		$view->document = $document;

		if (empty($model->getState($modelName . 'id')))
		{
			$model->setState($modelName . 'id', $id);
		}

		$view->display();

		return $this;
	}

	/**
	 * Basic display of a list view
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function displayList()
	{
		// Assemble pagination information (using recommended JsonApi pagination notation for offset strategy)
		$paginationInfo = $this->input->get('page', [], 'array');
		$internalPaginationMapping = [];

		if (array_key_exists('offset', $paginationInfo))
		{
			$this->app->input->set('limitstart', $paginationInfo['offset']);
		}

		if (array_key_exists('limit', $paginationInfo))
		{
			$internalPaginationMapping['limit'] = $paginationInfo['limit'];
		}

		$this->app->input->set('list', $internalPaginationMapping);


		$document = \JFactory::getDocument();
		$viewType = $document->getType();
		$viewName = $this->input->get('view', 'ListJsonView');
		$viewLayout = $this->input->get('layout', 'default', 'string');

		try
		{
			$view = $this->getView($viewName, $viewType, '', ['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]);
		}
		catch (\Exception $e)
		{
			return $this;
		}

		// Get/Create the model
		if ($model = $this->getModel($this->contentType))
		{
			// Push the model into the view (as default)
			$view->setModel($model, true);
		}

		$view->document = $document;

		$view->display();

		return $this;
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
		if (!\JFactory::getUser()->authorise('core.delete', $this->option))
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED', 403);
		}

		$id = $this->input->get('id', 0, 'int');

		/** @var \Joomla\CMS\MVC\Model\AdminModel $model */
		$model = $this->getModel();

		// Remove the item.
		if (!$model->delete($id))
		{
			throw new \RuntimeException($model->getError(), 500);
		}

		$this->app->setHeader('status', 204);
	}

	/**
	 * Method to add a new record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function add($key = null, $urlVar = null)
	{
		// Access check.
		if (!$this->allowAdd())
		{
			throw new NotAllowed('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED', 403);
		}
		else
		{
			if (empty($urlVar))
			{
				$urlVar = 'id';
			}

			// TODO: Error handling. We have error messages set into the controller
			$success = $this->save($key, $urlVar);

			if ($success)
			{
				$this->displayItem();
			}
		}
	}

	/**
	 * Method to edit an existing record.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key
	 *                           (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if save succeeded after access level check and checkout passes, false otherwise.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function edit($key = null, $urlVar = null)
	{
		/** @var \Joomla\CMS\MVC\Model\AdminModel $model */
		$model = $this->getModel();

		try
		{
			$table = $model->getTable();
		}
		catch (\Exception $e)
		{
			$this->setMessage($e->getMessage());

			return false;
		}

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
			throw new NotAllowed('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED', 403);
		}

		// Attempt to check-out the new record for editing and redirect.
		if ($checkin && !$model->checkout($recordId))
		{
			// Check-out failed, display a notice but allow the user to see the record.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');

			return false;
		}
		else
		{
			// Check-out succeeded, push the new record id into the session.
			$this->holdEditId($this->context, $recordId);
			$this->app->setUserState($this->context . '.data', null);

			return $this->save($key, $urlVar);
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
		/** @var \Joomla\CMS\MVC\Model\AdminModel $model */
		$model = $this->getModel();

		try
		{
			$table = $model->getTable();
		}
		catch (\Exception $e)
		{
			$this->setMessage($e->getMessage());

			return false;
		}

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

		// Validate the posted data.
		// Sometimes the form needs some posted data, such as for plugins and modules.
		$form = $model->getForm($data, false);

		if (!$form)
		{
			$this->setMessage($model->getError(), 'error');

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
					$this->setMessage($errors[$i]->getMessage(), 'warning');
				}
				else
				{
					$this->setMessage($errors[$i], 'warning');
				}
			}

			// Save the data in the session.
			$this->app->setUserState($context . '.data', $data);

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
			$this->app->setUserState($context . '.data', $data);

			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'error');

			return false;
		}
		// Save succeeded, so check-in the record.
		if ($checkin && $model->checkin($validData[$key]) === false)
		{
			// Save the data in the session.
			$this->app->setUserState($context . '.data', $validData);

			// Check-in failed, so go back to the record and display a notice.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');

			return false;
		}

		// Clear the record id and data from the session.
		$this->releaseEditId($context, $validData[$key]);
		$this->app->setUserState($context . '.data', null);

		return true;
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
}
