<?php
/**
 *  Index page for users of the Library plugin
 *
 *  By default displays available items along with links to check out and
 *  detailed item views
 *
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

/** Require core glFusion code */
require_once '../lib-common.php';

// If plugin is installed but not enabled, display an error and exit gracefully
if (!in_array('library', $_PLUGINS)) {
    COM_404();
}

// Import plugin-specific functions
USES_library_functions();

// Ensure sufficient privs to read this page 
library_access_check();

/*$vars = array('msg' => 'text',
              'category' => 'number' );
library_filterVars($vars, $_REQUEST);*/

$content = '';
$expected = array('mode', 'addwait', 'rmvwait', 'thanks',
    'history', 'detail', 'itemlist',
);
$action = 'view';       // Default action
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
//$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'list';
//$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : $mode;

switch ($action) {
case 'addwait':
    $id = COM_sanitizeID($_REQUEST['id'], false);
    $uid = (int)$_USER['uid'];
    if (!empty($id) || $uid > 1) {
        $status = DB_query("INSERT INTO {$_TABLES['library.waitlist']}
            VALUES (0, NOW(), '$id', $uid)", 1);
    }
    $status = DB_getItem($_TABLES['library.items'], 'status', "id='$id'");
    if ($status == LIB_STATUS_AVAIL && $_CONF_LIB['notify_checkout'] == 1) {
        LIBRARY_notifyLibrarian($id, $uid);
    }
    $page = 'itemlist';
    break;

case 'rmvwait':
    $id = COM_sanitizeID($_REQUEST['id'], false);
    $uid = (int)$_USER['uid'];
    if (!empty($id) || $uid > 1) {
        $status = DB_delete($_TABLES['library.waitlist'],
            array('item_id', 'uid'), array($id, $uid));
    }
    $page = 'itemlist';
    break;


case 'thanks':
    $T = new Template($_CONF['path'] . 'plugins/library/templates');
    $T ->set_file(array('msg'   => 'thanks_for_order.thtml'));
    $T->set_var(array(
        'site_name'     => $_CONF['site_name'],
        'payment_date'  => $_POST['payment_date'],
        'currency'      => $_POST['mc_currency'],
        'mc_gross'      => $_POST['mc_gross'],
        'library_url'    => $_CONF_LIB['library_url'],
    ) );
    
    $content .= COM_showMessageText($T->parse('output', 'msg'), 
                $LANG_LIB['thanks_title']);
    $page = 'productlist';
    break;

default:
    $page = $action;
    break;
}


switch ($page) {
case 'history':
    $content .= LIBRARY_history();
    $menu_opt = $LANG_LIB['purchase_history'];
    break;

case 'detail':
    $P = new Library\Item($_REQUEST['id']);
    $params = array();
    if (isset($_GET['query'])) $params[] = 'query=' . $_GET['query'];
    if (isset($_GET['sortdir'])) $params[] = 'sortdir=' . $_GET['sortdir'];
    if (isset($_GET['type'])) $params[] = 'type=' . $_GET['type'];
    if (!empty($params)) {
        $P->SetListUrl(LIBRARY_URL . '/index.php?' . implode('&', $params));
    }
    $content .= $P->Detail();
    $menu_opt = $LANG_LIB['item_list'];
    break;

case 'itemlist':
default:
    $cat = isset($_GET['category']) ? $_GET['category'] : 0;
    $content .= LIBRARY_ItemList($cat);
    $menu_opt = $LANG_LIB['item_list'];
    break;
}

// Create the user menu
$menu = array();
$menu[$LANG_LIB['item_list']] = LIBRARY_URL . '/index.php';
if (SEC_hasRights('library.admin')) {
    $menu[$LANG_LIB['mnu_admin']] = LIBRARY_ADMIN_URL . '/index.php';
}


$display = LIBRARY_siteHeader();
$T = new Template(LIBRARY_PI_PATH . '/templates');
$T->set_file('title', 'library_title.thtml');
$T->set_var('title', $LANG_LIB['main_title']);
$display .= $T->parse('', 'title');
$display .= $content;
$display .= LIBRARY_siteFooter();
echo $display;

?>
