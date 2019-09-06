<?php
/**
 * @package     Joomla.API
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Languages\Api\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\ApiController;
use Tobscure\JsonApi\Exception\InvalidParameterException;

/**
 * The strings controller
 *
 * @since  4.0.0
 */
class StringsController extends ApiController
{
	/**
	 * The content type of the item.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $contentType = 'strings';

	/**
	 * The default view for the display method.
	 *
	 * @var    string
	 * @since  3.0
	 */
	protected $default_view = 'strings';

	/**
	 * Search by languages constants
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @throws  InvalidParameterException
	 * @since   4.0.0
	 */
	public function search()
	{
		$data = $this->input->get('data', json_decode($this->input->json->getRaw(), true), 'array');

		if (!isset($data['searchstring']) || !is_string($data['searchstring']))
		{
			throw new InvalidParameterException("Invalid param 'searchstring'");
		}

		if (!isset($data['searchtype']) || !in_array($data['searchtype'], ['constant', 'value']))
		{
			throw new InvalidParameterException("Invalid param 'searchtype'");
		}

		$app = Factory::getApplication();
		$app->input->set('searchstring', $data['searchstring']);
		$app->input->set('searchtype', $data['searchtype']);
		$app->input->set('more', 0);

		$viewType   = $this->app->getDocument()->getType();
		$viewName   = $this->input->get('view', $this->default_view);
		$viewLayout = $this->input->get('layout', 'default', 'string');

		try
		{
			/** @var \Joomla\Component\Languages\Api\View\Strings\JsonApiView $view */
			$view = $this->getView(
				$viewName,
				$viewType,
				'',
				['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]
			);
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException($e->getMessage());
		}

		/** @var \Joomla\Component\Languages\Administrator\Model\StringsModel $model */
		$model = $this->getModel($this->contentType, '', ['ignore_request' => true]);

		if (!$model)
		{
			throw new \RuntimeException('Unable to create the model');
		}

		// Push the model into the view (as default)
		$view->setModel($model, true);

		$view->document = $this->app->getDocument();
		$view->displayList();

		return $this;
	}

	/**
	 * Refresh cache
	 *
	 * @return  static  A \JControllerLegacy object to support chaining.
	 *
	 * @throws \Exception
	 * @since   4.0.0
	 */
	public function refresh()
	{
		/** @var \Joomla\Component\Languages\Administrator\Model\StringsModel $model */
		$model = $this->getModel($this->contentType, '', ['ignore_request' => true]);

		if (!$model)
		{
			throw new \RuntimeException('Unable to create the model');
		}

		$result = $model->refresh();

		if ($result instanceof \Exception)
		{
			throw $result;
		}

		return $this;
	}
}
