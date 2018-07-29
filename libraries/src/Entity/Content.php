<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Entity;

use Joomla\Entity\Relations\Relation;

defined('JPATH_PLATFORM') or die;

/**
 * Entity Model for an Article.
 *
 * @since  __DEPLOY_VERSION__
 */
class Content extends CMSModel
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = '#__content';

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [
		'attribs'  => 'array',
		'metadata' => 'array',
		'images'   => 'array',
		'urls'     => 'array'
	];

	/**
	 * The attributes that should be mutated to dates. Already aliased!
	 *
	 * @var array
	 */
	protected $dates = [
		'created',
		'modified',
		'checked_out_time',
		'publish_up',
		'publish_down'
	];

	/**
	 * Array with alias for "special" columns such as ordering, hits etc etc
	 *
	 * @var    array
	 */
	protected $columnAlias = [
		'createdAt' => 'created',
		'updatedAt' => 'modified',
		'published' => 'state'
	];

	/**
	 * Get the category for the current article.
	 * @return Relation
	 */
	public function category()
	{
		return $this->belongsTo('Joomla\CMS\Entity\Category', 'category', 'catid');
	}

	/**
	 * Get the featured for the current article.
	 * @return Relation
	 */
	public function feature()
	{
		return $this->hasOne('Joomla\CMS\Entity\Featured');
	}

	/**
	 * Get the voting for the current article.
	 * @return Relation
	 */
	public function rating()
	{
		return $this->hasOne('Joomla\CMS\Entity\Rating');
	}

	/**
	 * Get the author for the current article.
	 * @return Relation
	 */
	public function author()
	{
		return $this->belongsTo('Joomla\CMS\Entity\User', 'author', 'created_by');
	}

	/**
	 * Get the author for the current article.
	 * @return Relation
	 */
	public function editor()
	{
		return $this->hasOne('Joomla\CMS\Entity\User', 'id', 'checked_out');
	}

	/**
	 * Get the featured for the current article.
	 * @return Relation
	 */
	public function lang()
	{
		return $this->hasOne('Joomla\CMS\Entity\Language', 'lang_code', 'language');
	}

	/**
	 * Get the view level for the current article.
	 * @return Relation
	 */
	public function viewLevel()
	{
		return $this->hasOne('Joomla\CMS\Entity\ViewLevel', 'id', 'access');
	}

	/**
	 * Mutation for articletext
	 * @return mixed|string
	 */
	public function getArticletextAttribute()
	{
		return trim($this->fulltext) != '' ? $this->introtext . '<hr id="system-readmore" />' . $this->fulltext : $this->introtext;
	}

	/**
	 * Mutation for articletext
	 *
	 * @param   string  $value  intotext + fulltext
	 * @return void
	 */
	public function setArticletextAttribute($value)
	{
		$value = explode('<hr id="system-readmore" />', $value, 2);

		$this->introtext = $value[0];
		$this->fulltext = (array_key_exists(1, $value)) ? $value[1] : '';
	}

	/**
	 * Mutation for association
	 * @return boolean
	 * @todo this needs refactoring
	 */
	public function getAssociationAttribute()
	{
		// Cannot group by on relations!
		$associationsGroupBy = [
			'a.id',
			'a.title',
			'a.alias',
			'a.checked_out',
			'a.checked_out_time',
			'a.state',
			'a.access',
			'a.created',
			'a.created_by',
			'a.created_by_alias',
			'a.modified',
			'a.ordering',
			'a.featured',
			'a.language',
			'a.hits',
			'a.publish_up',
			'a.publish_down',
			'a.catid'
		];

		$query = $this->db->getQuery(true);

		$query->select('COUNT(asso2.id)>1 as association')
			->from("#__content as a")
			->join('LEFT', '#__associations AS asso ON asso.id = a.id AND asso.context=' . $this->getDb()->quote('com_content.item'))
			->join('LEFT', '#__associations AS asso2 ON asso2.key = asso.key')
			->group($this->getDb()->quoteName($associationsGroupBy));

		return $this->db->setQuery($query)->loadResult();
	}
}
