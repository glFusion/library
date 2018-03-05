<?php
/**
*   glFusion API functions for the Library plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

//  Check and see if we need to load the plugin configuration
if (isset($config) && !isset($_CONF_LIB['maxcheckout'])) {
    /** Include the system configuration class */
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    $lib_config = config::get_instance();
    $_CONF_LIB= $lib_config->get_config('library');
}

/** Include library config file  */
require_once 'library.php';
/** Define base path to plugin */
define('LIBRARY_PI_PATH', "{$_CONF['path']}/plugins/{$_CONF_LIB['pi_name']}");
/** Define base URL to plugin */
define('LIBRARY_URL', "{$_CONF['site_url']}/{$_CONF_LIB['pi_name']}");
/** Define URL to plugin admin interface */
define('LIBRARY_ADMIN_URL',
        "{$_CONF['site_admin_url']}/plugins/{$_CONF_LIB['pi_name']}");
define ('LIB_STATUS_ALL', 0);
/** Define status code: item available */
define ('LIB_STATUS_AVAIL', 1);
/** Define status code: item checked out */
define ('LIB_STATUS_OUT', 2);

// Store the private image path in a conf var so it's available
$_CONF_LIB['image_dir'] = $_CONF['path'] . 'data/library/images/items';
$_CONF_LIB['_is_uikit'] = $_SYSTEM['framework'] == 'uikit' ? 'true' : '';
$_CONF_LIB['_iconset'] = $_CONF_LIB['_is_uikit'] ? 'uk-icon' : 'fa fa';

//  Additional config items and table definitions are added here
if (file_exists(LIBRARY_PI_PATH . '/config.php')) {
    /** Include local configuration overrides */
    include_once LIBRARY_PI_PATH . '/config.php';
}

// Language file Include
$langpath = LIBRARY_PI_PATH . '/language';
$langfile = "{$_CONF['language']}.php";
if (!file_exists("$langpath/$langfile")) {
    $langfile = 'english.php';
}
/** Include the proper language file */
require_once "$langpath/$langfile";
if (file_exists("$langpath/custom/$langfile")) {
    /** Include the custom language file, if any */
    include_once "$langpath/custom/$langfile";
}

//  Set user ID = 1 for anonymous users.
if (empty($_USER['uid'])) {
    $_USER['uid'] = 1;
    $_USER['username'] = 'anonymous';
    $_USER['fullname'] = 'Anonymous';
}

/** Import plugin-specific functions */
function USES_library_functions()
{   require_once LIBRARY_PI_PATH . '/library_functions.inc.php';  }

/** Upgrade functions */
function USES_library_upgrade()
{   require_once LIBRARY_PI_PATH . '/upgrade.inc.php';   }

spl_autoload_register(function ($class)
{
    if (strpos($class, 'Library\\') === 0) {
        $class = str_replace('Library\\', '', $class);
        $path = __DIR__ . '/classes/' . $class . '.class.php';
        if (file_exists($path)) {
            include $path;
        }
    }
});

/**
*   Upgrade the plugin to the currently-installed version.
*
*   @return mixed       true if successful, false or error number otherwise
*/
function plugin_upgrade_library()
{
    global $_TABLES, $_CONF_LIB;

    $pi_name = $_CONF_LIB['pi_name'];

    // Get the current version, compare to the version in the plugin code
    $current_ver = DB_getItem($_TABLES['plugins'], 'pi_version',
        "pi_name = '$pi_name'");
    if (!$current_ver) {
        COM_errorLog("Error getting the $pi_name Plugin version",1);
        return '03';
    }

    // if we're at the current version, what are we doing here?
    if ($current_ver >= $_CONF_LIB['pi_version']) {
        COM_errorLog("Current version $current_ver is already up to date");
        return '06';
    }

    // If we're still here, perform the upgrade functions.
    USES_library_upgrade();
    $error = LIBRARY_do_upgrade($current_ver);
    if ($error) {
        COM_errorLog("Error updating the $pi_name Plugin version",1);
        return '04';
    }

    // now update the current version number.
    DB_query("UPDATE
            {$_TABLES['plugins']}
        SET
            pi_version = '{$_CONF_LIB['pi_version']}',
            pi_gl_version = '{$_CONF_LIB['gl_version']}',
            pi_homepage = '{$_CONF_LIB['pi_url']}'
        WHERE
            pi_name = '$pi_name';
    ");

    if (DB_error()) {
        COM_errorLog("Error updating the $pi_name Plugin version",1);
        return '05';
    }

    COM_errorLog("Succesfully updated the $pi_name Plugin!",1);
    return true;
}


/**
*   Returns the main menu items for the plugin.
*
*   @return array   Associative array of menu items to be added to menu
*/
function plugin_getmenuitems_library()
{
    global $_CONF_LIB, $LANG_LIB;

    $menuitems = array();
    if ($_CONF_LIB['menuitem'] && SEC_hasRights('library.view,library.admin', 'OR')) {
      	$menuitems[$LANG_LIB['mnu_library']] = LIBRARY_URL . '/index.php';
    }
    return $menuitems;
}


/**
*   Check to see if this plugin supports comments.
*
*   @return boolean     True if comments supported, false otherwise
*/
function plugin_commentsupport_library()
{
    global $_CONF_LIB;
    return $_CONF_LIB['ena_comments'] == 0 ? false : true;
}


/**
*   Save a comment
*
*   @param  string  $type       Plugin to save comment
*   @param  string  $title      Comment title
*   @param  string  $comment    Comment text
*   @param  string  $id         Item id to which $cid belongs
*   @param  int     $pid        Comment parent
*   @param  string  $postmode   'html' or 'text'
*   @return mixed   false for failure, HTML string (redirect?) for success
*/
function plugin_savecomment_library($title,$comment,$id,$pid,$postmode)
{
    global $_CONF, $LANG03, $_TABLES;

    // Without comment support, we shouldn't get here unless something
    // nefarious is going on, but just in case...
    if (!plugin_commentsupport_library())
        return;

    $title = strip_tags($title);
    $pid = COM_applyFilter($pid, true);
    $postmode = COM_applyFilter($postmode);
    $id = COM_sanitizeID($id, false);

    $ret = CMT_saveComment ( $title, $comment, $id, $pid,
            'library', $postmode);

    if ( $ret > 0 ) { // failure
        return COM_siteHeader()
            . CMT_commentform ($title, $comment, $id, $pid,
                    'library', $LANG03[14], $postmode)
            . COM_siteFooter();
    } else {        // success - update the comment counter for this ad
        $comments = DB_count($_TABLES['comments'],
                        array('type', 'sid'),
                        array('library', DB_escapeString($id)));

        DB_change($_TABLES['library.items'], 'comments',  $comments,
                'id', $id);

        return (COM_refresh (COM_buildUrl(LIBRARY_URL .
                    "/index.php?mode=detail&id=$id")) );
    }
}


/**
*   How the plugin will display specific comments.
*
*   @param   string  $id     Unique idenifier for item comment belongs to
*   @param   int     $cid    Comment id to display
*   @param   string  $title  Page/comment title
*   @param   string  $order  'ASC' or 'DSC' or blank
*   @param   string  $format 'threaded', 'nested', or 'flat'
*   @param   int     $page   Page number of comments to display
*   @param   boolean $view   True to view comment (by cid), false to display (by $pid)
*   @return  mixed   results of calling the plugin_displaycomment_ function
*/
function plugin_displaycomment_library($id, $cid, $title, $order, $format, $page, $view)
{
    global $_TABLES, $LANG_ACCESS;

    $retval = '';

    // Without comment support, we shouldn't get here unless something
    // nefarious is going on, but just in case...
    if (!plugin_commentsupport_library())
        return $retval;

    // Maybe some permission check will be added later...
    /*$sql = "SELECT
                COUNT(*) AS count,
                owner_id,
                group_id,
                perm_owner,
                perm_group,
                perm_members,
                perm_anon
            FROM
                {$_TABLES['library.products']}
            WHERE
                (id = '" . DB_escapeString($id) . "')"
                . COM_getPermSQL('AND') .
        ;
    $result = DB_query($sql);
    $A = DB_fetchArray($result);
    $allowed = $A['count'];*/

    $allowed = 1;

    if ($allowed == 1) {
        $delete_option = (SEC_hasRights('library.admin'));
        $retval .= CMT_userComments($id, $title, 'library', $order, $format,
                                     $cid, $page, $view, $delete_option,
                                     plugin_commentsupport_library());
    } else {
        $retval .= COM_startBlock($LANG_ACCESS['accessdenied'], '',
                        COM_getBlockTemplate ('_msg_block', 'header'))
                . $LANG_ACCESS['storydenialmsg']
                . COM_endBlock(COM_getBlockTemplate ('_msg_block', 'footer'));
    }

    return $retval;
}


/**
*   Delete a single comment.
*
*   @param  int     $cid    Comment to be deleted
*   @param  string  $id     Item id to which $cid belongs
*   @return mixed   false for failure, HTML string (redirect?) for success
*/
function plugin_deletecomment_library($cid, $id)
{
    global $_CONF,$_FM_TABLES, $_TABLES;

    $cid = COM_sanitizeID($cid, false);
    $id = COM_sanitizeID($id, false);

    if (SEC_hasRights('library.admin')) {
        if (CMT_deleteComment($cid, $id, 'library') == 0) {
            $count = DB_count($_TABLES['comments'],
                        array('sid', 'type'),
                        array($id, 'library'));
            $count--;
            if ($count < 0) $count = 0;
            DB_query("UPDATE {$_TABLES['library.items']}
                    SET comments=$count
                    WHERE id='$id'");
            // Redirect back to the product detail page
            return (COM_refresh(LIBRARY_URL .
                    '/index.php?mode=detail&id=' . $id));
        }
    }

    // Default return if anything fails
    return false;
}


/**
*   Handle any post-processing for an edited comment.
*   Just redirects back to the ad detail page.  The comment is actually
*   saved in comment.php.
*
*   @param  integer $cid    Comment ID
*   @param  mixed   $id     Item ID
*/
function plugin_editcomment_library($cid, $id)
{
    echo COM_refresh(COM_buildUrl(
            LIBRARY_URL . '/index.php?mode=detail&id=' . $id));
    exit;
}


/**
*   Returns the URL of the plugin's icon.
*
*   @return string      URL of the icon
*/
function plugin_geticon_library()
{
    global $_CONF_LIB;
    return LIBRARY_URL . '/images/' . $_CONF_LIB['pi_name'] . '.png';
}



/**
*   Add an option for the plugin in the command and control area.
*
*   @return array   Array containing (pi_name, admin_url, icon_url)
*/
function plugin_cclabel_library()
{
    global $_CONF_LIB;

    if (SEC_hasRights('library.admin')) {
        return array($_CONF_LIB['pi_display_name'],
                    LIBRARY_ADMIN_URL . '/index.php',
                    plugin_geticon_library());
    }
}


/**
*   Adds the library plugin to the Admin menu.
*
*   @return array   Array containing (pi_name, pi_admin_url, # of items or '')
*/
function plugin_getadminoption_library()
{
    global $_TABLES, $_CONF_LIB;

    if (SEC_hasRights('library.admin')) {
        return array($_CONF_LIB['pi_display_name'],
                    LIBRARY_ADMIN_URL . '/index.php',
                    DB_count($_TABLES['library.items']));
    }
}


/**
*   Returns the current version of the library plugin.
*
*   @return string  Plugin version
*/
function plugin_chkVersion_library()
{
    global $_CONF_LIB;

    return $_CONF_LIB['pi_version'];
}


/**
*   Performs operations when a user (or admin) deletes an account
*
*   Remove purchases from the library purchases table.
*
*   @TODO   Rather than drop rows, archive them
*   @param  int $uid    User ID that is being deleted
*/
function plugin_user_delete_library($uid) {
    global $_TABLES;

    //$sql = "DELETE FROM {$_TABLES['library.purchases']} WHERE user_id = $uid";
    //DB_query($sql);
}


/**
*   Implements the [library:] autotag.
*
*   Handle autotags.
*   Currently only allows for a single product ID, which will generate
*   a link to that product's detail page.
*
*   @since  version 0.4.0
*   @param  string  $op         Operation to perform (tagname or parse)
*   @param  string  $content    Content in which to replace autotag
*   @param  array   $autotag    Autotag details
*   @return string              Updated $content
*/
function plugin_autotags_library ($op, $content = '', $autotag = '') {
    global $_CONF, $_TABLES;

    if ($op == 'tagname' ) {
        return 'library';
    } else if ($op == 'parse') {
        $id = COM_applyFilter($autotag['parm1']);
        $url = LIBRARY_URL . "/index.php?mode=detail&product=$id";

        // Allow the author to use different text for the link.  Default
        // is the stored product name.
        if (empty($autotag['parm2'])) {
            $linktext = stripslashes(DB_getItem($_TABLES['library.items'],
                        'name', "id = '$id'"));
        } else {
            $linktext = $autotag['parm2'];
        }
        $link = '<a href="' . $url . '">' . $linktext . '</a>';
        $content = str_replace ($autotag['tagstr'], $link, $content);

        return $content;
    }
}


/**
*   Checks to see if the user has sufficient rights to view the page.
*
*   Check if the current user has rights (at least one of) specified by
*   $rights.  If not log the error, display a generic access denied message
*   and exit.
*
*   @param  string  $rights     Comma-separated list of allowable rights
*/
function library_access_check($rights = 'library.user,library.admin')
{
    global $_USER, $_SERVER, $LANG_LIB;

    // Confirm that the user has sufficient rights to access this page
    if (!SEC_hasRights($rights, 'OR')) {
        $display .= COM_siteHeader();
        $display .= COM_startBlock('Access Denied');
        $display .= LIBRARY_errorMessage($LANG_LIB['access_denied_msg'],
                    'alert', $LANG_LIB['access_denied']);
        $display .= COM_endBlock();
        $display .= COM_siteFooter();
        COM_accessLog("Unauthorized user {$_USER['username']} from "
                . "IP {$_SERVER['REMOTE_ADDR']} attempted to access the "
                . "library plugin at {$_SERVER['REQUEST_URI']}");
        echo $display;
        exit;
    }
}


/**
*   Determine if the current user is a plugin administrator
*
*   @return boolean     True if is a moderator, False if not.
*/
function plugin_ismoderator_library()
{
    static $retval = NULL;
    if ($retval  === NULL) {
        $retval = SEC_hasRights('library.admin');
    }
    return $retval;
}


/**
*   Filter submitted variables.
*
*   Uses glFusion functions to filter submitted variables for illegal content.
*   Any editing is done in place and no value is returned.
*
*   @param  array   $vars   Associative array of varname => typei
*                           (type is (number,alpha,text,html))
*   @param  array   &$arr   Reference to array of variable name => value
*/
function library_filterVars($vars, &$arr) {
    // cycle through each variable applying the appropriate filter
    foreach ($vars as $var => $type) {
        switch ($type) {
        case 'number':
            $arr[$var] = COM_applyFilter($arr[$var], true);
            break;

        case 'html':
            $arr[$var] = COM_checkHTML($arr[$var], 'library.admin');
            break;

        case 'text':
            $arr[$var] = htmlspecialchars(strip_tags(
                        COM_stripslashes($arr[$var])));
            break;

        case 'alpha':
        default:
            $arr[$var] = COM_applyFilter($arr[$var], false);
            break;
        }
    }
}


/**
*   Return the types to list in the search types selection.
*
*   @return array   Associative array (typename => title)
*/
function plugin_searchtypes_library()
{
    global $_CONF_LIB, $LANG_LIB;
    return array($_CONF_LIB['pi_name'] => $LANG_LIB['srchtitle']);
}


/**
*   Create a plugin search object to search this plugin
*
*   @param  string  $query
*   @param  string  $datestart  Starting date for search
*   @param  string  $dateend    Ending date for search
*   @param  string  $topic      Topic selected for search
*   @param  string  $type       Type selected during search
*   @param  string  $author     Limit results to this author (not used)
*   @return object              Search object to be processed by Search class
*/
function plugin_dopluginsearch_library(
    $query, $datestart, $dateend, $topic, $keyType, $author
)
{
return;
    global $_CONF, $_TABLES, $_CONF_LIB, $LANG_LIB;

    if (!SEC_hasRights('library.user,library.admin', 'OR'))
        return;

    if (empty($keyType)) {
        $type = 'all';
    }

    // Encode query for inclusion in result's url
    $htmlquery = urlencode($query);

    $query = trim(DB_escapeString(htmlspecialchars($query)));

    $sql_X = "SELECT
                p.id, p.name as title, p.dscp as description,
                dt_add as date,  views as hits,
                CONCAT('/{$_CONF_LIB['pi_name']}/index.php?mode=detail&id=',p.id,'&query=$htmlquery') as url
            FROM
                {$_TABLES['library.items']} p
            LEFT JOIN
                {$_TABLES['library.categories']} c
                ON p.cat_id=c.cat_id
            WHERE
                p.enabled = 1
            AND
                (c.enabled=1 OR c.enabled IS NULL)
            ";

    $sql = "SELECT
                p.id, p.name as title, p.dscp as description,
                dt_add as date,  views as hits,
                CONCAT('/{$_CONF_LIB['pi_name']}/index.php?detail=x&id=',p.id,'&query=$htmlquery') as url
            FROM
                {$_TABLES['library.items']} p
            WHERE
                p.enabled = 1
            ";

    $search = new SearchCriteria($_CONF_LIB['pi_name'], $LANG_LIB['srchtitle']);
    $columns = array('p.name', 'p.dscp',
                    'p.keywords', 'c.cat_name', 'c.dscp');
    list($sql, $ftsql) = $search->buildSearchSQL($keyType, $query, $columns, $sql);
    $search->setSQL($sql);
    return $search;
}


/**
*   Get additional Javascript files to import in the header
*
*   @return array   Array of filenames to import
*/
function plugin_getheaderjs_library()
{
    return array(
        LIBRARY_PI_PATH . '/js/utils.js',
    );
}


/**
*   Get additional CSS into the site header.
*   Allows for custom CSS to be placed by the site admin.
*
*   @return array   List of paths to CSS files
*/
function plugin_getheadercss_library()
{
    $styles = array();
    $files = array('style.css');

    $path = LIBRARY_PI_PATH . '/css';
    foreach ($files as $file) {
        $styles[] = $path . '/' . $file;
    }

    // Now get any customized versions
    $path .= '/custom';
    foreach ($files as $file) {
        if (is_file($path . '/' . $file)) {
            $styles[] = $path . '/' . $file;
        }
    }
    return $styles;
}


/**
*   Get the URL to the localized configuration help file.
*
*   @see    LIBRARY_getDocURL()
*   @param  string  $option     Configuration option to display
*   @param  string  $doclang    Documentation language requested
*   @return array   Array of (url, window_type)
*/
function plugin_getconfigelementhelp_library($option, $doclang='english')
{
    $doc_url = LIBRARY_getDocURL('config.html', $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }
    return array($doc_url, 0);
}


/**
*   Get the link to a documentation file.
*   Returns the URL to the file, or to the directory if no file requested.
*
*   @see    plugin_getconfigelementhelp_library()
*   @see    Product::showForm()
*   @param  string  $file       HTML file
*   @param  string  $doclang    Documentation language
*   @return string              URL to document directory or file
*/
function LIBRARY_getDocURL($file='', $doclang='english')
{
    $doc_path = LIBRARY_PI_PATH . "/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        if (!file_exists($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = LIBRARY_URL . "/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = LIBRARY_URL . "/docs/$doclang/";
    }
    return $doc_url;
}


/**
*   Return the items that should be removed with the plugin.
*   Can also handle any plugin-specific removal tasks.
*
*   @return   array     Array of items to be removed.
*/
function plugin_autouninstall_library()
{
    $out = array (
        // Give the name of the tables, without $_TABLES[]
        'tables' => array(
                'library.items',
                'library.trans',
                'library.images',
                'library.categories',
                'library.waitlist',
                'library.types',
        ),
        // Give the full name of the group, as in the db
        'groups' => array('library Admin'),
        // Give the full name of the feature, as in the db
        'features' => array(
                'library.admin',
                'library.view',
                'library.checkout',
        ),
        // Give the full name of the block, including 'phpblock_', etc
        'php_blocks' => array(),
        // List all vars by name
        'vars'=> array()
    );
    return $out;
}


/**
*   Enable or disable this plugin's blocks when the plugin is changed.
*   Acts on all blocks with a phpblock function similar to the specified
*   functions.
*
*   @param  boolean $enabled    Plugin's "enabled" state
*/
/*function plugin_enablestatechange_library($enabled)
{
    global $_TABLES;

    $enabled = $enabled == true ? 1 : 0;
    $blockfns = array('phpblock_library_');

    foreach ($blockfns as $blockfn) {
        DB_query("UPDATE {$_TABLES['blocks']}
                SET is_enabled=$enabled
                WHERE phpblockfn like '{$blockfn}%'"
                );
    }
}*/


/**
*   Provides the getItemInfo function for classified ads
*
*   @param  string  $id         Product ID
*   @param  string  $fields     Comma-separated list of fields to return
*   @param  integer $uid        User ID
*   @param  array   $options    Not used
*   @return array           Array of product information
*/
function plugin_getiteminfo_library($id, $what, $uid=0, $options=array())
{
    global $_TABLES, $_CONF, $LANG_LIB;

    $properties = explode(',', $what);
    $fields = array();
    $retval = array();
    $buildingSearchIndex = false;
    $fields[] = 'id';
    foreach ($properties as $p) {
        switch ($p) {
        case 'search_index':
            $buildingSearchIndex = true;
            break;
        case 'id':
            $fields[] = 'id';
            break;
        case 'title':
            $fields[] = 'name';
            break;
        case 'content':
        case 'description':
            $fields[] = 'short_dscp';
            $fields[] = 'dscp';
            break;
        case 'searchidx':
            $fields[] = 'name';
            $fields[] = 'short_dscp';
            $fields[] = 'dscp';
            $fields[] = 'keywords';
            $fields[] = 'publisher';
            $fields[] = 'author';
            break;
        case 'author':
        case 'author_name':
            $fields[] = 'author';
            break;
        case 'date':
            $fields[] = 'dt_add';
            break;
        case 'date':
            $fields[] = 'id';
            break;
        }
    }
    if (count($fields) == 0) {
        return $retval;
    }
    $fields = array_unique($fields);
    $fields = implode(',', $fields);
    $retval = array();
    $sql = "SELECT $fields FROM {$_TABLES['library.items']} ";
    if ($id == '*') {
        $where = ' WHERE 1=1 ';
    } else {
        $where = " WHERE (id = '" . DB_escapeString($id) . "')";
    }
    //echo $sql;die;
    $res = DB_query($sql . $where);
    while ($A = DB_fetchArray($res, false)) {
        foreach ($properties as $p) {
            switch ($p) {
            case 'id':
                $props[$p] = $A['id'];
                break;
            case 'description':
                $props[$p] = $A['dscp'];
                break;
            case 'excerpt':
            case 'content':
                $props[$p] = $A['name'] . ' ' . $A['short_dscp'] . ' ' . $A['dscp'];
                break;
            case 'title':
                $props[$p] = $A['name'];
                break;
            case 'author':
            case 'author_name':
                $props[$p] = $A['author'];
                break;
            case 'date':
                $props[$p] = $A['dt_add'];
                break;
            case 'searchidx':
                $props[$p] = $A['name'] . ' ' . $A['short_dscp'] . ' ' .
                        $A['dscp'] . ' ' . $A['keywords'] . ' ' . $A['publisher'] .
                        ' ' . $A['author'];
                break;
            case 'url':
                $props[$p] = COM_buildUrl($_CONF['site_url'] .
                    '/library/index.php?mode=detail&id=' . urlencode($A['id']));
                break;
            default:
                $props[$p] = isset($A[$p]) ? $A[$p] : NULL;
                break;
            }
        }
        $mapped = array();
        foreach ($props as $key => $value) {
            if ($id == '*') {
                if ($value != '') {
                    $mapped[$key] = $value;
                }
            } else {
                $mapped[$key] = $value;
            }
        }

        if ($id == '*') {
            $retval[] = $mapped;
        } else {
            $retval = $mapped;
            break;
        }
    }

    if (($id != '*') && (count($retval) == 1)) {
        $tRet = array_values($retval);
        $retval = $tRet[0];
    }
    if ($retval === '' || (is_array($retval) && count($retval) == 0)) {
        return NULL;
    } else {
        return $retval;
    }
}


/**
*   Display a formatted error message
*
*   @param  string $msg     Error message to display
*   @param  string $type    Type of message, used for style and header
*   @param  string $header  Optional header text.
*   @return string          HTML for formatted error message
*/
function LIBRARY_errorMessage($msg = '', $type = '', $header = '')
{
    global $LANG_LIB;

    // Require a valid message
    if ($msg == '')
        return '';

    // If no header text specified, we'll put in a default.
    // Also validate the message type in this switch.
    switch (strtolower($type)) {
    case 'info':
        $class = 'info';
        if ($header == '') $header = $LANG_LIB['info'];
        break;

    case 'note':
    case 'warn':
        $class = 'note';
        if ($header == '') $header = $LANG_LIB['warning'];
        break;

    case 'alert':
    default:
        $class = 'alert';
        if ($header == '') $header = $LANG_LIB['alert'];
        break;
    }

    return "<span class=\"$class\">"
            . COM_startBlock($header)
            . $msg
            . COM_endBlock()
            . '</span>';
}


/**
*   Updates the product table with the new rating and vote count.
*   Requires glFusion 1.1.7 or higher.
*
*   @since  version 0.4.0
*   @param  mixed   $id     Product ID
*   @param  float   $rating Rating Value
*   @param  integer $votes  Number of votes cast
*/
function plugin_itemrated_library($id, $rating, $votes)
{
    global $_TABLES;

    $sql = "UPDATE {$_TABLES['library.items']}
            SET rating = $rating, votes = $votes
            WHERE id='$id'";
    DB_query($sql);
    Library\Cache::clear();
    return true;
}


/**
*   Determine if the current user is allowed to rate an item.
*   Curretly, all logged-in users can rate (even admin).
*   Requires glFusion 1.1.7 or higher.
*
*   @since  version 0.4.0
*   @param  mixed   $item_id    ID of product to be rated
*   @param  integer $uid        User ID of the one doing the rating
*   @return boolean             True if user is allowed, false if not
*/
function plugin_canuserrate_library($item_id, $uid)
{
    global $_CONF_LIB;

    $_CONF_LIB['anon_can_rate'] = 0;    // TODO
    if ($_CONF_LIB['anon_can_rate'] == 0 && COM_isAnonUser()) {
        return false;
    } else {
        return true;
    }
}


/**
*   Perform scheduled functions, such as notifications and waitlist cleanup.
*/
function plugin_runScheduledTask_library()
{
    Library\Waitlist::Expire();
}


/**
*   Displays Library stats for the user in the user's profile.
*
*   @param  integer $uid    User ID whose profile is being displayed
*   @param  object  &$T     Pointer to template object
*   @return string          HTML to include in profile page
*/
function plugin_profilevariablesdisplay_library($uid, &$T)
{
    global $_TABLES, $_CONF, $_USER;

    $retval = '';

    // Only admins can see other users' library items
    if ($uid != $_USER['uid'] && !SEC_hasRights('library.admin')) {
        return;
    }

    $uid = (int)$uid;
    // Make sure that a valid UID was passed in.
    if ($uid < 2 ) {
        return;
    }

    $sql = "SELECT id, name, short_dscp, due
            FROM {$_TABLES['library.items']}
            WHERE uid='$uid'";
    //echo $sql;die;
    $res = DB_query($sql, 1);
    if (DB_error() || DB_numRows($res) < 1)
        return;

    $LT = new Template(LIBRARY_PI_PATH .'/templates');
    $LT->set_file('block', 'profileblock.thtml');

    $ts = time();
    while ($A = DB_fetchArray($res, false)) {
        $fmt_dt_due = date('M d, Y', $A['due']);
        if ($A['due'] < $ts) {
            $fmt_dt_due = '<span style="color:red;">' .
                $fmt_dt_due . '</span>';
        }
        $LT->set_block('block', 'profile_row', 'prow');
        $LT->set_var(array(
            'name'  => $A['name'],
            'short_dscp' => $A['short_dscp'],
            'id'    => $A['id'],
            'due_date' => $fmt_dt_due,
        ) );
        $LT->parse('prow', 'profile_row', true);
    }

    $existingFields = $T->get_var('plugin_fields');
    $LT->parse('output', 'block');
    $T->set_var('plugin_fields',
        $existingFields . $LT->finish($LT->get_var('output')));
}


/**
*   Select the Librarian group.
*   'All Users' is not allowed
*
*   @return array       Array of groups
*/
function plugin_configmanager_select_grp_librarians_library()
{
    global $_TABLES;

    $A = array();
    $sql = "SELECT grp_id, grp_name FROM {$_TABLES['groups']}";
    $res = DB_query($sql);
    if ($res) {
        while ($row = DB_fetchArray($res, false)) {
            $row['grp_name'] = htmlspecialchars($row['grp_name']);
            $A[$row['grp_name']] = $row['grp_id'];
        }
    }
    return $A;
}


/**
*   Show the site header, with or without left blocks according to config.
*
*   @since  version 1.0.2
*   @see    COM_siteHeader()
*   @param  string  $subject    Text for page title (ad title, etc)
*   @param  string  $meta       Other meta info
*   @return string              HTML for site header
*/
function LIBRARY_siteHeader($subject='', $meta='')
{
    global $_CONF_LIB, $LANG_LIB;

    $retval = '';

    $title = $LANG_LIB['main_title'];
    if ($subject != '')
        $title = $subject . ' : ' . $title;

    switch ($_CONF_LIB['displayblocks']) {
    case 2:     // right only
    case 0:     // none
        $retval .= COM_siteHeader('none', $title, $meta);
        break;

    case 1:     // left only
    case 3:     // both
    default :
        $retval .= COM_siteHeader('menu', $title, $meta);
        break;
    }

    return $retval;
}


/**
*   Show the site footer, with or without right blocks according to config.
*
*   @since  version 1.0.2
*   @see    COM_siteFooter()
*   @return string              HTML for site header
*/
function LIBRARY_siteFooter()
{
    global $_CONF_LIB;

    $retval = '';

    switch($_CONF_LIB['displayblocks']) {
    case 2 : // right only
    case 3 : // left and right
        $retval .= COM_siteFooter(true);
        break;

    case 0: // none
    case 1: // left only
    default :
        $retval .= COM_siteFooter();
        break;
    }

    return $retval;
}


/**
*   Shortcut to get the current date object
*
*   @return object  Date object for current timestamp
*/
function LIBRARY_now()
{
    global $_CONF;
    static $now = NULL;
    if ($now === NULL) {
        $now = new \Date('now', $_CONF['timezone']);
    }
    return $now;
}


/**
*   Shortcut function to get the SQL-formatted date
*
*   @return string  Today's date as "YYYY-MM-DD"
*/
function LIBRARY_today()
{
    return LIBRARY_now()->format('Y-m-d', true);
}


/**
*   Calculate and return the expiration date for an item
*
*   @return Object      Expiration date
*/
function LIBRARY_dueDate($days = 0)
{
    global $_CONF_LIB;
    if ($days == 0) $days = $_CONF_LIB['maxcheckout'];
    return LIBRARY_now()->add(new \DateInterval("P{$days}D"));
}


/**
*   Get an icon from the CSS icon set, either UIkit or FontAwesome
*
*   @param  string  $name   Base name of icon, e.g. "edit" for "uk-icon-edit"
*   @param  string  $style  Style name from local CSS
*   @return string      Class names for icon
*/
function LIBRARY_getIcon($name, $style='info')
{
    global $_CONF_LIB;

    if ($style != '')
        $style = ' lib-icon-' . $style;
    return $_CONF_LIB['_iconset'] . '-' . $name . $style;
}


/**
*   Helper function to get a template object.
*
*   @param  string  $name   Base name of template file, e.g. "editor"
*   @param  string  $id     ID of template, e.g. "editform"
*   @param  string  $path   Optional path if not "templates/"
*   @return string      Full filename, e.g. "editor.uikit.thtml"
*/
function LIBRARY_getTemplate($name, $id='', $path='')
{
    global $_CONF_LIB;

    $templates = array();

    // Put parameters into an array if not already.
    if (!is_array($name) && !empty($id)) {
        $templates = array($id => $name);
    } elseif (is_array($name)) {
        $templates = $name;
    }

    // Add additional path info if needed
    if (substr($path, 0, 9) != 'templates') {
        $path = 'templates/' . $path;
    }

    // May use "switch" later, for now there are only two options
    $tpltype = $_CONF_LIB['_is_uikit'] ? '' : '.legacy';
    foreach ($templates as $t_id =>$t_name) {
        if (file_exists(__DIR__ . "/$path/$t_name$tpltype.thtml")) {
            $templates[$t_id] = "$t_name$tpltype.thtml";
        } else {
            $templates[$t_id] = "$t_name.thtml";
        }
    }
    $T = new Template(__DIR__ . '/' . $path);
    $T->set_file($templates);
    return $T;
}

?>
