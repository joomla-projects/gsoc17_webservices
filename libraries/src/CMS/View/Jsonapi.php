<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Cms\View;

defined('_JEXEC') or die;

/**
 * HTML Article View class for the Content component
 *
 * @since  __DEPLOY_VERSION__
 */
class Jsonapi extends AbstractView
{
	/**
	 * The active document object
	 *
	 * @var    \JDocumentJsonapi
	 * @since  __DEPLOY_VERSION__
	 */
	public $document;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 * @since   __DEPLOY_VERSION__
	 */
	public function display($tpl = null)
	{
		/** @var \Joomla\Component\Content\Administrator\Model\Article $model */
		$model = $this->getModel();
		$item = $model->getItem();

		if ($item->id === null)
		{
			throw new \RuntimeException('Item not found', 404);
		}

		$this->document->document->addLink('self', \JUri::current());
	}
}
