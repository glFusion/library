<?php
/**
*   Plugin-specific functions for the Library plugin for glFusion.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/


/**
*   Diaplay the product catalog items.
*
*   @return string      HTML for product catalog.
*/
function LIBRARY_ItemList()
{
    global $_TABLES, $_CONF, $_CONF_LIB, $LANG_LIB, $_USER, $_PLUGINS;

    $T = LIBRARY_getTemplate('item_list', 'item');
    $sortby = 'name';
    $sortdir = isset($_GET['sortdir']) && $_GET['sortdir'] == 'DESC' ? 'DESC' : 'ASC';
    $url_opts = '&sortdir=' . $sortdir;
    $med_type = isset($_GET['type']) ? (int)$_GET['type'] : 0;
    $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
    $url_opts .= '&type=' . $med_type;

    $T->set_var(array(
        'pi_url'        => LIBRARY_URL,
        'type_select'   => Library\MediaType::buildSelection($med_type),
        'cat_select'    => Library\Category::buildSelection($cat_id),
    ) );

    // Get items from database
    $sql = " FROM {$_TABLES['library.items']} p
            LEFT JOIN {$_TABLES['library.categories']} c
                ON p.cat_id = c.cat_id
            WHERE p.enabled=1
            AND (c.enabled=1 OR c.enabled IS NULL) " .
            COM_getPermSQL('AND', 0, 2, 'c');

    $pagenav_args = '?1=1';

    // If applicable, limit by category
    if ($cat_id > 0) {
        $sql .= " AND p.cat_id = $cat_id";
        $pagenav_args .= '&category=' . $cat_id;
    }

    if ($med_type > 0) {
        $sql .= " AND type = '$med_type'";
        $pagenav_args .= '&type = ' . $med_type;
    }

    if (!empty($_GET['query'])) {
        $query = DB_escapeString($_GET['query']);
        $sql .= " AND (p.name like '%$query%'
                OR p.dscp like '%$query%')";
        $T->set_var('query', htmlspecialchars($_GET['query']));
        $pagenav_args .= '&query=' . urlencode($_GET['query']);
    }

    // If applicable, order by
    $sql .= " ORDER BY $sortby $sortdir";

    // If applicable, handle pagination of query
    if (isset($_CONF_LIB['items_per_page']) && $_CONF_LIB['items_per_page'] > 0) {
        // Count items from database
        $count_sql = 'SELECT COUNT(*) as cnt ' . $sql;
        $key = md5($count_sql);
        $count = Library\Cache::get($key);
        if ($count === NULL) {
            $res = DB_query($count_sql);
            $x = DB_fetchArray($res, false);
            if (isset($x['cnt']))
                $count = (int)$x['cnt'];
            else
                $count = 0;
            Library\Cache::set($key, $count);
        }

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

    $sql1 = 'SELECT p.* ' . $sql;
    $key = md5($sql);
    $Items = Library\Cache::get($key);
    if ($Items === NULL) {
        // Re-execute query with the limit clause in place
        $res = DB_query($sql1);
        $Items = DB_fetchAll($res, false);
        Library\Cache::set($key, $Items);
    }

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
        $ratedIds = RATING_getRatedIds('library');
    } else {
        $ratedIds = array();
    }

    // Display each product
    $T->set_block('item', 'ItemRow', 'IRow');
    //while ($A = DB_fetchArray($res, false)) {
    foreach ($Items as $A) {

        $P->SetVars($A, true);

        if ($_CONF_LIB['ena_ratings'] == 1) {
            if (in_array($P->id, $ratedIds)) {
                $static = true;
                $voted = 1;
            } elseif (plugin_canuserrate_library($A['id'], $_USER['uid'])) {
                $static = 0;
                $voted = 0;
            } else {
                $static = 1;
                $voted = 0;
            }
            $rating_box = RATING_ratingBar('library', $P->id,
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
            $l_desc = COM_highlightQuery(htmlspecialchars($P->dscp),
                        $query);
            $s_desc = COM_highlightQuery(htmlspecialchars($P->short_dscp),
                        $query);
        } else {
            $hi_name = htmlspecialchars($P->name);
            $l_desc = htmlspecialchars($P->dscp);
            $s_desc = htmlspecialchars($P->short_dscp);
        }

        $T->set_var(array(
            'id'        => $A['id'],
            'name'      => $P->name,
            'hi_name'   => $hi_name,
            'dscp' => PLG_replacetags($l_desc),
            'short_dscp' => $s_desc,
            'img_cell_width' => ($_CONF_LIB['max_thumb_size'] + 20),
            'avail_blk' => $P->AvailBlock(),
            'author'    => $P->author,
            'publisher' => $P->publisher,
            'url_opts'  => $url_opts,
            'can_edit'  => plugin_ismoderator_library(),
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
}


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
    $Item = new Library\Item($item_id);
    if ($Item->isNew) return;   // invalid item id

    $msg = '<p>Someone has requested a library item.</p>' . LB .
        '<p>Item Name: ' . $Item->name . '</p>' . LB .
        '<p>Requested By: ' . $user . '</p>' . LB;

    while ($A = DB_fetchArray($res, false)) {
        if (empty($A['email'])) continue;
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
