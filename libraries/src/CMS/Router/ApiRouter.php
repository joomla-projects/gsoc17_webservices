<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\ApiRouter;
use Joomla\Router\Router;

/**
 * Joomla! API Router class
 *
 * @since  __DEPLOY_VERSION__
 */
class ApiRouter extends Router
{
	/**
	 * Router instances container.
	 *
	 * @var    array
	 * @since  DEPLOY_VERSION
	 */
	protected static $instances = array();

	/**
	 * Constructor.
	 *
	 * @param   array  $maps  An optional array of route maps
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(array $maps = [])
	{
		parent::__construct($maps);
	}

	/**
	 * Returns an ApiRouter object, only creating it if it
	 * doesn't already exist.
	 *
	 * @param   string  $name     The name (optional) of the ApiRouter class to instantiate.
	 * @param   array   $options  An associative array of options
	 *
	 * @return  ApiRouter  An ApiRouter object.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \RuntimeException
	 */
	public static function getInstance($name, $options = array())
	{
		if (empty(self::$instances[$name]))
		{
			// Create a Router object
			$classname = 'ApiRouter' . ucfirst($name);

			if (!class_exists($classname))
			{
				throw new \RuntimeException(\JText::sprintf('JLIB_APPLICATION_ERROR_ROUTER_LOAD', $name), 500);
			}

			// Check for a possible service from the container otherwise manually instantiate the class
			if (\JFactory::getContainer()->exists($classname))
			{
				self::$instances[$name] = \JFactory::getContainer()->get($classname);
			}
			else
			{
				self::$instances[$name] = new $classname($options);
			}
		}

		return self::$instances[$name];
	}

	/**
	 * Creates routes map for CRUD
	 *
	 * @param   string  $baseName   The base name of the component.
	 * @param   string  $controller   The name of the controller that contains CRUD functions.
	 * @param   array   $defaults    An array of default values that are used when the URL is matched.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function createCRUDRoutes($baseName, $controller, $defaults = array())
	{
		$routes = array(
			array('method' => 'GET', 'pattern' => $baseName, 'controller' => $controller . '@display', 'defaults' => $defaults),
			array('method' => 'GET', 'pattern' => $baseName . '/new', 'controller' => $controller . '@add', 'defaults' => $defaults),
			array('method' => 'GET', 'pattern' => $baseName . '/:id', 'controller' => $controller . '@display', 'rules' => array('id' => '(\d+)'), 'defaults' => $defaults),
			array('method' => 'GET', 'pattern' => $baseName . '/:id/edit', 'controller' => $controller . '@edit', 'rules' => array('id' => '(\d+)'), 'defaults' => $defaults),
			array('method' => 'POST', 'pattern' => $baseName, 'controller' => $controller . '@add', 'defaults' => $defaults),
			array('method' => 'PUT', 'pattern' => $baseName . '/:id', 'controller' => $controller . '@edit', 'rules' => array('id' => '(\d+)'), 'defaults' => $defaults),
			array('method' => 'DELETE', 'pattern' => $baseName . '/:id', 'controller' => $controller . '@delete', 'rules' => array('id' => '(\d+)'), 'defaults' => $defaults),
		);
		$this->addRoutes($routes);
	}
}
