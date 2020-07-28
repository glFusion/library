<?php
/**
*   Global configuration items for the Library plugin.
*   These are either static items, such as the plugin name and table
*   definitions, or are items that don't lend themselves well to the
*   glFusion configuration system, such as allowed file types.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
*   @package    paypal
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

global $_DB_table_prefix, $_TABLES;

Library\Config::set('pi_name', 'library');
Library\Config::set('pi_display_name', 'Library');
Library\Config::set('pi_version', '0.0.1');
Library\Config::set('gl_version', '1.7.0');
Library\Config::set('pi_url', 'http://www.glfusion.org');

$_LIB_table_prefix = $_DB_table_prefix . 'library_';

$_TABLES['library.items']       = $_LIB_table_prefix . 'items';
$_TABLES['library.instances']   = $_LIB_table_prefix . 'instances';
$_TABLES['library.log']         = $_LIB_table_prefix . 'log';
$_TABLES['library.images']      = $_LIB_table_prefix . 'images';
$_TABLES['library.categories']  = $_LIB_table_prefix . 'categories';
$_TABLES['library.waitlist']    = $_LIB_table_prefix . 'waitlist';
$_TABLES['library.types']       = $_LIB_table_prefix . 'mediatypes';

?>
