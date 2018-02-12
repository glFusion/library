<?php
/**
*   Admin index page for the library plugin.
*   By default, lists products available for editing.
*
*   @author     Lee Garner <lee@leegarner.com
*   @copyright  Copyright (c) 2009 Lee Garner
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/


/** Import Required glFusion libraries */
require_once('../../../lib-common.php');

// Make sure the plugin is installed and enabled
if (!in_array('library', $_PLUGINS)) {
    COM_404();
    exit;
}

// Check for required permissions
if (!plugin_ismoderator_library()) {
    COM_accessLog("Unauthorized user {$_USER['username']} from "
                . "IP {$_SERVER['REMOTE_ADDR']} attempted to access the "
                . "library plugin at {$_SERVER['REQUEST_URI']}");
    COM_404();
    exit;
}

USES_library_functions();
USES_lib_admin();

$content = '';
$expected = array(
    // actions:
    'mode', 'checkout', 'checkin', 'deleteitem',
    'deletecatimage', 'deletecat', 'delete_img', 'deletemedia',
    'savemedia', 'saveitem', 'savecat',
    'edititem', 'editcat', 'editmedia',
    // views:
    'catlist', 'medialist', 'itemlist', 'pending',
    'checkoutform', 'history',
);
$action = 'itemlist';       // Default action
$view = '';
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}
if ($action == 'mode') $action = $actionval;

switch ($action) {
case 'checkout':
    $I = new Library\Item($_POST['id']);
    $I->checkOut($_POST['uid']);
    COM_refresh(LIBRARY_ADMIN_URL);
    break;

case 'checkin':
    $I = new Library\Item($_REQUEST['id']);
    $I->checkIn();
    LIBRARY_notifyWaitlist($_REQUEST['id']);
    COM_refresh(LIBRARY_ADMIN_URL);
    break;

case 'deleteitem':
    $P = new Library\Item($_REQUEST['id']);
    if (!$P->isUsed()) {
        $P->Delete();
        COM_refresh(LIBRARY_ADMIN_URL);
    } else {
        $content .= "Product has purchase records, can't delete.";
    }
    break;

case 'deletecatimage':
    $id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
    if ($id > 0) {
        $C = new Library\Category($id);
        $C->DeleteImage();
        $view = 'editcat';
        $_REQUEST['id'] = $id;
    } else {
        $view = 'categories';
    }
    break;

case 'deletecat':
    if (!empty($LANG_LIB['deletecat'])) {
        $C = new Library\Category($_REQUEST['id']);
        if (!$C->isUsed()) {
            $C->Delete();
        } else {
            $content .= "Category has related products, can't delete.";
        }
        $view = 'catlist';
    }
    break;

case 'delete_img':
    $img_id = (int)$_REQUEST['img_id'];
    Library\Item::DeleteImage($img_id);
    $view = 'edititem';
    break;

case 'savemedia':
    $M = new Library\MediaType($_POST['name']);
    $M->Save($_POST);
    $view = 'medialist';
    break;

case 'saveitem':
    $P = new Library\Item($_POST['oldid']);
    if (!$P->Save($_POST)) {
        $content .= LIBRARY_errMsg($P->PrintErrors());
        $view = 'edititem';
    } else {
        $view = 'itemlist';
    }
    break;

case 'savecat':
    $C = new Library\Category($_POST['cat_id']);
    if (!$C->Save($_POST)) {
        $content .= LIBRARY_popupMsg($LANG_LIB['invalid_form']);
        $view = 'editcat';
    } else {
        $view = 'catlist';
    }
    break;

default:
    $view = $action;
    break;
}

switch ($view) {
case 'checkoutform':
    $content .= LIBRARY_checkoutForm($_REQUEST['id']);
    break;

case 'history':
    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
        $content .= LIBRARY_history($_REQUEST['id']);
    }
    break;

case 'edititem':
    $view ='itemlist';
    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
    $P = new Library\Item($id);
    // Pick any field.  If it exists, then this is probably a rejected save
    // so pre-populate the fields.
    if ($id == '' && isset($_POST['name'])) {
        $P->SetVars($_POST);
    }
    $content .= $P->showForm();
    break;

case 'editcat':
    $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    $C = new Library\Category($id);
    if ($id == 0 && isset($_POST['description'])) {
        // Pick a field.  If it exists, then this is probably a rejected save
        $C->SetVars($_POST);
    }
    $content .= $C->showForm();
    break;

case 'editmedia':
    $view ='medialist';
    $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    $C = new Library\MediaType($id);
    if ($id == 0 && isset($_POST['name'])) {
        // Pick a field.  If it exists, then this is probably a rejected save
        $C->SetVars($_POST);
    }
    $content .= $C->showForm();
    break;

case 'catlist':
    $content .= LIBRARY_adminlist_Category();
    break;

case 'medialist':
    $content .= LIBRARY_adminlist_MediaType();
    break;

case 'pending':
    $content .= LIBRARY_adminlist_Items(0, true);
    break;

case 'itemlist':
default:
    $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
    $content .= LIBRARY_adminlist_Items($cat_id);
    break;
}

$display = COM_siteHeader();
$display .= LIBRARY_adminMenu($view);
if (!empty($_REQUEST['msg'])) {
    $display .= COM_startBlock('Message');
    $display .= $_REQUEST['msg'];
    $display .= COM_endBlock();
}

$display .= $content;
$display .= COM_siteFooter();
echo $display;
exit;


/**
*   Product Admin List View.
*
*   @param  integer $cat_id     Optional category to restrict view
*/
function LIBRARY_adminlist_Items($cat_id = 0, $pending = false)
{
    global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER, $LANG_ADMIN;

    $display = '';
    $sql1 = "SELECT
                p.id, p.name,
                p.type, p.enabled, p.status, p.uid,
                FROM_UNIXTIME(p.due) AS due,
                c.cat_id, c.cat_name,
                t.name AS typename,
                count(w.id) as waiting
            FROM {$_TABLES['library.items']} p
            LEFT JOIN {$_TABLES['library.categories']} c
                ON p.cat_id = c.cat_id
            LEFT JOIN {$_TABLES['library.types']} t
                ON p.type = t.id
            LEFT JOIN {$_TABLES['library.waitlist']} w
                ON w.id = p.id
            GROUP BY p.id";

    if ($pending) {
        $wait_vars = ', count(w.id) AS wait_count';
        $wait_join = "LEFT JOIN {$_TABLES['library.waitlist']} w
                ON p.id = w.item_id";
        $wait_where = ' GROUP BY w.item_id HAVING count(w.id) > 0 ';
    } else {
        $wait_vars = '';
        $wait_join = '';
        $wait_where = '';
    }

    $sql = "SELECT p.id, p.name,
                p.type, p.enabled, p.status, p.uid,
                FROM_UNIXTIME(p.due) AS due,
                t.name AS typename
            FROM {$_TABLES['library.items']} p
            LEFT JOIN {$_TABLES['library.types']} t
                ON p.type = t.id
            $wait_join
            WHERE 1=1 $wait_where";

    $header_arr = array(
        array(  'text'  => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
        array(  'text'  => 'ID',
                'field' => 'id',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['enabled'],
                'field' => 'enabled',
                'sort'  => false,
                'align' => 'center',
            ),
        array(  'text'  => $LANG_LIB['item_name'],
                'field' => 'name',
                'sort'  => true,
            ),
        //array('text' => $LANG_LIB['category'],
        //        'field' => 'cat_name', 'sort' => true),
        array(  'text'  => $LANG_LIB['type'],
                'field' => 'typename',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['checkout_to'],
                'field' => 'uid',
                'sort'  => false,
            ),
        array(  'text'  => $LANG_LIB['status_hdr'],
                'field' => 'status',
                'sort'  => false,
                'align' => 'center',
            ),
        array(  'text'  => $LANG_LIB['dt_due'],
                'field' => 'due',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['action_hdr'],
                'field' => 'action',
                'sort'  => false,
            ),
        array(  'text'  => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort'  => false,
                'align' => 'center',
            ),
    );

    $defsort_arr = array('field' => 'id',
            'direction' => 'asc');

    $display .= COM_startBlock('', '',
                    COM_getBlockTemplate('_admin_block', 'header'));

    if ($cat_id > 0) {
        $def_filter = "WHERE c.cat_id='$cat_id'";
    } else {
        $def_filter = 'WHERE 1=1';
    }
    $query_arr = array(
        'table' => 'library.items',
        'sql' => $sql,
        'query_fields' => array('p.name',
                            'p.description'),
        'default_filter' => $def_filter,
    );
    $text_arr = array(
        //'has_extras' => $pending ? false: true,
        'form_url' => LIBRARY_ADMIN_URL . '/index.php',
    );
    $form_arr = array();
    $filter = '';

    if (!isset($_REQUEST['query_limit']))
        $_GET['query_limit'] = 20;

    $display .= ADMIN_list('library', 'LIBRARY_getAdminField_Item',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, '', '', $form_arr);

    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $display;
}


/**
*   Get an individual field for the Item Admin screen.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array (not used)
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function LIBRARY_getAdminField_Item($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB, $_TABLES, $LANG_ADMIN;

    $retval = '';

    switch($fieldname) {
    case 'id':
        $retval = COM_createLink($fieldvalue,
            LIBRARY_ADMIN_URL . '/index.php?history=x&id=' . $fieldvalue);
        break;

    case 'edit':
        $retval .= COM_createLink(
                '<i class="' . LIBRARY_getIcon('edit') . '"></i>',
                LIBRARY_ADMIN_URL . "/index.php?edititem=x&amp;id={$A['id']}"
            );
        break;

    case 'delete':
        $retval .= COM_createLink(
                '<i class="' . LIBRARY_getIcon('trash-o', 'danger') . '"></i>',
            LIBRARY_ADMIN_URL. '/index.php?deleteitem=x&amp;id=' . $A['id'],
            array('onclick'=>'return confirm(\''.$LANG_LIB['conf_delitem'].'\');',
                'title' => $LANG_LIB['deleteitem'],
                'data-uk-tooltip' => '',
            )
        );
        break;

    case 'enabled':
        $chk = $fieldvalue == 1 ? ' checked="checked"' : '';
        $retval .= "<input type=\"checkbox\" $chk value=\"1\" name=\"ena_check\"
                id=\"togenabled{$A['id']}\"
                onclick='LIBR_toggle(this,\"{$A['id']}\",\"enabled\",\"item\");'>".LB;
        break;

    case 'name':
        $retval = COM_createLink($fieldvalue,
                LIBRARY_URL . '/index.php?detail=x&id=' . $A['id']);
        break;

    case 'type':
        $retval = $LANG_LIB['types'][$A['type']];
        break;

    case 'cat_name':
        $retval = COM_createLink($fieldvalue,
                LIBRARY_ADMIN_URL . '/index.php?cat_id=' . $A['cat_id']);
        break;

    case 'status':
        if ($fieldvalue == LIB_STATUS_OUT) {
            if ($A['due'] < LIBRARY_now()) {
                $cls = 'danger';
                $msg = $LANG_LIB['overdue'];
            } else {
                $cls = 'unknown';
                $msg = $LANG_LIB['not_available'];
            }
        } elseif (isset($A['wait_count']) && $A['wait_count'] > 0) {
            $cls = 'warning';
            $msg = $LANG_LIB['waitlisted'];
        } elseif ($fieldvalue == LIB_STATUS_AVAIL) {
            $cls = 'ok';
            $msg = $LANG_LIB['available'];
        }
        $retval .= '<i class="' . LIBRARY_getIcon('circle', $cls) .
            '" title="' . $msg . '" data-uk-tooltip></i>';
        break;

    case 'due':
        if ($fieldvalue > '1970-01-01') {
            $retval .= $fieldvalue;
        }
        break;

    case 'action':
        switch ($A['status']) {
        case LIB_STATUS_AVAIL:
            $retval = COM_createLink($LANG_LIB['checkout'],
                LIBRARY_ADMIN_URL . '/index.php?checkoutform=x&id=' .
                $A['id']);
            break;
        case LIB_STATUS_OUT:
            $retval = COM_createLink($LANG_LIB['checkin'],
                LIBRARY_ADMIN_URL . '/index.php?checkin=x&id=' .
                $A['id']);
            break;
        }
        break;

    case 'uid':
        if ($fieldvalue < 1) {
            $retval = '';
        } else {
            $name = COM_getDisplayName($fieldvalue);
            $retval = COM_createLink(htmlspecialchars($name),
                    $_CONF['site_url'].'/user.php?mode=profile?uid='.
                    $fieldvalue);
        }
        break;

    default:
        $retval = htmlspecialchars($fieldvalue);
        break;
    }

    return $retval;
}


/**
*   Create the administrator menu
*
*   @return string      Administrator menu
*/
function LIBRARY_adminMenu($mode='')
{
    global $_CONF, $LANG_ADMIN, $LANG_LIB;

    $menu_arr = array(
            array(  'url'   => $_CONF['site_admin_url'],
                    'text'  => $LANG_ADMIN['admin_home'],
            ),
            array(  'url'   => LIBRARY_ADMIN_URL . '/index.php',
                    'text'  => $LANG_LIB['item_list'],
            ),
            array(  'url'   => LIBRARY_ADMIN_URL . '/index.php?medialist=x',
                    'text'  => $LANG_LIB['media_list'],
            ),
            array(  'url'   => LIBRARY_ADMIN_URL . '/index.php?pending=x',
                    'text'  => $LANG_LIB['pending_actions'],
            ),
    );

    $new_item_span = '<span class="libNewAdminItem">%s</span>';
    $admin_hdr = 'admin_item_hdr';
    if ($mode == 'itemlist' || $mode == '') {
        $menu_arr[] = array(
                    'url'  => LIBRARY_ADMIN_URL . '/index.php?mode=edititem',
                    'text' => sprintf($new_item_span, $LANG_LIB['new_item']));
    }

    if ($mode == 'catlist') {
        $menu_arr[] = array(
                    'url'  => LIBRARY_ADMIN_URL . '/index.php?mode=editcat',
                    'text' => $LANG_LIB['new_category']);
    } else {
        $menu_arr[] = array(
                    'url'  => LIBRARY_ADMIN_URL . '/index.php?mode=catlist',
                    'text' => $LANG_LIB['category_list']);
    }

    if ($mode == 'medialist') {
        $menu_arr[] = array(
                    'url'  => LIBRARY_ADMIN_URL . '/index.php?editmedia=x',
                    'text' => sprintf($new_item_span, $LANG_LIB['new_mediatype']));
        $admin_hdr = 'admin_media_hdr';
    }

    $T = new Template(LIBRARY_PI_PATH . '/templates');
    $T->set_file('title', 'library_title.thtml');
    $T->set_var('title', $LANG_LIB['admin_title']);
    $retval = $T->parse('', 'title');
    $retval .= ADMIN_createMenu($menu_arr, $LANG_LIB[$admin_hdr],
            plugin_geticon_library());

    return $retval;
}



/**
*   Category Admin List View.
*/
function LIBRARY_adminlist_Category()
{
    global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER, $LANG_ADMIN;

    $display = '';
    $sql = "SELECT
                cat.cat_id, cat.cat_name, cat.description, cat.enabled,
                parent.cat_name as pcat
            FROM {$_TABLES['library.categories']} cat
            LEFT JOIN {$_TABLES['library.categories']} parent
            ON cat.parent_id = parent.cat_id";

    $header_arr = array(
        array('text' => $LANG_ADMIN['edit'],
                'field' => 'edit', 'sort' => false, 'align' => 'center'),
        array('text' => 'ID',
                'field' => 'cat_id', 'sort' => true),
        array('text' => $LANG_ADMIN['enabled'],
                'field' => 'enabled', 'sort' => false, 'align' => 'center'),
        array('text' => $LANG_LIB['category'],
                'field' => 'cat_name', 'sort' => true),
        array('text' => $LANG_LIB['description'],
                'field' => 'description', 'sort' => true),
        array('text' => $LANG_LIB['parent_cat'],
                'field' => 'pcat', 'sort' => true),
        array('text' => $LANG_ADMIN['delete'],
                'field' => 'delete', 'sort' => false, 'align' => 'center'),
    );
    $display .= COM_startBlock('', '', COM_getBlockTemplate('_admin_block', 'header'));

    $defsort_arr = array('field' => 'cat_id',
            'direction' => 'asc');
    $query_arr = array('table' => 'library.categories',
        'sql' => $sql,
        'query_fields' => array('cat.name', 'cat.description'),
        'default_filter' => 'WHERE 1=1',
    );
    $text_arr = array(
        'has_extras' => true,
        'form_url' => LIBRARY_ADMIN_URL . '/index.php',
    );
    $form_arr = array();
    $filter = '';
    if (!isset($_REQUEST['query_limit']))
        $_GET['query_limit'] = 20;

    $display .= ADMIN_list('library', 'LIBRARY_getAdminField_Category',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, '', '', $form_arr);

    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $display;
}


/**
*   Get an individual field for the category admin list.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array (not used)
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function LIBRARY_getAdminField_Category($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB, $LANG_ADMIN;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval .= COM_createLink(
                '<i class="' . LIBRARY_getIcon('edit') . '"></i>',
                LIBRARY_ADMIN_URL . "/index.php?mode=editcat&amp;id={$A['cat_id']}",
                array('class' => 'gl_mootip',
                    'title' => $LANG_ADMIN['edit'],
                    'data-uk-tooltip' => '',
                )
            );
        break;

    case 'enabled':
        $chk = $fieldvalue == 1 ? 'checked="checked"' : '';
        $retval .= "<input type=\"checkbox\" $chk value=\"1\" name=\"ena_check\"
                id=\"togenabled{$A['cat_id']}\" class=\"tooltip\" title=\"Enable/Disable\"
                onclick='LIBR_toggle(this,\"{$A['cat_id']}\",\"{$fieldname}\",".
                "\"category\");' />" . LB;
        break;

    case 'delete':
        if (!Library\Category::isUsed($A['cat_id'])) {
            $retval .= COM_createLink(
                '<i class="' . LIBRARY_getIcon('trash', 'danger') . '"></i>',
                LIBRARY_ADMIN_URL. '/index.php?deletecat&id=' . $A['cat_id'],
                array('class' => 'gl_mootip',
                    'onclick' => 'return confirm(\'' . $LANG_LIB['conf_delitem'] . '\');',
                    'title' => $LANG_LIB['deleteitem'],
                    'data-uk-tooltip' => '',
                ));
        }
        break;

    default:
        $retval = htmlspecialchars($fieldvalue);
        break;
    }
    return $retval;
}


/**
*   Category Admin List View.
*/
function LIBRARY_adminlist_MediaType()
{
    global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER, $LANG_ADMIN;

    $display = '';
    $sql = "SELECT  *
            FROM {$_TABLES['library.types']} ";

    $header_arr = array(
        array(  'text'  => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
        array(  'text'  => $LANG_LIB['name'],
                'field' => 'name',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort'  => false,
                'align' => 'center',
            ),
    );

    $defsort_arr = array('field' => 'id',
            'direction' => 'asc');

    $display .= COM_startBlock('', '', COM_getBlockTemplate('_admin_block', 'header'));

    $query_arr = array('table' => 'library.types',
        'sql' => $sql,
        'query_fields' => array('name'),
        'default_filter' => 'WHERE 1=1',
    );
    $text_arr = array(
        'has_extras' => true,
        'form_url' => LIBRARY_ADMIN_URL . '/index.php',
    );
    $form_arr = array();
    $filter = '';
    if (!isset($_REQUEST['query_limit']))
        $_GET['query_limit'] = 20;

    $display .= ADMIN_list('library', 'LIBRARY_getAdminField_MediaType',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, '', '', $form_arr);

    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $display;
}

/**
*   Get an individual field for the media type admin list.
*
*   @param  string  $fieldname  Name of field (from the array, not the db)
*   @param  mixed   $fieldvalue Value of the field
*   @param  array   $A          Array of all fields from the database
*   @param  array   $icon_arr   System icon array (not used)
*   @param  object  $EntryList  This entry list object
*   @return string              HTML for field display in the table
*/
function LIBRARY_getAdminField_MediaType($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB, $LANG_ADMIN;

    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink(
                '<i class="' . LIBRARY_getIcon('edit') . '"></i>',
                LIBRARY_ADMIN_URL . "/index.php?editmedia=x&amp;id={$A['id']}",
                array('class' => 'gl_mootip',
                    'data-uk-tooltip' => '',
                    'title' => $LANG_ADMIN['edit'],
                ) );
        break;

    case 'delete':
        if (!Library\MediaType::isUsed($A['id'])) {
            $retval = COM_createLink(
                '<i class="' . LIBRARY_getIcon('trash', 'danger') . '"></i>',
                LIBRARY_ADMIN_URL. '/index.php?deletemedia=x&id=' . $A['id'],
            array('onclick'=>'return confirm(\''.$LANG_LIB['conf_delitem'].'\');',
                'title' => $LANG_ADMIN['delete'],
                'data-uk-tooltip' => '',
            ) );
        } else {
            $retval = $LANG_LIB['in_use'];
        }
        break;

    default:
        $retval = htmlspecialchars($fieldvalue);
        break;
    }
    return $retval;
}


/**
*   Display the item checkout form
*
*   @param  string  $id     Item ID
*   @return string          HTML for the form
*/
function LIBRARY_checkoutForm($id)
{
    global $_CONF, $LANG_LIB, $_CONF_LIB;

    $I = new Library\Item($id);
    $item_id = $I->id;
    if ($item_id == '') {
        return 'Invalid Item Selected';
    }

    // Get the ISO language.  This is to load the correct language for
    // the calendar popup, so make sure a corresponding language file
    // exists.  Default to English if not found.
    $iso_lang = $_CONF['iso_lang'];
    if (!is_file($_CONF['path_html'] . $_CONF_LIB['pi_name'] .
        '/js/calendar/lang/calendar-' . $iso_lang . '.js')) {
        $iso_lang = 'en';
    }
    if ($I->maxcheckout < 1) $I->maxcheckout = (int)$_CONF_LIB['maxcheckout'];

    $T = LIBRARY_getTemplate('checkout_form', 'form');
    $T->set_var(array(
        'title'         => $LANG_LIB['admin_title'],
        'action_url'    => LIBRARY_ADMIN_URL . '/index.php',
        'pi_url'        => LIBRARY_URL,
        'item_id'       => $item_id,
        'item_name'     => $I->name,
        'item_desc'     => $I->description,
        'user_select'   => LIBRARY_userSelect($item_id),
        'due'           => LIBRARY_dueDate($I->maxcheckout)->format('Y-m-d'),
        'iso_lang'      => $iso_lang,
    ) );
    $T->parse('output', 'form');
    return $T->finish($T->get_var('output'));
}


function LIBRARY_userSelect($item_id='')
{
    global $_TABLES, $LANG_LIB;

    $sel_user = '';
    if ($item_id != '') {
        // Get the next user on the waiting list
        $sql = "SELECT uid FROM {$_TABLES['library.waitlist']}
                WHERE item_id='" . COM_sanitizeId($item_id, false) . "'
                ORDER BY dt ASC
                LIMIT 1";
        $res = DB_query($sql,1);
        if (DB_numRows($res) == 1) {
            $A = DB_fetchArray($res, false);
            $sel_user = $A['uid'];
        }
    }

    $sql = "SELECT uid, username, fullname
            FROM {$_TABLES['users']}
            WHERE uid > 1";
    $res = DB_query($sql,1);

    $retval = '';
    while ($A = DB_fetchArray($res, false)) {
        $userdisplay = "{$A['fullname']} ({$A['username']})";
        if ($A['uid'] == $sel_user) {
            $selected = 'selected="selected"';
            $userdisplay = $userdisplay . ' &lt;== ' . $LANG_LIB['next_on_list'];
        } else {
            $selected = '';
        }
        $retval .= "<option value='{$A['uid']}' $selected>$userdisplay</option>\n";
    }
    return $retval;
}


/**
*   Checkout History View.
*   Displays the purchase history for the current user.  Admins
*   can view any user's histor, or all users
*
*   @author     Lee Garner <lee@leegarner.com>
*   @package    library
*/
function LIBRARY_history($item_id)
{
    global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER;

    $display = '';
    $item_id = COM_sanitizeId($item_id, false);
    $sql = "SELECT
            t.*,
            u.username, u.fullname,
            uby.username as byuser, uby.fullname as byname
        FROM {$_TABLES['library.trans']} AS t
        LEFT JOIN {$_TABLES['users']} AS u
            ON t.uid = u.uid
        LEFT JOIN {$_TABLES['users']} as uby
            ON t.doneby = uby.uid
        WHERE t.item_id = '$item_id'";

    $item_name = DB_getItem($_TABLES['library.items'], 'name', "id='$item_id'");

    $base_url = LIBRARY_ADMIN_URL;

    $header_arr = array(
        array(  'text'  => $LANG_LIB['datetime'],
                'field' => 'dt',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['action_hdr'],
                'field' => 'trans_type',
                'sort' => true,
            ),
        array(  'text'  => $LANG_LIB['by'],
                'field' => 'doneby',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['username'],
                'field' => 'uid',
                'sort' => true,
            ),
    );

    $defsort_arr = array('field' => 't.dt',
            'direction' => 'desc');

    $display .= COM_startBlock(
        "{$LANG_LIB['trans_hist_title']} $item_name ($item_id)",
        '',
        COM_getBlockTemplate('_admin_block', 'header'));

    $query_arr = array('table' => 'library.trans',
            'sql' => $sql,
            'query_fields' => array(),
            'default_filter' => '',
        );
    $text_arr = array(
        'has_extras' => false,
        'form_url' => $base_url . '/index.php?history',
    );
    $form_arr = array();
    $filter = '';
    if (!isset($_REQUEST['query_limit']))
        $_GET['query_limit'] = 20;

    $display .= ADMIN_list('library', 'LIBRARY_getTransHistoryField',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, '', '', $form_arr);
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
function LIBRARY_getTransHistoryField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB;

    $retval = '';

    switch($fieldname) {
    case 'id':
        $retval = COM_createLink($fieldvalue,
            LIBRARY_URL . '/index.php?detail=x&id=' . $fieldvalue);
        break;

    case 'dt':
        $retval = date('Y-m-d H:i:s', $fieldvalue);
        break;

    case 'doneby':
        $retval = $A['byuser'];
        if (!empty($A['byname'])) {
            $retval .= '&nbsp;&nbsp;(' . $A['byname'] . ')';
        }
        //$retval = COM_getDisplayName($fieldvalue);
        break;

    case 'uid':
        $retval = $A['username'];
        if (!empty($A['fullname'])) {
            $retval .= '&nbsp;&nbsp;(' . $A['fullname'] . ')';
        }
        break;

    default:
        $retval = htmlspecialchars($fieldvalue);
        break;
    }

    return $retval;
}

?>
