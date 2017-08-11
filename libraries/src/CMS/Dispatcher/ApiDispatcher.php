<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\Dispatcher;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Controller\Controller;
use Joomla\CMS\Mvc\Factory\MvcFactory;

/**
 * API Implementation for our dispatcher. It loads a component's administrator language files, and calls the API
 * Controller so that components that haven't implemented webservices can add their own handling.
 *
 * @since  __DEPLOY_VERSION__
 */
final class ApiDispatcher implements DispatcherInterface
{
	/**
	 * The URL option for the component.
	 *
	 * @var    string
	 * @since  1.6
	 */
	protected $option;

	/**
	 * The extension namespace
	 *
	 * @var    string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $namespace;

	/**
	 * The CmsApplication instance
	 *
	 * @var    CMSApplication
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * The JApplication instance
	 *
	 * @var    \JInput
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $input;

	/**
	 * Constructor for Dispatcher
	 *
	 * @param   CMSApplication  $app    The JApplication for the dispatcher
	 * @param   \JInput         $input  JInput
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(CMSApplication $app, \JInput $input = null)
	{
		$this->app    = $app;
		$this->input  = $input ?: $app->input;
		$this->option = $this->input->get('option');

		$db = \JFactory::getDbo();

		$query = $db->getQuery(true);

		$query->select($db->quoteName(array('namespace')))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('namespace') . ' IS NOT NULL AND ' . $db->quoteName('namespace') . ' != ""')
			->where($db->quoteName('element') . ' = ' . $db->quote($this->option));

		$db->setQuery($query);

		$this->namespace = $db->loadResult();

		if ($this->namespace === null)
		{
			throw new \RuntimeException('Namespace can not be empty!');
		}

		$this->loadLanguage();
	}

	/**
	 * Load the component's administrator language
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * @return  void
	 */
	protected function loadLanguage()
	{
		// Load common and local language files.
		$this->app->getLanguage()->load($this->option, JPATH_BASE, null, false, true) ||
		$this->app->getLanguage()->load($this->option, JPATH_COMPONENT_ADMINISTRATOR, null, false, true);
	}

	/**
	 * Dispatch a controller task. Redirecting the user if appropriate.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function dispatch()
	{
		$command = $this->input->getCmd('task', 'display');

		// Check for a controller.task command.
		if (strpos($command, '.') !== false)
		{
			// Explode the controller.task command.
			list ($controllerName, $task) = explode('.', $command);

			$this->input->set('controller', $controllerName);
			$this->input->set('task', $task);
		}
		else
		{
			// Do we have a controller?
			$controllerName = $this->input->get('controller', 'controller');
			$task           = $command;
		}

		// Build controller config data
		$config['option'] = $this->option;

		// Set name of controller if it is passed in the request
		if ($this->input->exists('controller'))
		{
			$config['name'] = strtolower($this->input->get('controller'));
		}

		// Execute the task for this component
		$controller = $this->getController($controllerName, $config);
		$controller->execute($task);
		$controller->redirect();
	}

	/**
	 * Get a controller from the component
	 * TODO: Run the API Controller rather than the component's administrator controller
	 *
	 * @param   string  $name    Controller name
	 * @param   array   $config  Optional controller config
	 *
	 * @return  Controller
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getController($name, $config = array())
	{
		// Set up the namespace
		$namespace = rtrim($this->namespace, '\\') . '\\';

		$controllerClass = $namespace . 'Administrator\\Controller\\' . ucfirst($name);

		if (!class_exists($controllerClass))
		{
			throw new \InvalidArgumentException(\JText::sprintf('JLIB_APPLICATION_ERROR_INVALID_CONTROLLER_CLASS', $controllerClass));
		}

		$controller = new $controllerClass($config, new MvcFactory($namespace, $this->app), $this->app, $this->input);

		return $controller;
	}
}
