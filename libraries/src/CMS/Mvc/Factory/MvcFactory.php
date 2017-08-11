<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\Mvc\Factory;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\CMSApplicationInterface;

/**
 * Factory to create MVC objects based on a namespace.
 *
 * @since  __DEPLOY_VERSION__
 */
class MvcFactory implements MvcFactoryInterface
{
	/**
	 * The namespace to create the objects from.
	 *
	 * @var string
	 */
	private $namespace = null;

	/**
	 * The application.
	 *
	 * @var CMSApplicationInterface
	 */
	private $application = null;

	/**
	 * The namespace must be like:
	 * Joomla\Component\Content
	 *
	 * @param   string                   $namespace    The namespace.
	 * @param   CMSApplicationInterface  $application  The application
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($namespace, CMSApplicationInterface $application)
	{
		$this->namespace   = $namespace;
		$this->application = $application;
	}

	/**
	 * Method to load and return a model object.
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  Optional model prefix.
	 * @param   array   $config  Optional configuration array for the model.
	 *
	 * @return  \Joomla\CMS\Model\Model  The model object
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \Exception
	 */
	public function createModel($name, $prefix = '', array $config = array())
	{
		if (empty($prefix) && $this->application->isClient('api'))
		{
			$prefix = 'Administrator';
		}

		// Clean the parameters
		$name   = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

		$className = $this->getClassName('Model\\' . ucfirst($name), $prefix);

		if (!$className)
		{
			return null;
		}

		return new $className($config, $this);
	}

	/**
	 * Method to load and return a view object.
	 *
	 * @param   string  $name    The name of the view.
	 * @param   string  $prefix  Optional view prefix.
	 * @param   string  $type    Optional type of view.
	 * @param   array   $config  Optional configuration array for the view.
	 *
	 * @return  \Joomla\CMS\View\AbstractView  The view object
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \Exception
	 */
	public function createView($name, $prefix = '', $type = '', array $config = array())
	{
		// Clean the parameters
		$name   = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);
		$type   = preg_replace('/[^A-Z0-9_]/i', '', $type);

		$className = $this->getClassName('View\\' . ucfirst($name) . '\\' . ucfirst($type), $prefix);

		if (!$className)
		{
			return null;
		}

		return new $className($config);
	}

	/**
	 * Method to load and return a table object.
	 *
	 * @param   string  $name    The name of the table.
	 * @param   string  $prefix  Optional table prefix.
	 * @param   array   $config  Optional configuration array for the table.
	 *
	 * @return  \Joomla\CMS\Table\Table  The table object
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \Exception
	 */
	public function createTable($name, $prefix = '', array $config = array())
	{
		// Clean the parameters
		$name = preg_replace('/[^A-Z0-9_]/i', '', $name);
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);

		$className = $this->getClassName('Table\\' . ucfirst($name), $prefix)
				?: $this->getClassName('Table\\' . ucfirst($name), 'Administrator');

		if (!$className)
		{
			return null;
		}

		if (array_key_exists('dbo', $config))
		{
			$db = $config['dbo'];
		}
		else
		{
			$db = \JFactory::getDbo();
		}

		return new $className($db);
	}

	/**
	 * Returns a standard classname, if the class doesn't exist null is returned.
	 *
	 * @param   string  $suffix  The suffix
	 * @param   string  $prefix  The prefix
	 *
	 * @return  string|null  The class name
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	private function getClassName($suffix, $prefix)
	{
		if (!$prefix)
		{
			$prefix = $this->application->getName();
		}

		$className = trim($this->namespace, '\\') . '\\' . ucfirst($prefix) . '\\' . $suffix;

		if (!class_exists($className))
		{
			return null;
		}

		return $className;
	}
}
