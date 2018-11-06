<?php
/**
*   Configuration defaults for the Library plugin for glFusion.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/


// This file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $libraryConfigData;
$libraryConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'menuitem',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 2,
        'sort' => 10,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'daysonhold',
        'default_value' => 3,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'items_per_page',
        'default_value' => 10,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'ena_comments',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 2,
        'sort' => 40,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'ena_ratings',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 2,
        'sort' => 50,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'displayblocks',
        'default_value' => 3,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 13,
        'sort' => 60,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'maxcheckout',
        'default_value' => 21,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 70,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'lookup_method',
        'default_value' => 'openlib',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 14,
        'sort' => 80,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'menuitem',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 2,
        'sort' => 80,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'def_group_id',
        'default_value' => 13,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 90,
        'set' => true,
        'group' => 'library',
    ),

    array(
        'name' => 'fs_paths',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => NULL,
        'sort' => 10,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'max_images',
        'default_value' => 3,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'max_image_size',
        'default_value' => 4194304,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'max_thumb_size',
        'default_value' => 100,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'img_max_width',
        'default_value' => 800,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'img_max_height',
        'default_value' => 600,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 50,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'max_thumb_size',
        'default_value' => 100,
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'library',
    ),

    array(
        'name' => 'fs_notifications',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => NULL,
        'sort' => 20,
        'set' => true,
        'group' => 'library',
    ),
    array(
        'name' => 'grp_librarians',
        'default_value' => 1,   // Root by default
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 0, // helper function used
        'sort' => 10,
        'set' => true,
        'group' => 'library',
    ),

    array(
        'name' => 'notify_checkout',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 2,
        'sort' => 20,
        'set' => true,
        'group' => 'library',
    ),
);

/**
*  Initialize Library plugin configuration
*
*  Creates the database entries for the configuation if they don't already
*  exist.
*
*  @param  integer $group_id   Group ID to use as the plugin's admin group
*  @return boolean             true: success; false: an error occurred
*/
function plugin_initconfig_library($group_id = 0)
{
    global $libraryConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('library')) {
        USES_lib_install();
        foreach ($libraryConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        COM_errorLog('initconfig error: Library config group already exists');
    }
    return true;
}

?>
