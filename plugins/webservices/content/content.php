<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Webservices.Content
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;

/**
 * Webservices adapter for com_content.
 *
 * @since  __DEPLOY_VERSION__
 */
class PlgWebservicesContent extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Registers com_content's API's routes in the application
	 *
	 * @param   object  $router  The API Routing object
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onBeforeApiRoute(&$router)
	{
		$router->createCRUDRoutes('article', 'article', ['component' => 'com_content']);
	}
}
