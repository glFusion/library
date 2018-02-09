<?php
/**
*   Plugin-specific functions for the Library plugin for glFusion.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


/**
*   Checkout History View.
*   Displays the purchase history for the current user.  Admins
*   can view any user's histor, or all users
*
*   @author     Lee Garner <lee@leegarner.com>
*   @package    library
*/
function X_LIBRARY_history($admin = false, $uid = '')
{
    global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER;

    // Not available to anonymous users
    if (COM_isAnonUser())
        return '';

    USES_lib_admin();

    $isAdmin = $admin == true ? 1 : 0;

    $sql = "SELECT p.*, UNIX_TIMESTAMP(p.expiration) AS time, 
                d.name, d.short_description, d.file, d.prod_type,
                $isAdmin as isAdmin, 
                u.uid, u.username
            FROM  {$_TABLES['library.trans']} AS p 
            LEFT JOIN {$_TABLES['library.items']} AS d 
                ON d.id = p.item_id 
            LEFT JOIN {$_TABLES['users']} AS u 
                ON p.user_id = u.uid ";

    $base_url = LIBRARY_ADMIN_URL;
    if (!$isAdmin) {
        $where = " WHERE p.user_id = '" . (int)$_USER['uid'] . "'";
        $base_url = LIBRARY_URL;
    } elseif (!empty($uid)) {
        $where = " WHERE p.user_id = '" . (int)$uid . "'";
    }

    $header_arr = array(
        array('text' => $LANG_LIB['item_id'], 
                'field' => 'name', 'sort' => true),
        array('text' => $LANG_LIB['qty'], 
                'field' => 'quantity', 'sort' => true),
        array('text' => $LANG_LIB['description'],
                'field' => 'short_description', 'sort' => true),
        array('text' => $LANG_LIB['purch_date'],
                'field' => 'purchase_date', 'sort' => true),
        array('text' => $LANG_LIB['txn_id'],
                'field' => 'txn_id', 'sort' => true),
        array('text' => $LANG_LIB['expiration'],
                'field' => 'expiration', 'sort' => true),
        array('text' => $LANG_LIB['prod_type'],
                'field' => 'prod_type', 'sort' => true),
    );
    if ($isAdmin) {
        $header_arr[] = array('text' => $LANG_LIB['username'], 
                'field' => 'username', 'sort' => true);
    }


    $defsort_arr = array('field' => 'p.purchase_date',
            'direction' => 'asc');

    $display = COM_startBlock('', '', 
                COM_getBlockTemplate('_admin_block', 'header'));

    $query_arr = array('table' => 'library.trans',
            'sql' => $sql,
            'query_fields' => array('d.name', 'd.short_description', 'p.txn_id'),
            'default_filter' => $where,
        );

    $text_arr = array(
        'has_extras' => true,
        'form_url' => $base_url . '/index.php?mode=history',
    );

    if (!isset($_REQUEST['query_limit']))
        $_GET['query_limit'] = 20;

    $display .= ADMIN_list('library', 'LIBRARY_getPurchaseHistoryField',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, $this, '', $form_arr);

    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $display;

}


/**
*   Get an individual field for the history screen.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array (not used)
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function LIBRARY_getPurchaseHistoryField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB;
    
    $retval = '';

    switch($fieldname) {
    case 'name':
        if (is_numeric($A['item_id'])) {
            // One of our catalog items, so link to it
            $retval = COM_createLink($fieldvalue, 
                LIBRARY_URL . '/index.php?mode=detail&product=' . $A['item_id']);
        } else {
            // Probably came from a plugin, just show the product name
            $retval = htmlspecialchars($A['item_id']);
        }
        break; 

    case 'username':
        $retval = COM_createLink($fieldvalue, 
                $_CONF['site_url'] . '/users.php?mode=profile&uid=' . 
                $A['uid']);
        break;

    case 'quantity':
        $retval = '<div style="text-align:right;">' . $fieldvalue . "</div>";
        break;

    case 'txn_id':
        if ($A['isAdmin'] == 1) {
            $retval = COM_createLink($fieldvalue,
                LIBRARY_ADMIN_URL . '/index.php?mode=ipnlog&op=single&txn_id=' .
                $fieldvalue);
        } else {
            $retval = $fieldvalue;
        }
        break;

    case 'prod_type':
        $retval = $LANG_LIB['prod_types'][$A['prod_type']];
        break;

    default:
        $retval = htmlspecialchars($fieldvalue);
        break;
    }

    return $retval;
}


/**
*   Diaplay the product catalog items.
*
*   @return string      HTML for product catalog.
*/
function LIBRARY_ItemList()
{
    global $_TABLES, $_CONF, $_CONF_LIB, $LANG_LIB, $_USER, $_PLUGINS;

    $T = new Template(LIBRARY_PI_PATH . '/templates');
    $T->set_file('item', 'item_list.thtml');

    $sortby = 'name';
    $sortdir = isset($_GET['sortdir']) && $_GET['sortdir'] == 'DESC' ? 'DESC' : 'ASC';
    $url_opts = '&sortdir=' . $sortdir;
    $med_type = isset($_REQUEST['type']) ? (int)$_REQUEST['type'] : 0;
    $url_opts .= '&type=' . $med_type;

    $res = DB_query("SELECT * from {$_TABLES['library.types']}", 1);
    $opt_list = '';
    while ($type = DB_fetchArray($res, false)) {
        $sel = $type['id'] == $med_type ? 'selected="selected"' : '';
        $opt_list .= "<option value='{$type['id']}' $sel>{$type['name']}</option>\n";
    }
    $T->set_var(array(
            'pi_url'        => LIBRARY_URL,
            'type_select'   => $opt_list,
    ) );

    // Get items from database
    /*$sql_X = " FROM {$_TABLES['library.items']} p
            LEFT JOIN {$_TABLES['library.categories']} c
                ON p.cat_id = c.cat_id
            WHERE 
                p.enabled=1 
            AND 
                (c.enabled=1 OR c.enabled IS NULL)";*/

    $sql = " FROM {$_TABLES['library.items']} p
            WHERE p.enabled=1 ";

    // If applicable, limit by category
    /*if (!empty($_REQUEST['category'])) {
        $sql .= " AND c.cat_id = '".DB_escapeString($_REQUEST['category'])."'";
        $pagenav_args = '?category=' . urlencode($_REQUEST['category']);
    }*/

    if ($med_type > 0)
        $sql .= " AND type = '$med_type'";

    if (!empty($_REQUEST['query'])) {
        $query = DB_escapeString($_REQUEST['query']);
        $sql .= " AND (p.name like '%$query%' 
                OR p.description like '%$query%')";
        $T->set_var('query', htmlspecialchars($_REQUEST['query']));
    }

    // If applicable, order by
    $sql .= " ORDER BY $sortby $sortdir";

    // If applicable, handle pagination of query
    if (isset($_CONF_LIB['items_per_page']) && $_CONF_LIB['items_per_page'] > 0) {
        // Count items from database
        $res = DB_query('SELECT COUNT(*) as cnt ' . $sql);
        $x = DB_fetchArray($res, false);
        if (isset($x['cnt']))
            $count = (int)$x['cnt'];
        else
            $count = 0;

        // Make sure page requested is reasonable, if not, fix it
        $page = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $start_limit = ($page - 1) * $_CONF_LIB['items_per_page'];
        if ($start_limit > $count) {
            $page = ceil($count / $_CONF_LIB['items_per_page']);
        }
        // Add limit for pagination (if applicable)
        if ($count > $_CONF_LIB['items_per_page']) {
            $sql .= " LIMIT $start_limit, {$_CONF_LIB['items_per_page']}";
        }
    }

    // Re-execute query with the limit clause in place
    $res = DB_query('SELECT DISTINCT p.* ' . $sql);

    //$T->set_var('sortby_options', $sortby_options);
    if ($sortdir == 'DESC') {
        $T->set_var('sortdir_desc_sel', ' selected="selected"');
    } else {
        $T->set_var('sortdir_asc_sel', ' selected="selected"');
    }
    $T->set_var(array(
        'sortby'    => $sortby,
        'sortdir'   => $sortdir,
    ) );

    // Create an empty product object
    $P = new Library\Item();

    if ($_CONF_LIB['ena_ratings'] == 1) {
        $PP_ratedIds = RATING_getRatedIds('library');
    }

    // Display each product
    $T->set_block('item', 'ItemRow', 'IRow');
    while ($A = DB_fetchArray($res, false)) {
        //$T->set_block('product', 'ProdItem', 'PItem');

        $P->SetVars($A, true);

        if ($_CONF_LIB['ena_ratings'] == 1) {
            if (in_array($A['id'], $PP_ratedIds)) {
                $static = true;
                $voted = 1;
            } elseif (plugin_canuserrate_library($A['id'], $_USER['uid'])) {
                $static = 0;
                $voted = 0;
            } else {
                $static = 1;
                $voted = 0;
            }
            $rating_box = RATING_ratingBar('library', $A['id'], 
                    $P->votes, $P->rating, 
                    $voted, 5, $static, 'sm');
            $T->set_var('rating_bar', $rating_box);
        } else {
            $T->set_var('rating_bar', '');
        }

        // Highlight the query terms if coming from a search
        if (!empty($query)) {
            $url_opts .= '&query=' . urlencode($query);
            $hi_name = COM_highlightQuery(htmlspecialchars($P->name),
                        $query);
            $l_desc = COM_highlightQuery(htmlspecialchars($P->description), 
                        $query);
            $s_desc = COM_highlightQuery(htmlspecialchars($P->short_description), 
                        $query);
        } else {
            $hi_name = htmlspecialchars($P->name);
            $l_desc = htmlspecialchars($P->description);
            $s_desc = htmlspecialchars($P->short_description);
        }

        $T->set_var(array(
            'id'        => $A['id'],
            'name'      => $P->name,
            'hi_name'   => $hi_name,
            'description' => PLG_replacetags($l_desc),
            'short_description' => $s_desc,
            'img_cell_width' => ($_CONF_LIB['max_thumb_size'] + 20),
            'avail_blk' => $P->AvailBlock(),
            'author'    => $P->author,
            'publisher' => $P->publisher,
            'url_opts'  => $url_opts,
        ) );

        $pic_filename = DB_getItem($_TABLES['library.images'], 'filename',
                "item_id = '{$A['id']}'");
        if ($pic_filename) {
            $T->set_var('small_pic', 
                LGLIB_ImageUrl($_CONF_LIB['image_dir'] . '/' . $pic_filename));
        } else {
            $T->set_var('small_pic', '');
        }

        $T->parse('IRow', 'ItemRow', true);
    }

    // Display pagination
    if (isset($_CONF_LIB['items_per_page']) && 
            $_CONF_LIB['items_per_page'] > 0 &&
            $count > $_CONF_LIB['items_per_page'] ) {
        $T->set_var('pagination', 
            COM_printPageNavigation(LIBRARY_URL . '/index.php' . $pagenav_args,
                        $page, 
                        ceil($count / $_CONF_LIB['items_per_page'])));
    } else {
        $T->set_var('pagination', '');
    }

    $T->parse('output', 'item');
    return $T->finish($T->get_var('output'));
}


/**
*   Display a popup text message
*
*   @param string $msg Text to display 
*/
function LIBRARY_popupMsg($msg)
{
    global $_CONF;

    $msg = htmlspecialchars($msg);
    $popup = COM_showMessageText($msg);
    return $popup;
}


function LIBRARY_errMsg($msg)
{
    $retval = '<span class="alert">' . "\n";
    $retval .= "<ul>$msg</ul>\n";
    $retval .= "</span>\n";
    return $retval;
}


/**
*   Recurse through the category table building an option list
*   sorted by id.
*
*   @param integer  $sel        Category ID to be selected in list
*   @param integer  $parent_id  Parent category ID
*   @param string   $char       Separator characters
*   @param string   $not        'NOT' to exclude $items, '' to include
*   @param string   $items      Optional comma-separated list of items to include or exclude
*   @return string              HTML option list, without <select> tags
*/
function X_LIBRARY_recurseCats(
        $function, $sel=0, $parent_id=0, $char='', $not='', $items='', 
        $level=0, $maxlevel=0, $prepost = array())
{
    global $_TABLES, $_GROUPS;

    $str = '';
    if (empty($prepost)) {
        $prepost = array('', '');
    }

    // Locate the parent category of this one, or the root categories
    // if papa_id is 0.
    $sql = "
        SELECT
            cat_id, cat_name, parent_id, description,
            owner_id, group_id,
            perm_owner, perm_group, perm_members, perm_anon
        FROM
            {$_TABLES['library.categories']}
        WHERE
            parent_id = $parent_id";

    if (!empty($items)) {
        $sql .= " AND cat_id $not IN ($items) ";
    }
//    $sql .= COM_getPermSQL('AND'). "
    $sql .= "
        ORDER BY
            cat_name
                ASC
    ";
    //echo $sql;die;
    $result = DB_query($sql);
    // If there is no top-level category, just return.
    if (!$result)
        return '';

    while ($row = DB_fetchArray($result, false)) {
        $txt = $char . $row['cat_name'];
        $selected = $row['cat_id'] == $sel ? 'selected="selected"' : '';
        if ($row['parent_id'] == 0) {
            $style = 'style="background-color:lightblue"';
        } else {
            $style = '';
        }

        //$str .= "<option value=\"{$row['cat_id']}\" $style $selected $disabled>";
        //$str .= $txt;
        //$str .= "</option>\n";
        if (!function_exists($function))
            $function = 'LIBRARY_callbackCatOptionList';
        $str .= $function($row, $sel, $parent_id, $txt);
        if ($maxlevel == 0 || $level < $maxlevel) {
            $str .= $prepost[0] . 
                    LIBRARY_recurseCats($function, $sel, $row['cat_id'], 
                        $char."-", $not, $items, $level++, $maxlevel) .
                    $prepost[1];
        }
    }

    //echo $str;die;
    return $str;

}   // function LIBRARY_recurseCats()


/**
*   Callback function to create text for option list items.
*
*   @param  array   $A      Complete category record
*   @param  integer $sel    Selectd item (optional)
*   @param  integer $parent_id  Parent ID from which we've started searching
*   @param  string  $txt    Different text to use for category name.
*   @return string          Option list element for a category
*/
function X_LIBRARY_callbackCatOptionList($A, $sel=0, $parent_id=0, $txt='')
{
    if ($sel > 0 && $A['cat_id'] == $sel) {
        $selected = 'selected="selected"';
    } else {
        $selected = '';
    }

    if ($A['parent_id'] == 0) {
        $style = 'style="background-color:lightblue"';
    } else {
        $style = '';
    }

    if ($txt == '')
        $txt = $A['cat_name'];

    /*if (SEC_hasAccess($row['owner_id'], $row['group_id'],
                $row['perm_owner'], $row['perm_group'], 
                $row['perm_members'], $row['perm_anon']) < 3) {
            $disabled = 'disabled="true"';
    } else {
        $disabled = '';
    }*/

    $str = "<option value=\"{$A['cat_id']}\" $style $selected $disabled>";
    $str .= $txt;
    $str .= "</option>\n";
    return $str;

}


/**
*   Notify the first user on the waiting list that an item has become available.
*
*   @param  string  $id     Item ID
*/
function LIBRARY_notifyWaitlist($id = '')
{
    global $_TABLES,  $_CONF, $_CONF_LIB, $_LANG_LIB;

    // require a valid item ID
    $id = COM_sanitizeID($id);
    if ($id == '')
        return;

    // retrieve the first waitlisted user info. 
    $sql = "SELECT w.id, w.uid, w.item_id, i.name, i.daysonhold,
                u.email, u.language
            FROM {$_TABLES['library.waitlist']} w
            LEFT JOIN {$_TABLES['library.items']} i
                ON i.id = w.item_id
            LEFT JOIN {$_TABLES['users']} u
                ON u.uid = w.uid
            WHERE w.item_id='$id'
            LIMIT 1";
    $result = DB_query($sql);
    if (!$result || DB_numrows($result) < 1)
        return;

    $A = DB_fetchArray($result, false);
    $username = COM_getDisplayName($A['uid']);
    $daysonhold = (int)$A['daysonhold'] > 0 ? (int)$A['daysonhold'] : '';

    // Select the template for the message
    $template_dir = LIBRARY_PI_PATH . 
                    '/templates/notify/' . $name['language'];
    if (!file_exists($template_dir . '/item_avail.thtml')) {
        $template_dir = LIBRARY_PI_PATH . '/templates/notify/english';
    }

    // Load the recipient's language.
    $LANG = LIBRARY_loadLanguage($A['language']);

    $T = new Template($template_dir);
    $T->set_file('message', 'item_avail.thtml');
    $T->set_var(array(
        'username'      => $username,
        'pi_url'        => LIBRARY_URL,
        'item_id'       => $A['item_id'],
        'item_descrip'  => $A['name'],
        'daysonhold'    => $daysonhold,
    ) );
    $T->parse('output','message');
    $message = $T->finish($T->get_var('output'));

    COM_mail(
            $A['email'],
            "{$LANG['subj_item_avail']}",
            "$message",
            "{$_CONF['site_name']} <{$_CONF['site_mail']}>",
            true
        );

}   // function LIBRARY_waitlistNotify()


/**
*   Notify the librarian that an item has been requested.
*
*   @param  string  $item_id    Item ID being requested
*/
function LIBRARY_notifyLibrarian($item_id, $uid)
{
    global $_TABLES,  $_CONF, $_CONF_LIB, $_LANG_LIB;

    USES_lib_user();

    // require a valid item ID
    $item_id = COM_sanitizeID($item_id);
    if ($item_id == '') {
        return;
    }
    $user = COM_getDisplayName($uid);
    $grp_id = (int)$_CONF_LIB['grp_librarians'];
    $groupList = implode(',', USER_getChildGroups($grp_id, true));

    // Get the users in the Librarians group
    $sql = "SELECT u.email
            FROM {$_TABLES['group_assignments']} ga
            LEFT JOIN {$_TABLES['users']} u
                ON u.uid = ga.ug_uid
            WHERE ga.ug_main_grp_id IN ($groupList)
            AND u.email IS NOT NULL";
    //echo $sql;die;
    $res = DB_query($sql, 1);
    if (DB_numRows($res) == 0) return;
    $sql = "SELECT * 
            FROM {$_TABLES['library.items']}
            WHERE id = '$item_id'";
    //echo $sql;die;
    $item = DB_fetchArray(DB_query($sql, 1), false);
    if (empty($item)) return;

    $msg = '<p>Someone has requested a library item.</p>' . LB .
        '<p>Item Name: ' . $item['name'] . '</p>' . LB .
        '<p>Requested By: ' . $user . '</p>' . LB;

    while ($A = DB_fetchArray($res, false)) {
        if (empty($A['email']))
            continue;
        COM_mail(
            $A['email'],
            'Library Checkout Request',
            $msg,
            "{$_CONF['site_name']} <{$_CONF['site_mail']}>",
            true
        );
    }
}


/**
*   Loads a custom language array.
*   If $requested is an array, the first valid language file is loaded.  
*   If not, the $requested language file is loaded.
*   If $requested doesn't refer to a vailid language, then $_CONF['language']
*   is assumed.  If all else fails, english.php is loaded.
*
*   After loading the base language file, the same filename is loaded from
*   language/custom, if available.  The admin can override language strings
*   by creating a language file in that directory.
*
*   @param  mixed   $requested  A single or array of language strings
*   @return array               $LANG_LIB, which is not global here.
*/
function LIBRARY_loadLanguage($requested='')
{
    global $_CONF;

    // Set the language to the user's selected language, unless
    // otherwise specified.
    $languages = array();

    // Add the requested language, which may be an array or
    // a single item.
    if (is_array($requested)) { 
        $languages = $requested;
    } elseif ($requested != '') {
        // If no language requested, load the site/user default
        $languages[] = $requested;
    }

    // Add the site language and basic English  as a failsafe
    $languages[] = $_CONF['language'];
    $languages[] = 'english';

    // Search the array for desired language files, in order.
    $langpath = LIBRARY_PI_PATH . '/language';
    foreach ($languages as $language) {
        if (file_exists("$langpath/$language.php")) {
            include "$langpath/$language.php";
            // Include admin-supplied overrides, if any.
            if (file_exists("$langpath/custom/$language.php")) {
                include "$langpath/custom/$language.php";
            }
            break;
        }
    }
    return $LANG_LIB;
}

?>
