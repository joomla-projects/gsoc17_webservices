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
	 * Router instances container.
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	protected static $instances = array();

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
}
