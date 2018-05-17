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
	 * Sample curl requests. Username: admin. Password: 123.
	 *
	 * curl -H 'Authorization: Basic YWRtaW46MTIz' -X POST -H "Content-Type: application/json" http://localhost/~george/joomla-cms/api/index.php/article -d '{"title": "Just for you", "catid": 64, "articletext": "My text", "metakey": "", "metadesc": "", "language": "*", "alias": "tobias"}'
	 * curl -H 'Authorization: Basic YWRtaW46MTIz' -X PUT -H "Content-Type: application/json" http://localhost/~george/joomla-cms/api/index.php/article/111 -d '{"title": "Just for you part 2", "catid": 64}'
	 * curl -H 'Authorization: Basic YWRtaW46MTIz' -X GET http://localhost/~george/joomla-cms/api/index.php/article/111
	 * curl -H 'Authorization: Basic YWRtaW46MTIz' -X DELETE http://localhost/~george/joomla-cms/api/index.php/article/111
	 * curl -H 'Authorization: Basic YWRtaW46MTIz' -X GET http://localhost/~george/joomla-cms/api/index.php/article/111
	 * curl -H 'Authorization: Basic YWRtaW46MTIz' -X GET http://localhost/~george/joomla-cms/api/index.php/article
	 */

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
	 * @param   ApiRouter  &$router  The API Routing object
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
