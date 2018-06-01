<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

 namespace Joomla\CMS\Console;

 defined('JPATH_PLATFORM') or die;

 use Joomla\CMS\Http\Http;
 use Joomla\CMS\Language\Text;
 use Joomla\Console\AbstractCommand;
 use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A command line script for api testing.
 *
 * This is a command-line script to help with api tests.
 *
 * Called with no arguments: php api.php
 *
 * @since  4.0.0
 */
class ApiTestCommand extends AbstractCommand
{
	/**
	 * The url to test api.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	private $url = 'http://localhost/api/index.php/article/1';

	/**
	 * The http method to use
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	private $method = 'get';

	/**
	 * The Joomla! username
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	private $username = 'admin';

	/**
	 * The Joomla! password
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	private $password = '';

	/**
	 * Entry point for API CLI script
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function execute(): int
	{
		// Http instance
		$http = new Http();

		$symfonyStyle = new SymfonyStyle($this->getApplication()->getConsoleInput(), $this->getApplication()->getConsoleOutput());
		$symfonyStyle->title('Running Joomla! API Tester');

		// Send request
		switch ($this->method) {
			case 'post':
			case 'put':
				$response = $http->post($this->url, $resource, $this->getRestHeaders());
				break;

			case 'get':
			case 'patch':
			case 'delete':
			default:
				$response = $http->get($this->url, $this->getRestHeaders());
				break;
		}

		$print = json_encode(json_decode($response->body), JSON_PRETTY_PRINT);
		$symfonyStyle->writeln($print);

		// Print a blank line at the end.
		$symfonyStyle->success('Joomla! API Test finished');

		return 0;
	}

	/**
	 * Get the rest headers to send
	 *
	 * @param   bool    $form  True if we like to use POST
	 *
	 * @return  array   The RESTful headers
	 *
	 * @since   4.0.0
	 */
	protected function getRestHeaders($form = false)
	{
		// Encode the headers for REST
		$authorization = base64_encode($this->username . ":" . $this->password);

		$headers = array(
			'Authorization' => 'Basic ' . $authorization
		);

		$headers['Content-Type'] = "application/vnd.api+json";
		$headers['Accept'] = "text/*, text/html, text/html;level=1, */*";

		if ($form === true)
		{
			$headers['Content-Type'] = 'application/x-www-form-urlencoded';
		}

		return $headers;
	}

	/**
	 * Initialise the command.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function initialise()
	{
		$this->setName('api:test');
		$this->setDescription('Test the Joomla! API request');
		$this->setHelp(
<<<EOF
The <info>%command.name%</info> command run a http request to Joomla! API to test and debug it.

<info>php %command.full_name%</info>
EOF
		);
	}
}
