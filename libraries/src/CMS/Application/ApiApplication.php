<?php
/**
 * @package    Joomla.API
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\Application;

defined('JPATH_PLATFORM') or die;

use Joomla\Application\Web\WebClient;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Router\ApiRouter;
use Joomla\DI\Container;
use Joomla\Registry\Registry;

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

		// Mark afterInitialise in the profiler.
		JDEBUG ? $this->profiler->mark('afterInitialise') : null;

		// Route the application
		$this->route();

		// Mark afterApiRoute in the profiler.
		JDEBUG ? $this->profiler->mark('afterApiRoute') : null;

		// Dispatch the application
		$this->dispatch();

		// Mark afterDispatch in the profiler.
		JDEBUG ? $this->profiler->mark('afterDispatch') : null;
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
	 * @param   array  $options  An optional argument to enable CORS. (Temporary)
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	protected function respond($options = array())
	{
		$this->setBody(json_encode($this->getBody()));

		// Set the Joomla! API signature
		$this->setHeader('X-Powered-By', 'JoomlaAPI/1.0', true);

		if (array_key_exists('cors', $options))
		{
			// Enable CORS (Cross-origin resource sharing)
			$this->setHeader('Access-Control-Allow-Origin', '*', true);
			$this->setHeader('Access-Control-Allow-Headers', 'Authorization');
		}

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
		$uri    = \JUri::getInstance();
		$router = $this->getApiRouter();

		// Trigger the onBeforeApiRoute event.
		PluginHelper::importPlugin('webservices');
		$this->triggerEvent('onBeforeApiRoute', &$router);

		$route = $router->parseRoute($uri::current(), $this->input->getMethod());

		$this->input->set('option', $route['vars']['component']);
		$this->input->set('controller', $route['controller']);
		$this->input->set('task', $route['task']);

		foreach ($route['vars'] as $key => $value)
		{
			if ($key !== 'component')
			{
				if ($this->input->getMethod() === 'POST')
				{
					$this->input->post->set($key, $value);
				}
				else
				{
					$this->input->set($key, $value);
				}
			}
		}
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

	/**
	 * Dispatch the application
	 *
	 * @param   string  $component  The component which is being rendered.
	 *
	 * @return  void
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function dispatch($component = null)
	{
		$app = \JFactory::getApplication();

		// Get the component if not set.
		if (!$component)
		{
			$component = $this->input->get('option', null);
		}

		// Load the document to the API
		$this->loadDocument();

		// Set up the params
		$document = \JFactory::getDocument();

		// Register the document object with \JFactory
		\JFactory::$document = $document;

		$contents = ComponentHelper::renderComponent($component);
		$document->setBuffer($contents, 'component');

		// Trigger the onAfterDispatch event.
		PluginHelper::importPlugin('system');
		$this->triggerEvent('onAfterDispatch');
	}
}
