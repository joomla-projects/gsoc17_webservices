<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\Error\JsonApi;

use Exception;
use Joomla\CMS\Router\Exception\RouteNotFoundException;
use Tobscure\JsonApi\Exception\Handler\ExceptionHandlerInterface;
use Tobscure\JsonApi\Exception\Handler\ResponseBag;

class InvalidRouteExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function manages(Exception $e)
    {
        return $e instanceof RouteNotFoundException;
    }

	/**
	 * Handle the provided exception.
	 *
	 * @param   Exception  $e  The exception being handled
	 *
	 * @return  \Tobscure\JsonApi\Exception\Handler\ResponseBag
	 *
	 * @since  __DEPLOY_VERSION__
	 */
    public function handle(Exception $e)
    {
        $status = 404;
        $error = ['title' => 'Resource not found'];

        $code = $e->getCode();
        if ($code) {
            $error['code'] = $code;
        }

        return new ResponseBag($status, [$error]);
    }
}
