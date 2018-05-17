<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\Error\Renderer;
use Joomla\CMS\Error\JsonApi\AuthenticationFailedExceptionHandler;
use Joomla\CMS\Error\JsonApi\InvalidRouteExceptionHandler;
use Joomla\CMS\Error\JsonApi\NotAcceptableExceptionHandler;
use Joomla\CMS\Error\JsonApi\NotAllowedExceptionHandler;
use Joomla\CMS\Error\JsonApi\ResourceNotFoundExceptionHandler;
use Tobscure\JsonApi\ErrorHandler;
use Tobscure\JsonApi\Exception\Handler\FallbackExceptionHandler;
use Tobscure\JsonApi\Exception\Handler\InvalidParameterExceptionHandler;

/**
 * JSON error page renderer
 *
 * @since  4.0
 */
class JsonapiRenderer extends JsonRenderer
{
	/**
	 * The format (type) of the error page
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $type = 'jsonapi';

	/**
	 * Render the error page for the given object
	 *
	 * @param   \Throwable  $error  The error object to be rendered
	 *
	 * @return  string
	 *
	 * @since   4.0
	 */
	public function render(\Throwable $error): string
	{
		$errors = new ErrorHandler;

		$errors->registerHandler(new InvalidRouteExceptionHandler);
		$errors->registerHandler(new AuthenticationFailedExceptionHandler);
		$errors->registerHandler(new NotAcceptableExceptionHandler);
		$errors->registerHandler(new NotAllowedExceptionHandler);
		$errors->registerHandler(new InvalidParameterExceptionHandler);
		$errors->registerHandler(new ResourceNotFoundExceptionHandler);
		$errors->registerHandler(new FallbackExceptionHandler(JDEBUG));

		$response = $errors->handle($error);

		$this->getDocument()->setErrors($response->getErrors());

		if (ob_get_contents())
		{
			ob_end_clean();
		}

		return $this->getDocument()->render();
	}
}
