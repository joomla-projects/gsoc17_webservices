<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Router;
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
				'controller' => $controller . '.display',
				'defaults' => $defaults
			),
			array(
				'method' => 'GET',
				'pattern' => $baseName . '/new',
				'controller' => $controller . '.add',
				'defaults' => $defaults
			),
			array(
				'method' => 'GET',
				'pattern' => $baseName . '/:id',
				'controller' => $controller . '.display',
				'rules' => array('id' => '(\d+)'),
				'defaults' => $defaults
			),
			array(
				'method' => 'GET',
				'pattern' => $baseName . '/:id/edit',
				'controller' => $controller . '.edit',
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
	 * @param   string  $route   The route string for which to find and execute a controller.
	 * @param   string  $method  Request method to match. One of GET, POST, PUT, DELETE, HEAD, OPTIONS, TRACE or PATCH
	 *
	 * @return  array   An array containing the controller and the matched variables.
	 *
	 * @since   __DEPLOY_VERSION__
	 * @throws  \InvalidArgumentException
	 */
	public function parseRoute($route, $method = 'GET')
	{
		$method = strtoupper($method);

		if (!array_key_exists($method, $this->routes))
		{
			throw new \InvalidArgumentException(sprintf('%s is not a valid HTTP method.', $method));
		}

		// Get the path from the route and remove and leading or trailing slash.
		$route = trim(parse_url($route, PHP_URL_PATH), ' /');

		// Iterate through all of the known routes looking for a match.
		foreach ($this->routes[$method] as $rule)
		{
			if (preg_match($rule['regex'], $route, $matches))
			{
				// If we have gotten this far then we have a positive match.
				$vars = $rule['defaults'];

				foreach ($rule['vars'] as $i => $var)
				{
					$vars[$var] = $matches[$i + 1];
				}

				$controller = preg_split("/[.]+/", $rule['controller']);

				return [
					'controller' => $controller[0],
					'task'       => $controller[0],
					'vars'       => $vars
				];
			}
		}

		throw new \InvalidArgumentException(sprintf('Unable to handle request for route `%s`.', $route), 404);
	}
}
