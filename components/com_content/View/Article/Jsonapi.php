<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\Component\Content\Site\View\Article;

defined('_JEXEC') or die;

use Joomla\CMS\View\AbstractView;

/**
 * HTML Article View class for the Content component
 *
 * @since  1.5
 */
class Jsonapi extends AbstractView
{
	/**
	 * The active document object
	 *
	 * @var    \JDocumentJsonapi
	 * @since  3.0
	 */
	public $document;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 */
	public function display($tpl = null)
	{
		$this->document->document->addLink('self', \JUri::current());
	}
}
