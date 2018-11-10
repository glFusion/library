<?php
/**
 * Admin index page for the library plugin.
 * By default, lists products available for editing.
 *
 * @author      Lee Garner <lee@leegarner.com
 * @copyright   Copyright (c) 2009 Lee Garner
 * @package     library
 * @version     0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
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
    'mode', 'checkout', 'checkin', 'deleteitem', 'copyitem',
    'deletecatimage', 'deletecat', 'delete_img', 'deletemedia',
    'savemedia', 'saveitem', 'savecat',
    'edititem', 'editcat', 'editmedia',
    // views:
    'catlist', 'medialist', 'itemlist', 'pending',
    'checkoutform', 'checkinform', 'history', 'instances',
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
    $I = \Library\Item::getInstance($_POST['id']);
    $I->checkOut($_POST['uid']);
    COM_refresh($_CONF_LIB['admin_url'] . '/index.php?status=' . SESS_getVar('library.itemlist.status'));
    break;

case 'checkin':
    $I = \Library\Item::getInstance($_POST['id']);
    $I->checkIn($_POST['instance_id']);
    COM_refresh($_CONF_LIB['admin_url'] . '/index.php?status=' . SESS_getVar('library.itemlist.status'));
    break;

case 'deleteitem':
    // Item id can come from $_GET or $_POST
    $P = \Library\Item::getInstance($_REQUEST['id']);
    if (!$P->isUsed()) {
        $P->Delete();
        COM_refresh($_CONF_LIB['admin_url'] . '/index.php?status=' . SESS_getVar('library.itemlist.status'));
    } else {
        $content .= "Product has purchase records, can't delete.";
    }
    break;

case 'deleteinstance':
    // Instance ID only comes from $_GET
    $I = \Library\Instance::getInstance($_GET['id']);
    $I->Delete();
    COM_refresh($_CONF_LIB['admin_url'] . '/index.php?instances=x&item_id=' . $_GET['item_id']);;
    break;

case 'deletecatimage':
    $id = LGLIB_getVar($_GET, 'cat_id', 'integer');
    if ($id > 0) {
        $C = \Library\Category::getInstance($id);
        $C->DeleteImage();
        $view = 'editcat';
        $_REQUEST['id'] = $id;
    } else {
        $view = 'categories';
    }
    break;

case 'deletecat':
    if (!empty($LANG_LIB['deletecat'])) {
        $C = \Library\Category::getInstance($_REQUEST['id']);
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
    \Library\Item::DeleteImage($img_id);
    $view = 'edititem';
    break;

case 'savemedia':
    $M = \Library\MediaType::getInstance($_POST['id']);
    $M->Save($_POST);
    $view = 'medialist';
    break;

case 'deletemedia':
    \Library\MediaType::getInstance(LGLIB_getVar($_GET, 'id', 'integer'))->Delete();
    COM_refresh($_CONF_LIB['admin_url'] . '/index.php?medialist=x');
    break;

case 'saveitem':
    $P = \Library\Item::getInstance($_POST['id']);
    if (!$P->Save($_POST)) {
        $content .= LIBRARY_errMsg($P->PrintErrors());
        $view = 'edititem';
    } else {
        $view = 'itemlist';
    }
    break;

case 'savecat':
    $C = \Library\Category::getInstance($_POST['cat_id']);
    if (!$C->Save($_POST)) {
        $content .= LIBRARY_popupMsg($LANG_LIB['invalid_form']);
        $view = 'editcat';
    } else {
        COM_refresh($_CONF_LIB['admin_url'] . '/index.php?catlist');
    }
    break;

default:
    $view = $action;
    break;
}

switch ($view) {
case 'checkoutform':
    $content .= \Library\Item::checkoutForm($_REQUEST['id']);
    break;

case 'checkinform':
    $content .= \Library\Item::checkinForm($_REQUEST['id']);
    break;


case 'history':
    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
        $content .= LIBRARY_history($_REQUEST['id']);
    }
    break;

case 'edititem':
    $view ='itemlist';
    $id = LGLIB_getVar($_REQUEST, 'id');
    $P = \Library\Item::getInstance($id);
    // Pick any field.  If it exists, then this is probably a rejected save
    // so pre-populate the fields.
    if ($id == '' && isset($_POST['name'])) {
        $P->SetVars($_POST);
    }
    $content .= $P->showForm();
    break;

case 'copyitem':
    $view ='itemlist';
    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
    if (!empty($id)) {
        \Library\Item::makeClone($id);
    }
    echo COM_refresh($_CONF_LIB['admin_url']);
    break;

case 'editcat':
    $id = LGLIB_getVar($_REQUEST, 'id', 'integer');
    $C = \Library\Category::getInstance($id);
    if ($id == 0 && isset($_POST['dscp'])) {
        // Pick a field.  If it exists, then this is probably a rejected save
        $C->SetVars($_POST);
    }
    $content .= $C->showForm();
    break;

case 'editmedia':
    $view ='medialist';
    $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    $C = \Library\MediaType::getInstance($id);
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

case 'instances':
    $status = isset($_REQUEST['status']) ? (int)$_REQUEST['status'] : 0;
    $item_id = isset($_REQUEST['item_id']) ? $_REQUEST['item_id'] : '';
    $content .= LIBRARY_adminlist_Instances($item_id, $status);
    break;

case 'itemlist':
default:
    $status = isset($_REQUEST['status']) ? (int)$_REQUEST['status'] : 0;
    SESS_setVar('library.itemlist.status', $status);
    switch ($status) {
    case 0:         // All Items
    case 1:         // Available items
    case 3:         // Pending Actions
        $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
        $content .= LIBRARY_adminlist_Items($cat_id, $status);
        break;
    case 2:         // Checked-out Instances
    case 4:         // Overdue Instances
        // checked-out or overdue instances
        $item_id = isset($_REQUEST['item_id']) ? $_REQUEST['item_id'] : '';
        $content .= LIBRARY_adminlist_Instances($item_id, $status);
        break;
    }
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
 * Get the admin list of item instances.
 *
 * @param   string  $item_id    Item ID
 * @param   integer $status     Optional item status, to limit view
 * @return  string      HTML for admin list
 */
function LIBRARY_adminlist_Instances($item_id=0, $status=0)
{
    global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER, $LANG_ADMIN;

    $display = '';

    $sql = "SELECT inst.*, item.name FROM {$_TABLES['library.instances']} inst
            LEFT JOIN {$_TABLES['library.items']} item
                ON item.id = inst.item_id ";
    $stat_join = '';
    switch ($status) {
    case 0:     // All
        $stat_sql = ' WHERE 1=1 ';
        break;
    case 1:     // Available
        $stat_sql = ' WHERE inst.uid = 0 ';
        break;
    case 2:     // Checked Out
        $stat_sql = ' WHERE inst.uid > 0 ';
        break;
    case 3:     // Pending Actions, include available only
        $stat_sql = ' GROUP BY w.item_id HAVING count(w.id) > 0 ';
        $stat_join = "LEFT JOIN {$_TABLES['library.waitlist']} w
                ON item.id = w.item_id";
        break;
    case 4:     // Overdue
        $stat_sql = ' WHERE inst.due > 0 AND inst.due < UNIX_TIMESTAMP() ';
        break;
    }
    $sql .= $stat_join;
    $sql .= $stat_sql;
    if (!empty($item_id)) {
        $sql .= " AND inst.item_id = '" . DB_escapeString($item_id) . "'";
    }

    $header_arr = array(
        array(  'text'  => 'ID',
                'field' => 'instance_id',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['item_id'],
                'field' => 'item_id',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['checkout_user'],
                'field' => 'uid',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['checkedout'],
                'field' => 'checkout',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['dt_due'],
                'field' => 'due',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['checkin'],
                'field' => 'checkin',
                'sort'  => false,
            ),
        array(  'text'  => $LANG_ADMIN['delete'],
                'field' => 'delete',
                'sort'  => true,
            ),
    );

    $defsort_arr = array('field' => 'inst.due',
            'direction' => 'desc');

    $display .= COM_startBlock('', '',
                    COM_getBlockTemplate('_admin_block', 'header'));

    $query_arr = array(
        'table' => 'library.instances',
        'sql' => $sql,
        'query_fields' => array(),
        'default_filter' => '',
    );
    $filter = '';
    $text_arr = array(
        //'has_extras' => true,
        'form_url' => $_CONF_LIB['admin_url'] . '/index.php?status=' . $status,
    );
    $form_arr = LIBRARY_itemStatusForm($status, $item_id);
    $extras = array();
    $display .= ADMIN_list('library', 'LIBRARY_getAdminField_Instance',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, $extras, '', $form_arr);
    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $display;
}


/**
 * Product Admin List View.
 *
 * @param   integer $cat_id     Optional category to limit view
 * @param   integer $status     Optional status, to limit view
 */
function LIBRARY_adminlist_Items($cat_id = 0, $status = 0)
{
    global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER, $LANG_ADMIN;

    $sql = LIBRARY_admin_getSQL($cat_id, $status);

    $display = '';
    $header_arr = array(
        array(  'text'  => $LANG_ADMIN['edit'],
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
        array(  'text'  => $LANG_ADMIN['copy'],
                'field' => 'copy',
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
        array(  'text'  => $LANG_LIB['type'],
                'field' => 'typename',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['category'],
                'field' => 'cat_name',
                'sort'  => true,
            ),
        array(  'text'  => $LANG_LIB['available'],
                'field' => 'status',
                'sort'  => false,
                'align' => 'center',
            ),
        array(  'text'  => $LANG_LIB['history'],
                'field' => 'history',
                'sort'  => false,
            ),
        array(  'text'  => $LANG_LIB['checkout'],
                'field' => 'checkout',
                'sort'  => false,
            ),
        array(  'text'  => $LANG_LIB['checkin'],
                'field' => 'checkin',
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

    $query_arr = array(
        'table' => 'library.items',
        'sql' => $sql,
        'query_fields' => array('p.name',
                            'p.dscp'),
        'default_filter' => '',
    );
    $text_arr = array(
        //'has_extras' => true,
        'form_url' => $_CONF_LIB['admin_url'] . '/index.php?status=' . $status,
    );
    $form_arr = LIBRARY_itemStatusForm($status);
    $filter = '';
    $extras = array(
        'status'    => $status,
    );
    if (!isset($_REQUEST['query_limit'])) {
        $_GET['query_limit'] = 20;
    }

    $display .= '<div class="floatright">' . COM_createLink($LANG_LIB['new_item'],
        $_CONF_LIB['admin_url'] . '/index.php?edititem=0',
        array('class' => 'uk-button uk-button-success')
    ) . '</div>';
    $display .= ADMIN_list('library', 'LIBRARY_getAdminField_Item',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, $extras, '', $form_arr);

    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $display;
}


/**
 * Get an individual field for the Instance Admin screen.
 *
 * @param   string  $fieldname  Name of field (from the array, not the db)
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Array of all fields from the database
 * @param   array   $icon_arr   System icon array (not used)
 * @return  string              HTML for field display in the table
 */
function LIBRARY_getAdminField_Instance($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB, $_TABLES, $LANG_ADMIN;

    $retval = '';
    static $usernames = array();
    switch($fieldname) {
    case 'uid':
        if ($fieldvalue > 0) {
            if (!isset($usernames[$fieldvalue])) {
                $usernames[$fieldvalue] = COM_getDisplayName($fieldvalue);
            }
            $retval .= $usernames[$fieldvalue];
        }
        break;
    case 'checkout':
    case 'due':
        if ($fieldvalue > 0) {
            $dt = new Date($fieldvalue, $_CONF['timezone']);
            $retval .= $dt->format('Y-m-d', true);
        }
        break;
    case 'checkin':
        if ($A['uid'] > 0) {
            $retval .= COM_createLink('CheckIn',
                $_CONF_LIB['admin_url'] . '/index.php?checkinform=x&id=' . $A['item_id']);
        }
        break;
    case 'delete':
        if ($A['uid'] == 0) {
            $retval .= COM_createLink(
                '<i class="' . LIBRARY_getIcon('trash-o', 'danger') . '"></i>',
                $_CONF_LIB['admin_url']. '/index.php?deleteinstance=x&amp;id=' . $A['instance_id'],
                array('onclick'=>'return confirm(\''.$LANG_LIB['conf_delitem'].'\');',
                    'title' => $LANG_LIB['deleteitem'],
                    'class' => 'tooltip',
                )
            );
        }
        break;
    case 'item_id':
        $retval .= '<span title="' . htmlspecialchars($A['name']) . '" class="tooltip">' . $fieldvalue . '</span>';
        break;
    default:
        $retval .= $fieldvalue;
        break;
    }
    return $retval;
}


/**
 * Get an individual field for the Item Admin screen.
 *
 * @param   string  $fieldname  Name of field (from the array, not the db)
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Array of all fields from the database
 * @param   array   $icon_arr   System icon array (not used)
 * @return  string              HTML for field display in the table
 */
function LIBRARY_getAdminField_Item($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB, $_TABLES, $LANG_ADMIN;

    $retval = '';

    $avail = count(\Library\Instance::getAll($A['id'], LIB_STATUS_AVAIL));
    $out = count(\Library\Instance::getAll($A['id'], LIB_STATUS_OUT));
    $total = $avail + $out;

    switch($fieldname) {
    case 'id':
        $retval = COM_createLink($fieldvalue,
            $_CONF_LIB['admin_url'] . '/index.php?instances=x&item_id=' . $fieldvalue,
            array(
                'title' => $LANG_LIB['view_instances'],
                'class' => 'tooltip',
            ) );
        break;

    case 'edit':
        $retval .= COM_createLink(
                '<i class="' . LIBRARY_getIcon('edit') . '"></i>',
                $_CONF_LIB['admin_url'] . "/index.php?edititem=x&amp;id={$A['id']}"
            );
        break;

    case 'copy':
        $retval .= COM_createLink(
                '<i class="' . LIBRARY_getIcon('copy') . '"></i>',
                $_CONF_LIB['admin_url'] . "/index.php?copyitem=x&amp;id={$A['id']}"
            );
        break;

    case 'delete':
        if (!Library\Item::isUsed($A['id'])) {
            $retval .= COM_createLink(
                    '<i class="' . LIBRARY_getIcon('trash-o', 'danger') . '"></i>',
                $_CONF_LIB['admin_url']. '/index.php?deleteitem=x&amp;id=' . $A['id'],
                array('onclick'=>'return confirm(\''.$LANG_LIB['conf_delitem'].'\');',
                    'title' => $LANG_LIB['deleteitem'],
                    'class' => 'tooltip',
                )
            );
        }
        break;

    case 'enabled':
        $chk = $fieldvalue == 1 ? ' checked="checked"' : '';
        $retval .= "<input type=\"checkbox\" $chk value=\"1\" name=\"ena_check\"
                id=\"togenabled{$A['id']}\"
                onclick='LIBR_toggle(this,\"{$A['id']}\",\"enabled\",\"item\");'>".LB;
        break;

    case 'name':
        $retval = COM_createLink($fieldvalue,
                $_CONF_LIB['url'] . '/index.php?detail=x&id=' . $A['id'],
            array(
                'title' => $LANG_LIB['view_item'],
                'class' => 'tooltip',
            ) );
        break;

    case 'type':
        $retval = LGLIB_getVar($LANG_LIB['types'], $A['type'], 'string', 'Unknown');
        break;

    case 'status':
        $retval = $avail . ' / ' . $total;
        break;
        if ($fieldvalue == LIB_STATUS_OUT) {
            if ($A['due'] < LIBRARY_now()) {
                $cls = 'danger';
                $msg = $LANG_LIB['overdue'];
            } else {
                $cls = 'unknown';
                $msg = $LANG_LIB['checkedout'];
            }
        } elseif (isset($A['wait_count']) && $A['wait_count'] > 0) {
            $cls = 'warning';
            $msg = $LANG_LIB['waitlisted'];
        } elseif ($fieldvalue == LIB_STATUS_AVAIL) {
            $cls = 'ok';
            $msg = $LANG_LIB['available'];
        } else {
            $cls = 'unknown';
            $msg = '';
        }
        $retval .= '<i class="' . LIBRARY_getIcon('circle', $cls) .
            '" title="' . $msg . '" class="tooltip"></i>';
        break;

    case 'checkout':
        if ($avail > 0) {
            $retval .= COM_createLink('CheckOut',
                $_CONF_LIB['admin_url'] . '/index.php?checkoutform=x&id=' .
                $A['id']);
        }
        break;

    case 'checkin':
        if ($total > $avail) {
            $retval .= COM_createLink('CheckIn',
                $_CONF_LIB['admin_url'] . '/index.php?checkinform=x&id=' .
                $A['id']);
        }
        break;

    case 'history':
        if (DB_count($_TABLES['library.log'], 'item_id', $A['id']) > 0) {
            $retval .= COM_createLink('<i class="uk-icon uk-icon-file-text-o"></i>',
                $_CONF_LIB['admin_url'] . '/index.php?history=x&id=' . $A['id'],
                array(
                    'title' => $LANG_LIB['view_history'],
                    'class' => 'tooltip',
                ) );
        }
        break;

    default:
        $retval = htmlspecialchars($fieldvalue);
        break;
    }

    return $retval;
}


/**
 * Create the administrator menu
 *
 * @param   string  $mode   Current view mode
 * @return  string      Administrator menu
 */
function LIBRARY_adminMenu($mode='')
{
    global $_CONF, $_CONF_LIB, $LANG_ADMIN, $LANG_LIB;

    if ($mode == '') $mode = 'itemlist';
    $menu_arr = array(
        array(
            'url'   => $_CONF_LIB['admin_url'] . '/index.php',
            'text'  => $LANG_LIB['item_list'],
            'active' => $mode == 'itemlist' ? true : false,
        ),
        array(
            'url'  => $_CONF_LIB['admin_url'] . '/index.php?mode=catlist',
            'text' => $LANG_LIB['categories'],
            'active' => $mode == 'catlist' ? true : false,
        ),
        array(
            'url'   => $_CONF_LIB['admin_url'] . '/index.php?medialist=x',
            'text'  => $LANG_LIB['media_list'],
            'active' => $mode == 'medialist' ? true : false,
        ),
        array(
            'url'   => $_CONF_LIB['admin_url'] . '/index.php?overdue=x',
            'text'  => $LANG_LIB['overdue'],
            'active' => $mode == 'overdue' ? true : false,
        ),
        array(
            'url'   => $_CONF['site_admin_url'],
            'text'  => $LANG_ADMIN['admin_home'],
        ),
    );

    //$new_item_span = '<span class="libNewAdminItem">%s</span>';
    $admin_hdr = 'admin_item_hdr';
    /*if ($mode == 'itemlist' || $mode == '') {
        $menu_arr[] = array(
                    'url'  => $_CONF_LIB['admin_url'] . '/index.php?mode=edititem',
                    'text' => sprintf($new_item_span, $LANG_LIB['new_item']));
    }

    if ($mode == 'catlist') {
        $menu_arr[] = array(
                    'url'  => $_CONF_LIB['admin_url'] . '/index.php?mode=editcat',
                    'text' => $LANG_LIB['new_category']);
    } else {
        $menu_arr[] = array(
                    'url'  => $_CONF_LIB['admin_url'] . '/index.php?mode=catlist',
                    'text' => $LANG_LIB['categories']);
    }
     */
    /*if ($mode == 'medialist') {
        $menu_arr[] = array(
                    'url'  => $_CONF_LIB['admin_url'] . '/index.php?editmedia=x',
                    'text' => sprintf($new_item_span, $LANG_LIB['new_mediatype']));
        $admin_hdr = 'admin_media_hdr';
    }*/

    $T = new Template($_CONF_LIB['pi_path'] . '/templates');
    $T->set_file('title', 'library_title.thtml');
    $T->set_var('title', $LANG_LIB['admin_title']);
    $retval = $T->parse('', 'title');
    $retval .= ADMIN_createMenu($menu_arr, $LANG_LIB[$admin_hdr],
            plugin_geticon_library());

    return $retval;
}


/**
 * Category Admin List View.
 */
function LIBRARY_adminlist_Category()
{
    global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER, $LANG_ADMIN;

    $display = '';
    $sql = "SELECT cat.cat_id, cat.cat_name, cat.dscp, cat.enabled
            FROM {$_TABLES['library.categories']} cat";

    $header_arr = array(
        array('text' => $LANG_ADMIN['edit'],
                'field' => 'edit', 'sort' => false, 'align' => 'center'),
        array('text' => 'ID',
                'field' => 'cat_id', 'sort' => true),
        array('text' => $LANG_ADMIN['enabled'],
                'field' => 'enabled', 'sort' => false, 'align' => 'center'),
        array('text' => $LANG_LIB['category'],
                'field' => 'cat_name', 'sort' => true),
        array('text' => $LANG_LIB['dscp'],
                'field' => 'dscp', 'sort' => true),
        array('text' => $LANG_ADMIN['delete'],
                'field' => 'delete', 'sort' => false, 'align' => 'center'),
    );
    $display .= COM_startBlock('', '', COM_getBlockTemplate('_admin_block', 'header'));

    $defsort_arr = array('field' => 'cat_id',
            'direction' => 'asc');
    $query_arr = array('table' => 'library.categories',
        'sql' => $sql,
        'query_fields' => array('cat.name', 'cat.dscp'),
        'default_filter' => 'WHERE 1=1',
    );
    $text_arr = array(
        //'has_extras' => true,
        'form_url' => $_CONF_LIB['admin_url'] . '/index.php',
    );
    $form_arr = array();
    $filter = '';
    if (!isset($_REQUEST['query_limit'])) {
        $_GET['query_limit'] = 20;
    }

    $display .= '<div class="floatright">' . COM_createLink($LANG_LIB['new_category'],
        $_CONF_LIB['admin_url'] . '/index.php?editcat=0',
        array('class' => 'uk-button uk-button-success')
    ) . '</div>';

    $display .= ADMIN_list('library', 'LIBRARY_getAdminField_Category',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, '', '', $form_arr);

    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $display;
}


/**
 * Get an individual field for the category admin list.
 *
 * @param   string  $fieldname  Name of field (from the array, not the db)
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Array of all fields from the database
 * @param   array   $icon_arr   System icon array (not used)
 * @return  string              HTML for field display in the table
 */
function LIBRARY_getAdminField_Category($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB, $LANG_ADMIN;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval .= COM_createLink(
            '<i class="' . LIBRARY_getIcon('edit') . '"></i>',
            $_CONF_LIB['admin_url'] . "/index.php?mode=editcat&amp;id={$A['cat_id']}",
            array(
                'title' => $LANG_ADMIN['edit'],
                'class' => 'tooltip',
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
                $_CONF_LIB['admin_url']. '/index.php?deletecat&id=' . $A['cat_id'],
                array(
                    'onclick' => 'return confirm(\'' . $LANG_LIB['conf_delitem'] . '\');',
                    'title' => $LANG_LIB['deleteitem'],
                    'class' => 'tooltip',
                ));
        } else {
            $retval .= '<i class="tooltip ' . LIBRARY_getIcon('trash', 'unknown') .
                    '" title="' . $LANG_LIB['nodel_cat'] . '"></i>';
        }
        break;

    default:
        $retval = htmlspecialchars($fieldvalue);
        break;
    }
    return $retval;
}


/**
 *   Media Type Admin List View.
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
        array(  'text'  => $LANG_LIB['type'],
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
        'form_url' => $_CONF_LIB['admin_url'] . '/index.php',
    );
    $form_arr = array();
    $filter = '';
    if (!isset($_REQUEST['query_limit'])) {
        $_GET['query_limit'] = 20;
    }

    $display .= '<div class="floatright">' . COM_createLink($LANG_LIB['new_mediatype'],
        $_CONF_LIB['admin_url'] . '/index.php?editmedia=0',
        array('class' => 'uk-button uk-button-success')
    ) . '</div>';

    $display .= ADMIN_list('library', 'LIBRARY_getAdminField_MediaType',
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, '', '', $form_arr);

    $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $display;
}

/**
 * Get an individual field for the media type admin list.
 *
 * @param   string  $fieldname  Name of field (from the array, not the db)
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Array of all fields from the database
 * @param   array   $icon_arr   System icon array (not used)
 * @return  string              HTML for field display in the table
 */
function LIBRARY_getAdminField_MediaType($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB, $LANG_ADMIN;

    switch($fieldname) {
    case 'edit':
        $retval = COM_createLink(
                '<i class="' . LIBRARY_getIcon('edit') . '"></i>',
                $_CONF_LIB['admin_url'] . "/index.php?editmedia=x&amp;id={$A['id']}",
                array(
                    'class' => 'tooltip',
                    'title' => $LANG_ADMIN['edit'],
                ) );
        break;

    case 'delete':
        if (!Library\MediaType::isUsed($A['id'])) {
            $retval = COM_createLink(
                '<i class="' . LIBRARY_getIcon('trash', 'danger') . '"></i>',
                $_CONF_LIB['admin_url']. '/index.php?deletemedia=x&id=' . $A['id'],
                array(
                    'onclick'=>'return confirm(\''.$LANG_LIB['conf_delitem'].'\');',
                    'title' => $LANG_ADMIN['delete'],
                    'class' => 'tooltip',
                )
            );
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
 * Checkout History View.
 * Displays the purchase history for the current user.
 * Admins can view any user's histor, or all users.
 *
 * @param   string  $item_id    Library Item ID
 * @return  string      HTML for item history list
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
        FROM {$_TABLES['library.log']} AS t
        LEFT JOIN {$_TABLES['users']} AS u
            ON t.uid = u.uid
        LEFT JOIN {$_TABLES['users']} as uby
            ON t.doneby = uby.uid
        WHERE t.item_id = '$item_id'";

    $item_name = DB_getItem($_TABLES['library.items'], 'name', "id='$item_id'");

    $base_url = $_CONF_LIB['admin_url'];

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

    $defsort_arr = array(
        'field'     => 't.dt',
        'direction' => 'desc',
    );

    $display .= COM_startBlock(
        "{$LANG_LIB['trans_hist_title']} $item_name ($item_id)",
        '',
        COM_getBlockTemplate('_admin_block', 'header'));

    $query_arr = array('table' => 'library.log',
            'sql' => $sql,
            'query_fields' => array(),
            'default_filter' => '',
        );
    $text_arr = array(
        'has_extras' => false,
        'form_url' => $base_url . '/index.php?history&id=' . $item_id,
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
 * Get an individual field for the history screen.
 *
 * @param   string  $fieldname  Name of field (from the array, not the db)
 * @param   mixed   $fieldvalue Value of the field
 * @param   array   $A          Array of all fields from the database
 * @param   array   $icon_arr   System icon array (not used)
 * @return  string              HTML for field display in the table
 */
function LIBRARY_getTransHistoryField($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $_CONF_LIB, $LANG_LIB;

    $retval = '';

    switch($fieldname) {
    case 'id':
        $retval = COM_createLink($fieldvalue,
            $_CONF_LIB['url'] . '/index.php?detail=x&id=' . $fieldvalue);
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


/**
 * Get the SQL query for the item list.
 *
 * @param   integer $cat_id     Category ID
 * @param   integer $status     Optional status, default = "all"
 * @return  string      SQL query to get the items
 */
function LIBRARY_admin_getSQL($cat_id, $status = 0)
{
    global $_TABLES;

    $sql = "SELECT p.*,
                t.name AS typename,
                c.cat_name as cat_name
            FROM {$_TABLES['library.items']} p
            LEFT JOIN {$_TABLES['library.types']} t
                ON p.type = t.id
            LEFT JOIN {$_TABLES['library.categories']} c
                ON c.cat_id = p.cat_id";
    switch ($status) {
    case 0:     // All
        break;
    case 1:     // Available
        $sql .= "LEFT JOIN {$_TABLES['library.instances']} inst
                    ON p.id = inst.item_id
                WHERE inst.uid = 0 GROUP BY inst.item_id HAVING COUNT(inst.item_id) > 0";
        break;
    case 2:     // Checked Out
        $sql .= "LEFT JOIN {$_TABLES['library.instances']} inst
                    ON p.id = inst.item_id
                WHERE inst.uid > 0 GROUP BY inst.item_id HAVING COUNT(inst.item_id) > 0";
        break;
    case 3:     // Pending Actions, include available only
        $sql .= "LEFT JOIN {$_TABLES['library.waitlist']} w
                    ON p.id = w.item_id
                GROUP BY w.item_id HAVING count(w.id) > 0";
        break;
    case 4:     // Overdue
        //$sql .= "LEFT JOIN {$_TABLES['library.instances']} inst
        //            ON p.id = inst.item_id
        $sql .= "        WHERE inst.uid > 0 AND inst.due < UNIX_TIMESTAMP() ";
        $sql .= " GROUP BY  p.id ";
        break;
    }
    return $sql;
}


/**
 * Get the item status selection form.
 * Common to the item and instance displays.
 *
 * @param   integer $status     Item Status
 * @param   string  $item_id    Optional Item ID
 * @return  string      HTML for selection
 */
function LIBRARY_itemStatusForm($status, $item_id = '')
{
    global $LANG_LIB;

    for ($i = 0; $i < 5; $i++) {
        ${'sel_' . $i} = $i == $status ? 'selected="selected"' : '';
    }
    $form_arr = array(
        'top'    =>
                '<input type="hidden" name="item_id" value="' . $item_id . '" />' .
                '<select name="status" onchange="this.form.submit();">' .
                "<option value=\"0\" $sel_0>{$LANG_LIB['all']}</option>" .
                "<option value=\"1\" $sel_1>{$LANG_LIB['available']}</option>" .
                "<option value=\"2\" $sel_2>{$LANG_LIB['checkedout']}</option>" .
                "<option value=\"3\" $sel_3>{$LANG_LIB['pending']}</option>" .
                "<option value=\"4\" $sel_4>{$LANG_LIB['overdue']}</option>" .
                '</select>' . LB,
    );
    return $form_arr;
}

?>
