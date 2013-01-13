<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	mod_popular
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
?>
<table class="adminlist">
	<thead>
		<tr>
			<th>
				<?php echo JText::_('ZOOMBIE'); ?>
			</th>
			<th>
				<strong><?php echo JText::_('LAST'); ?></strong>
			</th>
			<th>
				<strong><?php echo JText::_('EXPECTED');?>
				</strong>
			</th>
		</tr>
	</thead>
<?php if (count($list)) : ?>
	<tbody>
	<?php foreach ($list as $i=>$item) : ?>
		<tr>
			<th scope="row">


			    <?php echo htmlspecialchars($item->pname, ENT_QUOTES, 'UTF-8');?>

			</th>

			<td class="center">
				<?php echo JHtml::_('date', $item->last, 'Y-m-d H:i:s');?>
			</td>
			<td class="center">
				<?php echo JHtml::_('date', $item->next, 'Y-m-d H:i:s');?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
<?php else : ?>
	<tbody>
		<tr>
			<td colspan="3">
				<p class="noresults"><?php echo JText::_('MOD_POPULAR_NO_MATCHING_RESULTS');?></p>
			</td>
		</tr>
	</tbody>
<?php endif; ?>
</table>
