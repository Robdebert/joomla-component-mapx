<?php

/**
 * @author     Branko Wilhelm <branko.wilhelm@gmail.com>
 * @link       http://www.z-index.net
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @var        XmapViewSitemap $this
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('sortablelist.sortable', 'menueList', 'adminForm', 'asc', false);
?>
<table class="table table-striped" id="menueList">
    <thead>
    <tr>
        <th width="1%" class="nowrap center hidden-phone">
        </th>
        <th width="1%" class="nowrap center">
            <?php echo JHtml::_('grid.checkall'); ?>
        </th>
        <th class="title">
            <?php echo JText::_('JGLOBAL_TITLE'); ?>
        </th>
        <th class="nowrap hidden-phone">
            <?php echo JText::_('COM_XMAP_PRIORITY'); ?>
        </th>
        <th class="nowrap hidden-phone">
            <?php echo JText::_('COM_XMAP_CHANGE_FREQUENCY'); ?>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($this->item->selections as $i => $menu): ?>
        <tr>
            <td class="center hidden-phone">
                <span class="sortable-handler">
                    <i class="icon-menu"></i>
                </span>
            </td>
            <td width="1%" class="center">
                <input type="checkbox" id="cb<?php echo $i; ?>" name="jform[selections][<?php echo $menu['menutype']; ?>][enabled]" value="1" <?php echo $menu['selected'] ? 'checked="checked"' : ''; ?> />
            </td>
            <td class="nowrap has-context">
                <label for="cb<?php echo $i; ?>"><?php echo $this->escape($menu['title']); ?></label>
            </td>
            <td class="nowrap hidden-phone">
                <?php echo JHtml::_('xmap.priorities', 'jform[selections][' . $menu['menutype'] . '][priority]', $menu['priority'], $i); ?>
            </td>
            <td class="nowrap hidden-phone">
                <?php echo JHTML::_('xmap.changefrequency', 'jform[selections][' . $menu['menutype'] . '][changefreq]', $menu['changefreq'], $i); ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>