<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Extension;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Dispatcher\DispatcherInterface;
use Joomla\CMS\Dispatcher\ModuleDispatcherFactoryInterface;
use Joomla\CMS\Dispatcher\ModuleDispatcherInterface;
use Joomla\Input\Input;

/**
 * Access to module specific services.
 *
 * @since  4.0.0
 */
class Module implements ModuleInterface
{
	/**
	 * The dispatcher factory.
	 *
	 * @var ModuleDispatcherFactoryInterface
	 *
	 * @since  4.0.0
	 */
	private $dispatcherFactory;

	/**
	 * Module constructor.
	 *
	 * @param   ModuleDispatcherFactoryInterface  $dispatcherFactory  The dispatcher factory
	 *
	 * @since   4.0.0
	 */
	public function __construct(ModuleDispatcherFactoryInterface $dispatcherFactory)
	{
		$this->dispatcherFactory = $dispatcherFactory;
	}

	/**
	 * Returns the dispatcher for the given application, module and input.
	 *
	 * @param   \stdClass                $module       The module
	 * @param   CMSApplicationInterface  $application  The application
	 * @param   Input                    $input        The input object, defaults to the one in the application
	 *
	 * @return  DispatcherInterface
	 *
	 * @since   4.0.0
	 */
	public function getDispatcher(\stdClass $module, CMSApplicationInterface $application, Input $input = null): DispatcherInterface
	{
		return $this->dispatcherFactory->createDispatcher($module, $application, $input);
	}
}
