<?php
/**
*   Upgrade routines for the Library plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Include the default configuration values */
require_once LIBRARY_PI_PATH . '/install_defaults.php';

/**
*   Perform the upgrade starting at the current version.
*
*   @since  version 0.4.0
*   @param  string  $current_ver    Current version to be upgraded
*   @return integer                 Error code, 0 for success
*/
function LIBRARY_do_upgrade($current_ver)
{
    global $_TABLES, $_CONF, $_CONF_LIB;

    $error = 0;

    return $error;
}


/**
*   Actually perform any sql updates.
*   Gets the sql statements from the $UPGRADE array defined (maybe)
*   in the SQL installation file.
*
*   @param  string  $version    Version being upgraded TO
*   @param  array   $sql        Array of SQL statement(s) to execute
*/
function LIBRARY_do_upgrade_sql($version='')
{
    global $_TABLES, $_CONF_LIB, $_DB_dbms;

    /** Include the table creation strings */
    require_once LIBRARY_PI_PATH . "/sql/{$_DB_dbms}_install.php";

    // If no sql statements passed in, return success
    if (!is_array($UPGRADE[$version]))
        return 0;

    // Execute SQL now to perform the upgrade
    COM_errorLOG("--Updating Library to version $version");
    foreach($UPGRADE[$version] as $sql) {
        COM_errorLOG("Library Plugin $version update: Executing SQL => $sql");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during Library Plugin update",1);
            return 1;
            break;
        }
    }
    return 0;
}

?>
