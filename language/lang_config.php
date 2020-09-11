<?php
/**
 * English language file for the Library plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
use Library\MO;


/** Language strings for the plugin configuration section */
$LANG_configsections['library'] = array(
    'label' => MO::_('Library'),
    'title' => MO::_('Library Configuration'),
);

/** Language strings for the field names in the config section */
$LANG_confignames['library'] = array(
    'daysonhold'    => MO::_('Days to keep items on hold for waitlisted users'),
    'order'         => MO::_('Default sort order for item display'),
    'items_per_page' => MO::_('Items to show per page'),
    'use_css_menus' => MO::_('CSS Menus? DEPRECATED'),
    'max_images'    => MO::_('Max number of item images'),
    'max_thumb_size' => MO::_('Max Thumbnail Dimension (px)'),
    'img_max_width' => MO::_('Max Image Width (px)'),
    'img_max_height' => MO::_('Max Image Height (px)'),
    'max_image_size' => MO::_('Max. Product Image Size'),
    'ena_comments'  => MO::_('Enable Comments?'),
    'ena_ratings'   => MO::_('Enable Ratings?'),
    'leftblocks'    => MO::_('Enable Left Blocks?'),
    'rightblocks'   => MO::_('Enable Right Blocks'),
    'maxcheckout'   => MO::_('Maximum number of days an item may be checked out'),
    'displayblocks'  => MO::_('Display glFusion Blocks'),
    'menuitem'      => MO::_('Show on user menu?'),
    'grp_librarians' => MO::_('Librarian Group'),
    'def_group_id'  => MO::_('Default Access Group'),
    'notify_checkout' => MO::_('Notify Librarians on item reservation?'),
    'lookup_method' => MO::_('ISBN Lookup Method'),
    'max_wait_items' => MO::_('Max Waitlist Items'),
    'def_checkout_limit' => MO::_('Max Checkouts Allowed'),
);

/** Language strings for the subgroup names in the config section */
$LANG_configsubgroups['library'] = array(
    'sg_main' => MO::_('Main Settings'),
);

/** Language strings for the field set names in the config section */
$LANG_fs['library'] = array(
    'fs_main'   => MO::_('General Settings'),
    'fs_paths'  => MO::_('Images and Paths'),
    'fs_notifications' => MO::_('Notifications'),
    'fs_limits' => MO::_('Limits'),
);

/**
 * Language strings for the selection option names in the config section
 */
$LANG_configselects['library'] = array(
    0 => array(
        MO::_('True') => 1,
        MO::_('False') => 0,
    ),
    1 => array(
        MO::_('True') => TRUE,
        MO::_('False') => FALSE,
    ),
    2 => array(
        MO::_('Yes') => 1,
        MO::_('No') => 0,
    ),
    3 => array(
        MO::_('Never') => 0, 
        MO::_('Always') => 1,
        MO::_('Logged-in Users') => 2,
    ),
    5 => array(
        MO::_('Name') => 'name',
        MO::_('Price') => 'price',
        MO::_('Product ID') => 'id',
    ),
    6 => array(
        MO::_('Always') => 2,
        MO::_('Physical Items Only') => 1,
        MO::_('Never') => 0,
    ),
    12 => array(
        MO::_('No access') => 0,
        MO::_('Read-Only') => 2,
        MO::_('Read-Write') => 3,
    ),
    13 => array(
        MO::_('None') => 0,
        MO::_('Left') => 1,
        MO::_('Right') => 2,
        MO::_('Both') => 3,
    ),
    14 => array(
        MO::_('-None-') => '',
        MO::_('OpenLibrary.org') => 'openlib',
        MO::_('Astore Plugin') => 'astore',
    ),
);

?>
