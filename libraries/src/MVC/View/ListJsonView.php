<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\MVC\View;

defined('_JEXEC') or die;

use Joomla\CMS\Serializer\YmlSerializer;
use Tobscure\JsonApi\Collection;

/**
 * Base class for a Joomla Json List View
 *
 * Class holding methods for displaying presentation data.
 *
 * @since  __DEPLOY_VERSION__
 */
class ListJsonView extends JsonView
{
	/**
	 * The content type
	 *
	 * @var  string
	 */
	protected $type;

	/**
	 * The items object
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The model state
	 *
	 * @var  \JObject
	 */
	protected $state;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  A named configuration array for object construction.
	 *                          contentType: the name (optional) of the content type to use for the serialization
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function __construct($config = array())
	{
		if (array_key_exists('contentType', $config))
		{
			$this->type = $config['contentType'];
		}

		parent::__construct($config);
	}

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		$model = $this->getModel();

		$this->items = $model->getItems();
		$this->state = $model->get('State');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new \JViewGenericdataexception(implode("\n", $errors), 500);
		}

		if ($this->type === null)
		{
			throw new \RuntimeException('Content type missing');
		}

		$serializer = new YmlSerializer($this->type, JPATH_COMPONENT . '/Serializer/' . $this->type . '.yml');
		$element = new Collection($this->items, $serializer);

		$this->document->setData($element);
		$this->document->addLink('self', \JUri::current());
		$this->document->render();
	}
}
