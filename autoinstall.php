<?php
/**
*   Automatic installation functions for the Library plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_dbms;

require_once __DIR__ . '/library.php';
require_once __DIR__ . '/sql/'.$_DB_dbms.'_install.php';
require_once __DIR__ . '/install_defaults.php';

$language = $_CONF['language'];
if (!is_file($_CONF['path'].'plugins/library/language/' . $language . '.php')) {
    $language = 'english';
}
require_once $_CONF['path'].'plugins/library/language/' . $language . '.php';
global $LANG_LIB;

//  Plugin installation options
$INSTALL_plugin['library'] = array(
    'installer' => array(
            'type' => 'installer',
            'version' => '1',
            'mode' => 'install'
        ),
    'plugin' => array(
            'type' => 'plugin',
            'name' => $_CONF_LIB['pi_name'],
            'ver' => $_CONF_LIB['pi_version'],
            'gl_ver' => $_CONF_LIB['gl_version'],
            'url' => $_CONF_LIB['pi_url'],
            'display' => $_CONF_LIB['pi_display_name']
        ),
    array(  'type' => 'table',
            'table' => $_TABLES['library.items'],
            'sql' => $_SQL['library.items']
        ),
    array(  'type' => 'table',
            'table' => $_TABLES['library.instances'],
            'sql' => $_SQL['library.instances']
        ),
    array(  'type' => 'table',
            'table' => $_TABLES['library.categories'],
            'sql' => $_SQL['library.categories']
        ),
    array(  'type' => 'table',
            'table' => $_TABLES['library.log'],
            'sql' => $_SQL['library.log']
        ),
    array(  'type' => 'table',
            'table' => $_TABLES['library.images'],
            'sql' => $_SQL['library.images']
        ),
    array(  'type' => 'table',
            'table' => $_TABLES['library.waitlist'],
            'sql' => $_SQL['library.waitlist']
        ),
    array(  'type' => 'table',
            'table' => $_TABLES['library.types'],
            'sql' => $_SQL['library.types']
        ),
    array(  'type' => 'group',
            'group' => 'library Admin',
            'desc' => 'Users in this group can administer the Library plugin',
            'variable' => 'admin_group_id',
            'addroot' => true
        ),
    /*array(  'type' => 'group',
            'group' => 'library Librarians',
            'desc' => 'Users in this group can manage checking and checkout of library items',
            'variable' => 'librarian_group_id',
            'addroot' => true
        ),*/
    array(  'type' => 'feature',
            'feature' => 'library.admin',
            'desc' => 'Ability to administer the Library plugin',
            'variable' => 'admin_feature_id'
        ),
    array(  'type' => 'feature',
            'feature' => 'library.view',
            'desc' => 'Ability to view the Library listings',
            'variable' => 'view_feature_id'
        ),
    array(  'type' => 'feature',
            'feature' => 'library.checkout',
            'desc' => 'Ability to check out library items',
            'variable' => 'checkout_feature_id'
        ),
    array(  'type' => 'mapping',
            'group' => 'admin_group_id',
            'feature' => 'admin_feature_id',
            'log' => 'Adding admin feature to the admin group'
        ),
    array(  'type' => 'mapping',
            'findgroup' => 'Logged-in Users',
            'feature' => 'checkout_feature_id',
            'log' => 'Adding checkout feature to the logged-in group'
        ),
    array(  'type' => 'mapping',
            'findgroup' => 'Logged-in Users',
            'feature' => 'view_feature_id',
            'log' => 'Adding view feature to the Logged-in Users group'
        ),
    array('type' => 'sql',
            'sql' => $_DEFDATA['library.categories']
        ),
    array('type' => 'sql',
            'sql' => $_DEFDATA['library.types']
        ),
    array('type'    => 'mkdir',
        'dirs'      => array(
            $_CONF['path'] . 'data/library',
            $_CONF['path'] . 'data/library/images',
            $_CONF['path'] . 'data/library/images/items',
        ),
    ),
);


/**
*   Puts the datastructures for this plugin into the glFusion database
*   Note: Corresponding uninstall routine is in functions.inc
*
*   @return   boolean True if successful False otherwise
*/
function plugin_install_library()
{
    global $INSTALL_plugin, $_CONF_LIB;

    COM_errorLog("Attempting to install the {$_CONF_LIB['pi_display_name']} plugin", 1);

    $ret = INSTALLER_install($INSTALL_plugin[$_CONF_LIB['pi_name']]);
    if ($ret > 0) {
        return false;
    }
    return true;
}


/**
*   Loads the configuration records for the Online Config Manager
*
*   @return boolean true = proceed with install, false = an error occured
*/
function plugin_load_configuration_library()
{
    global $_CONF, $_CONF_LIB, $_TABLES;

    // Get the group ID that was saved previously.
    $group_id = (int)DB_getItem($_TABLES['groups'], 'grp_id',
            "grp_name='{$_CONF_LIB['pi_name']} Admin'");

    return plugin_initconfig_library($group_id);
}

?>
