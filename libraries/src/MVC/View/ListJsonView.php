<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\MVC\View;

defined('_JEXEC') or die;

use Joomla\CMS\Document\JsonapiDocument;
use Joomla\CMS\MVC\Model\ListModel;
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
	 * The active document object (Redeclared for typehinting)
	 *
	 * @var    JsonapiDocument
	 * @since  3.0
	 */
	public $document;

	/**
	 * The content type
	 *
	 * @var  string
	 */
	protected $type;

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
		/** @var ListModel $model */
		$model = $this->getModel();

		$items = $model->getItems();
		$pagination = $model->getPagination();

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

		// Set up links for pagination
		$currentUrl = \JUri::getInstance();
		$currentPageDefaultInformation = array('offset' => $pagination->limitstart, 'limit' => $pagination->limit);
		$currentPageQuery = $currentUrl->getVar('page', $currentPageDefaultInformation);
		$totalPagesAvailable = ($pagination->pagesTotal * $pagination->limit);

		$firstPage = clone $currentUrl;
		$firstPageQuery = $currentPageQuery;
		$firstPageQuery['offset'] = 0;
		$firstPage->setVar('page', $firstPageQuery);

		$nextPage = clone $currentUrl;
		$nextPageQuery = $currentPageQuery;
		$nextOffset = $currentPageQuery['offset'] + $pagination->limit;
		$nextPageQuery['offset'] = ($nextOffset > ($totalPagesAvailable * $pagination->limit)) ? $totalPagesAvailable - $pagination->limit : $nextOffset;
		$nextPage->setVar('page', $nextPageQuery);

		$previousPage = clone $currentUrl;
		$previousPageQuery = $currentPageQuery;
		$previousOffset = $currentPageQuery['offset'] - $pagination->limit;
		$previousPageQuery['offset'] = $previousOffset >= 0 ? $previousOffset : 0;
		$previousPage->setVar('page', $previousPageQuery);

		$lastPage = clone $currentUrl;
		$lastPageQuery = $currentPageQuery;
		$lastPageQuery['offset'] = $totalPagesAvailable - $pagination->limit;
		$lastPage->setVar('page', $lastPageQuery);

		// Set the data into the document and render it
		$this->document->addMeta('total-pages', $pagination->pagesTotal)
			->setData(new Collection($items, $serializer))
			->addLink('self', (string) $currentUrl)
			->addLink('first', (string) $firstPage)
			->addLink('next', (string) $nextPage)
			->addLink('previous', (string) $previousPage)
			->addLink('last', (string) $lastPage);

		return $this->document->render();
	}
}
