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


/** Language strings for the plugin configuration section */
$LANG_configsections['library'] = array(
    'label' => \Library\_('Library'),
    'title' => \Library\_('Library Configuration'),
);

/** Language strings for the field names in the config section */
$LANG_confignames['library'] = array(
    'daysonhold'    => \Library\_('Days to keep items on hold for waitlisted users'),
    'order'         => \Library\_('Default sort order for item display'),
    'items_per_page' => \Library\_('Items to show per page'),
    'use_css_menus' => \Library\_('CSS Menus? DEPRECATED'),
    'max_images'    => \Library\_('Max number of item images'),
    'max_thumb_size' => \Library\_('Max Thumbnail Dimension (px)'),
    'img_max_width' => \Library\_('Max Image Width (px)'),
    'img_max_height' => \Library\_('Max Image Height (px)'),
    'max_image_size' => \Library\_('Max. Product Image Size'),
    'ena_comments'  => \Library\_('Enable Comments?'),
    'ena_ratings'   => \Library\_('Enable Ratings?'),
    'leftblocks'    => \Library\_('Enable Left Blocks?'),
    'rightblocks'   => \Library\_('Enable Right Blocks'),
    'maxcheckout'   => \Library\_('Maximum number of days an item may be checked out'),
    'displayblocks'  => \Library\_('Display glFusion Blocks'),
    'menuitem'      => \Library\_('Show on user menu?'),
    'grp_librarians' => \Library\_('Librarian Group'),
    'def_group_id'  => \Library\_('Default Access Group'),
    'notify_checkout' => \Library\_('Notify Librarians on item reservation?'),
    'lookup_method' => \Library\_('ISBN Lookup Method'),
);

/** Language strings for the subgroup names in the config section */
$LANG_configsubgroups['library'] = array(
    'sg_main' => \Library\_('Main Settings'),
);

/** Language strings for the field set names in the config section */
$LANG_fs['library'] = array(
    'fs_main'   => \Library\_('General Settings'),
    'fs_paths'  => \Library\_('Images and Paths'),
    'fs_notifications' => \Library\_('Notifications'),
);

/**
 * Language strings for the selection option names in the config section
 */
$LANG_configselects['library'] = array(
    0 => array(
        \Library\_('True') => 1,
        \Library\_('False') => 0,
    ),
    1 => array(
        \Library\_('True') => TRUE,
        \Library\_('False') => FALSE,
    ),
    2 => array(
        \Library\_('Yes') => 1,
        \Library\_('No') => 0,
    ),
    3 => array(
        \Library\_('Never') => 0, 
        \Library\_('Always') => 1,
        \Library\_('Logged-in Users') => 2,
    ),
    5 => array(
        \Library\_('Name') => 'name',
        \Library\_('Price') => 'price',
        \Library\_('Product ID') => 'id',
    ),
    6 => array(
        \Library\_('Always') => 2,
        \Library\_('Physical Items Only') => 1,
        \Library\_('Never') => 0,
    ),
    12 => array(
        \Library\_('No access') => 0,
        \Library\_('Read-Only') => 2,
        \Library\_('Read-Write') => 3,
    ),
    13 => array(
        \Library\_('None') => 0,
        \Library\_('Left') => 1,
        \Library\_('Right') => 2,
        \Library\_('Both') => 3,
    ),
    14 => array(
        \Library\_('-None-') => '',
        \Library\_('OpenLibrary.org') => 'openlib',
        \Library\_('Astore Plugin') => 'astore',
    ),
);

?>
