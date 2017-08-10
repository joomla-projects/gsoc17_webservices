<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\Dispatcher;
use Joomla\CMS\Controller\Controller;

/**
 * Dispatcher class for com_content
 *
 * @since  __DEPLOY_VERSION__
 */
class ContentDispatcher extends Dispatcher
{
	/**
	 * The extension namespace
	 *
	 * @var    string
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $namespace = 'Joomla\\Component\\Content';

	/**
	 * Dispatch a controller task. Redirecting the user if appropriate.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function dispatch()
	{
		JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');
		JLoader::register('ContentHelperQuery', JPATH_SITE . '/components/com_content/helpers/query.php');
		JLoader::register('ContentHelperAssociation', JPATH_SITE . '/components/com_content/helpers/association.php');

		parent::dispatch();
	}

	/**
	 * Get a controller from the component
	 *
	 * @param   string  $name    Controller name
	 * @param   string  $client  Optional client (like Administrator, Site etc.)
	 * @param   array   $config  Optional controller config
	 *
	 * @return  Controller
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getController($name, $client = null, $config = array())
	{
		if ($client === null || strtolower($client) === 'api')
		{
			$client = 'Site';
		}

		return parent::getController($name, $client, $config);
	}
}
