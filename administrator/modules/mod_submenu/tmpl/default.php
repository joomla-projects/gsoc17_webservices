<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_submenu
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

HTMLHelper::_('script', 'com_cpanel/admin-system-loader.js', ['version' => 'auto', 'relative' => true]);

/** @var  \Joomla\CMS\Menu\MenuItem  $root */
?>

<div class="com-cpanel-system">
	<?php foreach ($root->getChildren() as $child) : ?>
        <?php if ($child->hasChildren()) : ?>
		<div class="com-cpanel-system__category">
			<h2 class="com-cpanel-system__header">
				<span class="fa fa-<?php echo $child->icon; ?>" aria-hidden="true"></span>
				<?php echo Text::_($child->title); ?>
			</h2>
			<ul class="list-group list-group-flush">
				<?php foreach ($child->getChildren() as $item) : ?>
					<li class="list-group-item">
						<a href="<?php echo $item->link; ?>"><?php echo Text::_($item->title); ?>
                            <?php if ($item->ajaxbadge) : ?>
                                <span class="fa fa-spin fa-spinner pull-right mt-1 system-counter" data-url="<?php echo $item->ajaxbadge; ?>"></span>
                            <?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
        <?php endif; ?>
	<?php endforeach; ?>
</div>
