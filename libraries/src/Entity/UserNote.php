<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\CMS\Entity;

defined('_JEXEC') or die;

/**
 * Class UserNote
 *
 * @package  Joomla\Component\Users\Administrator\Entitiy
 * @since    __DEPLOY_VERSION__
 */
class UserNote extends \Joomla\Entity\Model
{
	/**
	 * The attributes that should be mutated to dates. Already aliased!
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $dates = array(
		'checked_out_time',
		'publish_up',
		'publish_down',
		'review_time',
		'created_time',
		'modified_time'
	);

	/**
	 * Array with alias for "special" columns such as ordering, hits etc etc
	 *
	 * @var    array
	 * @since  __DEPLOY_VERSION__
	 */
	protected $columnAlias = array(
		'published' => 'state',
		'createdAt' => 'created_time',
		'updatedAt' => 'modified_time'
	);

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = "#__user_notes";
}
