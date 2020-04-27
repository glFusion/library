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

use Library\_;

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
    'catlist', 'medialist', 'itemlist', 'pending', 'overdue',
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
    COM_refresh(Config::getInstance()->get('admin_url') . '/index.php?status=' . SESS_getVar('library.itemlist.status'));
    break;

case 'checkin':
    $I = \Library\Item::getInstance($_POST['id']);
    $I->checkIn($_POST['instance_id']);
    COM_refresh(Config::getInstance()->get('admin_url') . '/index.php?status=' . SESS_getVar('library.itemlist.status'));
    break;

case 'deleteitem':
    // Item id can come from $_GET or $_POST
    $P = \Library\Item::getInstance($_REQUEST['id']);
    if (!$P->isUsed()) {
        $P->Delete();
        COM_refresh(Config::getInstance()->get('admin_url') . '/index.php?status=' . SESS_getVar('library.itemlist.status'));
    } else {
        $content .= "Product has purchase records, can't delete.";
    }
    break;

case 'deleteinstance':
    // Instance ID only comes from $_GET
    $I = \Library\Instance::getInstance($_GET['id']);
    $I->Delete();
    COM_refresh(Config::getInstance()->get('admin_url') . '/index.php?instances=x&item_id=' . $_GET['item_id']);;
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
    $C = \Library\Category::getInstance($_REQUEST['id']);
    if (!$C->isUsed()) {
        $C->Delete();
    } else {
        $content .= _("Category has related products, can't delete.");
    }
    $view = 'catlist';
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
    COM_refresh(Config::getInstance()->get('admin_url') . '/index.php?medialist=x');
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
        $content .= LIBRARY_popupMsg(_('The submitted form has missing or invalid fields'));
        $view = 'editcat';
    } else {
        COM_refresh(Config::getInstance()->get('admin_url') . '/index.php?catlist');
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
    COM_refresh(Config::getInstance()->get('admin_url'));
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
    $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    $C = \Library\MediaType::getInstance($id);
    if ($id == 0 && isset($_POST['name'])) {
        // Pick a field.  If it exists, then this is probably a rejected save
        $C->SetVars($_POST);
    }
    $content .= $C->showForm();
    break;

case 'catlist':
    $content .= Library\Category::adminList();
    break;

case 'medialist':
    $content .= Library\MediaType::adminList();
    break;

case 'pending':
    $content .= Library\Item::adminList(0, true);
    break;

case 'instances':
    $status = isset($_REQUEST['status']) ? (int)$_REQUEST['status'] : 0;
    $item_id = isset($_REQUEST['item_id']) ? $_REQUEST['item_id'] : '';
    $content .= Library\Instance::adminList($item_id, $status);
    break;

case 'overdue':
case 'itemlist':
default:
    $status = isset($_REQUEST['status']) ? (int)$_REQUEST['status'] : 0;
    SESS_setVar('library.itemlist.status', $status);
    switch ($status) {
    case 0:         // All Items
    case 1:         // Available items
    case 3:         // Pending Actions
        $cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
        $content .= Library\Item::adminList($cat_id, $status);
        break;
    case 2:         // Checked-out Instances
    case 4:         // Overdue Instances
        // checked-out or overdue instances
        $item_id = isset($_REQUEST['item_id']) ? $_REQUEST['item_id'] : '';
        $content .= Library\Instance::adminList($item_id, $status);
        break;
    }
    break;
}

$display = COM_siteHeader();
//$display .= LIBRARY_adminMenu($view);
$display .= Library\Menu::Admin($view);
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
 * Checkout History View.
 * Displays the purchase history for the current user.
 * Admins can view any user's histor, or all users.
 *
 * @param   string  $item_id    Library Item ID
 * @return  string      HTML for item history list
 */
function LIBRARY_history($item_id)
{
    global $_CONF, $_TABLES, $_USER;

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

    $item_name = DB_getItem($_TABLES['library.items'], 'title', "id='$item_id'");

    $base_url = Library\Config::getInstance()->get('admin_url');

    $header_arr = array(
        array(  'text'  => _('Date/Time'),
                'field' => 'dt',
                'sort'  => true,
            ),
        array(  'text'  => _('Action'),
                'field' => 'trans_type',
                'sort' => true,
            ),
        array(  'text'  => _('By'),
                'field' => 'doneby',
                'sort'  => true,
            ),
        array(  'text'  => _('User Name'),
                'field' => 'uid',
                'sort' => true,
            ),
    );

    $defsort_arr = array(
        'field'     => 't.dt',
        'direction' => 'desc',
    );

    $display .= COM_startBlock(
        _('Transaction History for') . ": $item_name ($item_id)",
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
    global $_CONF;

    $retval = '';

    switch($fieldname) {
    case 'id':
        $retval = COM_createLink($fieldvalue,
            Library\Config::getInstance()->get('url') . '/index.php?detail=x&id=' . $fieldvalue);
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
 * Get the item status selection form.
 * Common to the item and instance displays.
 *
 * @param   integer $status     Item Status
 * @param   string  $item_id    Optional Item ID
 * @return  string      HTML for selection
 */
function LIBRARY_itemStatusForm($status, $item_id = '')
{
    for ($i = 0; $i < 5; $i++) {
        ${'sel_' . $i} = $i == $status ? 'selected="selected"' : '';
    }
    $form_arr = array(
        'top'    =>
                '<input type="hidden" name="item_id" value="' . $item_id . '" />' .
                '<select name="status" onchange="this.form.submit();">' .
                "<option value=\"0\" $sel_0>" . _('All') . "</option>" .
                "<option value=\"1\" $sel_1>" . _('Available') . "</option>" .
                "<option value=\"2\" $sel_2>" . _('Checked Out') . "</option>" .
                "<option value=\"3\" $sel_3>" . _('Pending') . "</option>" .
                "<option value=\"4\" $sel_4>" . _('Overdue') . "</option>" .
                '</select>' . LB,
    );
    return $form_arr;
}

?>
