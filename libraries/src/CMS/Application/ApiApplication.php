<?php
/**
 * @package    Joomla.API
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\Application;

defined('_JEXEC') or die;

use Joomla\Application\Web\WebClient;
use Joomla\DI\Container;
use Joomla\Registry\Registry;
use Joomla\CMS\Router\ApiRouter;

/**
 * Joomla! API Application class
 *
 * @since  __DEPLOY_VERSION__
 */
final class ApiApplication extends CMSApplication
{
	/**
	 * Class constructor.
	 *
	 * @param   \JInput    $input      An optional argument to provide dependency injection for the application's input
	 *                                 object.  If the argument is a JInput object that object will become the
	 *                                 application's input object, otherwise a default input object is created.
	 * @param   Registry   $config     An optional argument to provide dependency injection for the application's config
	 *                                 object.  If the argument is a Registry object that object will become the
	 *                                 application's config object, otherwise a default config object is created.
	 * @param   WebClient  $client     An optional argument to provide dependency injection for the application's client
	 *                                 object.  If the argument is a WebClient object that object will become the
	 *                                 application's client object, otherwise a default client object is created.
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct(\JInput $input = null, Registry $config = null, WebClient $client = null, Container $container = null)
	{
		// Register the application name
		$this->name = 'japi';

		// Register the client ID
		$this->clientId = 3;

		// Set format to JSON (uses JDocumentJson)
		$this->input->set('format', $input->get('format', 'json'));

		// Execute the parent constructor
		parent::__construct($input, $config, $client, $container);

		// Set the root in the URI based on the application name
		\JUri::root(null, str_ireplace('/' . $this->getName(), '', \JUri::base(true)));
	}


	/**
	 * Method to run the application routines.
	 *
	 * Most likely you will want to instantiate a controller and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function doExecute()
	{
		// Initialise the application
		$this->initialiseApp();

	}

	/**
	 * Rendering is the process of pushing the document buffers into the template
	 * placeholders, retrieving data from the document and pushing it into
	 * the application response buffer.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 *
	 * @note    Rendering should be overridden to get rid of the theme files.
	 */
	protected function render()
	{
		// Render the document
		$this->setBody($this->document->render($this->allowCache()));
	}

	/**
	 * Method to send the application response to the client.  All headers will be sent prior to the main application output data.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function respond()
	{
		$this->setBody(json_encode($this->getBody()));

		// Parent function can be overridden later on for debugging.
		parent::respond();
	}

	/**
	 * Gets the name of the current template.
	 *
	 * @param   boolean  $params  True to return the template parameters
	 *
	 * @return  string
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getTemplate($params = false)
	{
		// The API application should not need to use a template
		return 'system';
	}

	/**
	 * Route the application.
	 *
	 * Routing is the process of examining the request environment to determine which
	 * component should receive the request. The component optional parameters
	 * are then set in the request object to be processed when the application is being
	 * dispatched.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function route()
	{
		$uri = \JUri::getInstance();
		$router = static::getRouter();

		// Trigger the onBeforeApiRoute event.
		PluginHelper::importPlugin('webservices');
		$this->triggerEvent('onBeforeApiRoute', &$router);
	}

	/**
	 * Returns the application Router object.
	 *
	 * @return  ApiRouter
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getApiRouter()
	{
		return JFactory::getContainer()->get('ApiRouter');
	}
}
