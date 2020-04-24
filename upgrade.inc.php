<?php
/**
 * Upgrade routines for the Library plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True if this is a development update
 * @return  integer                 Error code, 0 for success
 */
function LIBRARY_do_upgrade($dvlp = false)
{
    global $_TABLES, $_CONF;

    $error = 0;

    // Sync config items
    USES_lib_install();
    require_once __DIR__ . '/install_defaults.php';
    _update_config('library', $libraryConfigData);

    return $error;
}


/**
 * Actually perform any sql updates.
 * Gets the sql statements from the $UPGRADE array defined (maybe)
 * in the SQL installation file.
 *
 * @param   string  $version    Version being upgraded TO
 * @param   boolean $dvlp       True to ignore errors and continue
 * @return  boolean     True on success, False on failure
 */
function LIBRARY_do_upgrade_sql($version='', $dvlp=false)
{
    global $_TABLES, $_DB_dbms;

    /** Include the table creation strings */
    require_once __DIR__ . "/sql/{$_DB_dbms}_install.php";

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
            if (!$dvlp) return 1;
            break;
        }
    }
    return 0;
}

?>
