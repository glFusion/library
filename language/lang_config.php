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

global $_CONF_LIB;

$LANG_LIB_HELP = array(
/*'item_id' => 'Enter a unique item ID. A SKU or ISBN is recommended.',
'item_name' => 'Enter the item&apos;s name or title.',
'short_dscp' => 'Enter a short one-line description of the item.',
'item_cat_id' => 'Select the category for this item.',
'author' => 'Enter the author name(s)',
'publisher' => 'Enter the name of the publisher, if any.',
'pub_date' => 'Enter the date published. Any text may be entered',
'media_type' => 'Select the type of media for this item.',
'item_dscp' => 'Enter a detailed description of this item.',
'keywords' => 'Keywords are used for searching but do not appear in the item&apos;s display.',
'images' => 'Upload images to be shown with the item, such as book covers.',
'maxcheckout' => 'Enter the maximum number of days for which the item can be checked out.',
'daysonhold' => 'Enter the grace period, in days, to redeem a waitlisted item once it becomes available.',
'enabled' => 'Check if this item is enabled. Disabling an item can be used to temporarily remove an item from the catalog.',
'comments' => 'Select whether comments are enabled for this item.',*/
'max_wait_items' => 'You can reserve up to ' . $_CONF_LIB['max_wait_items'] . ' items at a time.',
'due_dt' => 'Enter or select the due date for the item.',
'checkout_user' => 'Select the user to check out this item. The user at the top of the waiting list is shown first.',
'cat_name' => 'Enter a short name for this category',
'cat_owner' => 'Select the category owner. There is no submission option so this should normally be an administrator.',
'cat_group' => 'Select the category group. This can be used to limit read access to a specific group.',
'cat_perms' => 'Select the permissions for this category. Only Read permission is used.',
'cat_enabled' => 'Select whether this category is enabled. Disabling a category prevents any of its items from being displayed.',
'mt_type' => 'Enter a name for this media type. This should be unique.',
'add_instances' => 'Enter the number of instances to add for this item.',
'checkin_instance' => 'Select the specific item instance to check in.',
);

/** Message indicating plugin version is up to date */
$PLG_library_MESSAGE2 = 'The library plugin is already up to date.';

$PLG_library_MESSAGE03 = 'Error retrieving current version number';
$PLG_library_MESSAGE04 = 'Error performing the plugin upgrade';
$PLG_library_MESSAGE05 = 'Error upgrading the plugin version number';
$PLG_library_MESSAGE06 = 'Plugin is already up to date';

/** Message indicating that no event was selected when required */
$PLG_library_MESSAGE101 = 'No Event Selected';

/** Language strings for the plugin configuration section */
$LANG_configsections['library'] = array(
    'label' => 'Library',
    'title' => 'Library Configuration'
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
 *  Language strings for the selection option names in the config section
 *  Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
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
