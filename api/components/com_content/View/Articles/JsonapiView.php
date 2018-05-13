<?php
/**
 * @package     Joomla.API
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Content\Api\View\Articles;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\ListJsonView;
use Joomla\Component\Content\Api\Serializer\ItemSerializer;

/**
 * Override class for a Joomla Json List View
 *
 * Class holding methods for displaying presentation data.
 *
 * @since  __DEPLOY_VERSION__
 */
class JsonapiView extends ListJsonView
{
	public function __construct($config = array())
	{
		$this->serializer = new ItemSerializer;

		parent::__construct($config);
	}
}
