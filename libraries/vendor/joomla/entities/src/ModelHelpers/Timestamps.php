<?php
/**
 * Part of the Joomla GSoC Webservices Project
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Entity\ModelHelpers;

use Carbon\Carbon;

/**
 * Trait Timestamps
 * @package Joomla\Entity\Helpers
 * @since 1.0
 */
trait Timestamps
{
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var boolean
	 */
	public $timestamps = true;

	/**
	 * Update the model's update timestamp.
	 *
	 * @return boolean
	 */
	public function touch()
	{
		if (! $this->usesTimestamps())
		{
			return false;
		}

		$this->updateTimestamps();

		return $this->persist();
	}

	/**
	 * Update the creation and update timestamps.
	 *
	 * @return void
	 */
	protected function updateTimestamps()
	{
		$time = $this->freshTimestamp();

		if ($this->exists && $this->getColumnAlias('updatedAt') && !$this->isDirty('updatedAt'))
		{
			$this->updatedAt = $time;
		}

		if (!$this->exists && $this->getColumnAlias('createdAt') && !$this->isDirty('createdAt'))
		{
			$this->createdAt = $time;
		}
	}

	/**
	 * Get a fresh timestamp for the model.
	 *
	 * @return \Carbon\Carbon
	 */
	public function freshTimestamp()
	{
		return new Carbon;
	}

	/**
	 * Get a fresh timestamp for the model.
	 *
	 * @return string
	 */
	public function freshTimestampString()
	{
		return $this->fromDateTime($this->freshTimestamp());
	}

	/**
	 * Determine if the model uses timestamps.
	 *
	 * @return boolean
	 */
	public function usesTimestamps()
	{
		return $this->timestamps;
	}
}
