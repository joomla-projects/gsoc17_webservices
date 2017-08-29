<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Router;
use Joomla\CMS\Router\Exception\RouteNotFoundException;
use Joomla\Router\Router;

/**
 * Joomla! API Router class
 *
 * @since  __DEPLOY_VERSION__
 */
class ApiRouter extends Router
{
	/**
	 * Creates routes map for CRUD
	 *
	 * @param   string  $baseName    The base name of the component.
	 * @param   string  $controller  The name of the controller that contains CRUD functions.
	 * @param   array   $defaults    An array of default values that are used when the URL is matched.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function createCRUDRoutes($baseName, $controller, $defaults = array())
	{
		$routes = array(
			array(
				'method' => 'GET',
				'pattern' => $baseName,
				'controller' => $controller . '.displayList',
				'defaults' => $defaults
			),
			array(
				'method' => 'GET',
				'pattern' => $baseName . '/:id',
				'controller' => $controller . '.displayItem',
				'rules' => array('id' => '(\d+)'),
				'defaults' => $defaults
			),
			array(
				'method' => 'POST',
				'pattern' => $baseName,
				'controller' => $controller . '.add',
				'defaults' => $defaults
			),
			array(
				'method' => 'PUT',
				'pattern' => $baseName . '/:id',
				'controller' => $controller . '.edit',
				'rules' => array('id' => '(\d+)'),
				'defaults' => $defaults
			),
			array(
				'method' => 'DELETE',
				'pattern' => $baseName . '/:id',
				'controller' => $controller . '.delete',
				'rules' => array('id' => '(\d+)'),
				'defaults' => $defaults
			),
		);
		$this->addRoutes($routes);
	}

	/**
	 * Parse the given route and return the name of a controller mapped to the given route.
	 *
	 * @param   string  $method  Request method to match. One of GET, POST, PUT, DELETE, HEAD, OPTIONS, TRACE or PATCH
	 *
	 * @return  array   An array containing the controller and the matched variables.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \InvalidArgumentException
	 */
	public function parseApiRoute($method = 'GET')
	{
		$method = strtoupper($method);

		if (!array_key_exists($method, $this->routes))
		{
			throw new \InvalidArgumentException(sprintf('%s is not a valid HTTP method.', $method));
		}

		// Get the path from the route and remove and leading or trailing slash.
		$uri = \JUri::getInstance();
		$path = urldecode($uri->getPath());

		/**
		 * In some environments (e.g. CLI we can't form a valid base URL). In this case we catch the exception thrown
		 * by URI and set an empty base URI for further work.
		 * TODO: This should probably be handled better
		 */
		try
		{
			$baseUri = \JUri::base(true);
		}
		catch (\RuntimeException $e)
		{
			$baseUri = '';
		}

		// Remove the base URI path.
		$path = substr_replace($path, '', 0, strlen($baseUri));

		if (!\JFactory::getApplication()->get('sef_rewrite'))
		{
			// Transform the route
			if ($path === 'index.php')
			{
				$path = '';
			}
			else
			{
				$path = str_replace('index.php/', '', $path);
			}
		}

		$query = \JUri::getInstance()->getQuery(true);

		// Iterate through all of the known routes looking for a match.
		foreach ($this->routes[$method] as $rule)
		{
			if (preg_match($rule['regex'], ltrim($path, '/'), $matches))
			{
				// If we have gotten this far then we have a positive match.
				$vars = $rule['defaults'];

				foreach ($rule['vars'] as $i => $var)
				{
					$vars[$var] = $matches[$i + 1];
				}

				$controller = preg_split("/[.]+/", $rule['controller']);
				$vars       = array_merge($vars, $query);

				return [
					'controller' => $controller[0],
					'task'       => $controller[1],
					'vars'       => $vars
				];
			}
		}

		throw new RouteNotFoundException(sprintf('Unable to handle request for route `%s`.', $path));
	}
}
