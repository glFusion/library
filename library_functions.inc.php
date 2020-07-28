<?php
/**
 * Plugin-specific functions for the Library plugin for glFusion.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2018 Lee Garner
 * @package     library
 * @version     0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

use Library\_;

/**
 * Diaplay the product catalog items.
 *
 * @return  string      HTML for product catalog.
 */
function LIBRARY_ItemList()
{
    global $_TABLES, $_CONF, $_USER, $_GROUPS;

    $T = new \Template(__DIR__ . '/templates');
    $T->set_file(array(
        'item'      => 'item_list.thtml',
        'formjs'    => 'checkinout_js.thtml',
    ) );
    $sortby = 'title';
    $sortdir = isset($_GET['sortdir']) && $_GET['sortdir'] == 'DESC' ? 'DESC' : 'ASC';
    $url_opts = '&sortdir=' . $sortdir;
    $med_type = isset($_GET['type']) ? (int)$_GET['type'] : 0;
    $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
    $url_opts .= '&type=' . $med_type;

    $Config = Library\Config::getInstance();
    $T->set_var(array(
        'pi_url'        => $Config->get('url'),
        'type_select'   => Library\MediaType::buildSelection($med_type, true),
        'cat_select'    => Library\Category::buildSelection($cat_id),
        'lang_type'     => _('Media Type'),
        'lang_category' => _('Category'),
        'lang_search'   => _('Search'),
        'lang_all'      => _('All'),
        'lang_ascending' => _('Ascending'),
        'lang_descending' => _('Descending'),
        'lang_category' => _('Category'),
        'lang_edit' => _('Edit'),
        'lang_sort' => _('Sort'),
        'lang_submit' => _('Submit'),
        'is_admin' => plugin_ismoderator_library(),
        'lang_admin' => _('Admin'),
        //'is_librarian'  => plugin_ismoderatorator_library(),
    ) );
    $user_groups = implode(', ', $_GROUPS);

    // Get items from database
    $sql = " FROM {$_TABLES['library.items']} p
            LEFT JOIN {$_TABLES['library.categories']} c
                ON p.cat_id = c.cat_id
            WHERE p.enabled=1
            AND (c.enabled=1 OR c.enabled IS NULL)
            AND c.group_id IN ($user_groups) ";

    $pagenav_args = array();

    // If applicable, limit by category
    if ($cat_id > 0) {
        $sql .= " AND p.cat_id = $cat_id";
        $pagenav_args['category'] = $cat_id;
    }

    if ($med_type > 0) {
        $sql .= " AND type = '$med_type'";
        $pagenav_args['type'] = $med_type;
    }

    if (!empty($_GET['query'])) {
        $query = DB_escapeString($_GET['query']);
        $sql .= " AND (p.title like '%$query%'
                OR p.dscp like '%$query%')";
        $T->set_var('query', htmlspecialchars($_GET['query']));
        $pagenav_args['query'] = urlencode($_GET['query']);
    }

    // If applicable, order by
    $sql .= " ORDER BY $sortby $sortdir";

    // If applicable, handle pagination of query
    $items_per_page = (int)$Config->get('items_per_page');
    if ($items_per_page > 0) {
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
            Library\Cache::set($key, $count, 'items');
        }

        // Make sure page requested is reasonable, if not, fix it
        $page = isset($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $start_limit = ($page - 1) * $items_per_page;
        if ($start_limit > $count) {
            $page = ceil($count / $items_per_page);
        }
        // Add limit for pagination (if applicable)
        if ($count > $items_per_page) {
            $sql .= " LIMIT $start_limit, {$items_per_page}";
        }
    }

    $sql1 = 'SELECT p.* ' . $sql;
    $key = md5($sql);
    $Items = Library\Cache::get($key);
    if ($Items === NULL) {
        // Re-execute query with the limit clause in place
        $res = DB_query($sql1);
        $Items = DB_fetchAll($res, false);
        Library\Cache::set($key, $Items, 'items');
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

    if ($Config->get('ena_ratings') == 1) {
        $ratedIds = RATING_getRatedIds('library');
    } else {
        $ratedIds = array();
    }

    // Display each product
    $T->set_block('item', 'ItemRow', 'IRow');
    foreach ($Items as $A) {
        //var_dump($A);die;
        $P = \Library\Item::getInstance($A);

        if ($Config->get('ena_ratings') == 1) {
            if (in_array($P->getID(), $ratedIds)) {
                $static = true;
                $voted = 1;
            } elseif (plugin_canuserrate_library($A['id'], $_USER['uid'])) {
                $static = 0;
                $voted = 0;
            } else {
                $static = 1;
                $voted = 0;
            }
            $rating_box = RATING_ratingBar(
                'library', $P->getID(),
                $P->getVotes(), $P->getRating(),
                $voted, 5, $static, 'sm'
            );
            $T->set_var('rating_bar', $rating_box);
        } else {
            $T->set_var('rating_bar', '');
        }

        // Highlight the query terms if coming from a search
        if (!empty($query)) {
            $url_opts .= '&query=' . urlencode($query);
            $hi_name = COM_highlightQuery(htmlspecialchars($P->getTitle()),
                        $query);
            $l_desc = COM_highlightQuery(htmlspecialchars($P->getDscp()),
                        $query);
            $subtitle = COM_highlightQuery(htmlspecialchars($P->getSubtitle()),
                        $query);
        } else {
            $hi_name = htmlspecialchars($P->getTitle());
            $l_desc = htmlspecialchars($P->getDscp());
            $subtitle = htmlspecialchars($P->getSubtitle());
        }

        $T->set_var(array(
            'id'        => $A['id'],
            'title'     => $P->getTitle(),
            'hi_name'   => $hi_name,
            'dscp'      => PLG_replacetags($l_desc),
            'subtitle'  => $subtitle,
            'img_cell_width' => ($Config->get('max_thumb_size') + 20),
            'avail_blk' => $P->AvailBlock(),
            'author'    => $P->getAuthor(),
            'publisher' => $P->getPublisher(),
            'url_opts'  => $url_opts,
            'can_edit'  => plugin_ismoderator_library(),
        ) );

        $pic_filename = DB_getItem($_TABLES['library.images'], 'filename',
                "item_id = '{$A['id']}'");
        if ($pic_filename) {
            $T->set_var('small_pic',
                LGLIB_ImageUrl($Config->get('image_dir') . '/' . $pic_filename));
        } else {
            $T->set_var('small_pic', '');
        }

        if (plugin_ismoderator_library()) {
            $T->parse('checkinout_js', 'formjs');
        }
        $T->parse('IRow', 'ItemRow', true);
    }

    // Display pagination
    if (
        $items_per_page > 0 &&
        $count > $items_per_page
    ) {
        $T->set_var('pagination',
            COM_printPageNavigation(
                $Config->get('url') . '/index.php?' . http_build_query($pagenav_args),
                $page,
                ceil($count / $items_per_page)
            )
        );
    } else {
        $T->set_var('pagination', '');
    }

    $T->parse('output', 'item');
    return $T->finish($T->get_var('output'));
}


/**
 * Display a popup text message
 *
 * @param   string  $msg Text to display
 */
function LIBRARY_popupMsg($msg)
{
    global $_CONF;

    $msg = htmlspecialchars($msg);
    $popup = COM_showMessageText($msg);
    return $popup;
}


/**
 * Create an error message to be displayed.
 *
 * @param   string  $msg    Message Text
 * @return  string      HTML for message display
 */
function LIBRARY_errMsg($msg)
{
    $retval = '<span class="alert">' . "\n";
    $retval .= "<ul>$msg</ul>\n";
    $retval .= "</span>\n";
    return $retval;
}


/**
 * Notify the first user on the waiting list that an item has become available.
 *
 * @param   string  $id     Item ID
 */
function LIBRARY_notifyWaitlist($id = '')
{
    global $_TABLES,  $_CONF;

    // require a valid item ID
    $id = COM_sanitizeID($id);
    if ($id == '')
        return;

    // retrieve the first waitlisted user info.
    $sql = "SELECT w.id, w.uid, w.item_id, i.title, i.daysonhold,
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
    $template_dir = __DIR__ .
                    '/templates/notify/' . $name['language'];
    if (!file_exists($template_dir . '/item_avail.thtml')) {
        $template_dir = __DIR__ . '/templates/notify/english';
    }

    // Load the recipient's language.
    MO::init($A['language']);

    $T = new Template($template_dir);
    $T->set_file('message', 'item_avail.thtml');
    $T->set_var(array(
        'username'      => $username,
        'pi_url'        => $Config->get('url'),
        'item_id'       => $A['item_id'],
        'item_descrip'  => $A['title'],
        'daysonhold'    => $daysonhold,
    ) );
    $T->parse('output','message');
    $message = $T->finish($T->get_var('output'));

    COM_mail(
        $A['email'],
        _('Your requested library item is available'),
        $message,
        "{$_CONF['site_name']} <{$_CONF['site_mail']}>",
        true
    );
}


/**
 * Notify the librarian that an item has been requested.
 *
 * @param   string  $item_id    Item ID being requested
 * @param   integer $uid        User ID of requester.
 */
function LIBRARY_notifyLibrarian($item_id, $uid)
{
    global $_TABLES,  $_CONF;

    USES_lib_user();

    // require a valid item ID
    $item_id = COM_sanitizeID($item_id);
    if ($item_id == '') {
        return;
    }
    $user = COM_getDisplayName($uid);
    $grp_id = (int)Library\Config::getInstance()->get('grp_librarians');
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
    if ($Item->isNew()) return;   // invalid item id

    $msg = '<p>' . _('Someone has requested a library item.') . '</p>' . LB .
        '<p>' . _('Item Name') . ': ' . $Item->getTitle() . '</p>' . LB .
        '<p>' . _('Requested By') . ': ' . $user . '</p>' . LB;

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
 * Loads a custom language array.
 * If $requested is an array, the first valid language file is loaded.
 * If not, the $requested language file is loaded.
 * If $requested doesn't refer to a vailid language, then $_CONF['language']
 * is assumed.  If all else fails, english.php is loaded.
 *
 * After loading the base language file, the same filename is loaded from
 * language/custom, if available.  The admin can override language strings
 * by creating a language file in that directory.
 *
 * @param   mixed   $requested  A single or array of language strings
 * @return  array               $LANG_LIB, which is not global here.
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
    $langpath = __DIR__ . '/language';
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


/**
 * Provides the user selection options for the checkout form.
 *
 * @param   string  $item_id
 * @return  array       Array of user selections
 */
function LIBRARY_userSelect($item_id='')
{
    global $_TABLES;

    $retval = '';
    $wl_users = array();
    $sel_user = '';
    if ($item_id != '') {
        // Get the next user on the waiting list
        $sql = "SELECT wl.uid,u.fullname,u.username
                FROM {$_TABLES['library.waitlist']} wl
                LEFT JOIN {$_TABLES['users']} u
                    ON u.uid = wl.uid
                WHERE wl.item_id='" . COM_sanitizeId($item_id, false) . "'
                ORDER BY wl.dt ASC";
        $res = DB_query($sql,1);
        while ($A = DB_fetchArray($res, false)) {
            $wl_users[] = $A['uid'];
            if ($sel_user == '') {      // set the first user as selected
                $sel_user = $A['uid'];
                $sel = 'selected="selected"';
            } else {
                $sel = '';
            }
            $userdisplay = "{$A['fullname']} ({$A['username']}) &lt;== " .
                _('Next on Waiting List');
            $retval .= "<option value='{$A['uid']}' $sel>$userdisplay</option>\n";
        }
    }

    $sql = "SELECT uid, username, fullname
            FROM {$_TABLES['users']}
            WHERE uid > 1";
    if (!empty($wl_users)) {
        $sql .= ' AND uid NOT IN (' . implode(',', $wl_users) . ')';
    }
    $res = DB_query($sql,1);

    while ($A = DB_fetchArray($res, false)) {
        $userdisplay = "{$A['fullname']} ({$A['username']})";
        if ($sel_user == '') {
            $sel_user = $A['uid'];
            $sel = 'selected="selected"';
        } else {
            $sel = '';
        }
        $retval .= "<option value='{$A['uid']}' $sel>$userdisplay</option>\n";
    }
    return $retval;
}

?>
