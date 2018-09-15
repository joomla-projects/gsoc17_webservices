<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Finder\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Base controller class for Finder.
 *
 * @since  2.5
 */
class DisplayController extends BaseController
{
	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $default_view = 'index';

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link \JFilterInput::clean()}.
	 *
	 * @return  static   A Controller object to support chaining.
	 *
	 * @since	2.5
	 */
	public function display($cachable = false, $urlparams = array())
	{
		$view   = $this->input->get('view', 'index', 'word');
		$layout = $this->input->get('layout', 'index', 'word');
		$filterId = $this->input->get('filter_id', null, 'int');

		// Check for edit form.
		if ($view === 'filter' && $layout === 'edit' && !$this->checkEditId('com_finder.edit.filter', $filterId))
		{
			// Somehow the person just went to the form - we don't allow that.
			$this->setMessage(\JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $f_id), 'error');
			$this->setRedirect(\JRoute::_('index.php?option=com_finder&view=filters', false));

			return false;
		}

		return parent::display();
	}
}
