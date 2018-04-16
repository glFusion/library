<?php
/**
*   Index page for users of the Library plugin
*
*   By default displays available items along with links to check out and
*   detailed item views
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

// Only logged-in uses can view items
if (!SEC_hasRights('library.view,library.admin', 'OR')) {
    echo LIBRARY_siteHeader();
    echo SEC_loginRequiredForm();
    echo LIBRARY_siteFooter();
    exit;
}

$content = '';
COM_setArgNames(array('action', 'id'));
$action = COM_getArgument('action');
$actionval = '';
if (!empty($action)) {
    $id = COM_sanitizeID(COM_getArgument('id'));
} else {
    $expected = array('mode', 'addwait', 'rmvwait',
        'history', 'detail', 'itemlist',
    );
    $action = 'view';       // Default action
    $view = '';
    $id = isset($_REQUEST['id']) ? COM_sanitizeId($_REQUEST['id']) : '';
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
}
if ($action == 'mode') $action = $actionval;
switch ($action) {
case 'addwait':
    if (SEC_hasRights('library.checkout')) {
        $Item = new Library\Item($id);
        if (!$Item->isNew) {
            Library\Waitlist::Add($Item);
            if ($Item->status == LIB_STATUS_AVAIL && $_CONF_LIB['notify_checkout'] == 1) {
                LIBRARY_notifyLibrarian($id, $_USER['uid']);
            }
        }
        echo COM_refresh($_CONF_LIB['url']);
    } else {
        $content .= COM_showMessageText($LANG_LIB['access_denied']);
        $view = 'itemlist';
    }
    break;

case 'rmvwait':
    if (SEC_hasRights('library.checkout')) {
        Library\Waitlist::Remove($id);
        echo COM_refresh($_CONF_LIB['url']);
    } else {
        $content .= COM_showMessageText($LANG_LIB['access_denied']);
        $view = 'itemlist';
    }
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
    $P = new Library\Item($id);
    $params = array();
    if (isset($_GET['query'])) $params[] = 'query=' . $_GET['query'];
    if (isset($_GET['sortdir'])) $params[] = 'sortdir=' . $_GET['sortdir'];
    if (isset($_GET['type'])) $params[] = 'type=' . $_GET['type'];
    if (!empty($params)) {
        $P->SetListUrl($_CONF_LIB['url'] . '/index.php?' . implode('&', $params));
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
$menu[$LANG_LIB['item_list']] = $_CONF_LIB['url'] . '/index.php';
if (SEC_hasRights('library.admin')) {
    $menu[$LANG_LIB['mnu_admin']] = $_CONF_LIB['admin_url'] . '/index.php';
}

$display = LIBRARY_siteHeader();
$T = new Template($_CONF_LIB['pi_path'] . '/templates');
$T->set_file('title', 'library_title.thtml');
$T->set_var('title', $LANG_LIB['main_title']);
$display .= $T->parse('', 'title');
$display .= $content;
$display .= LIBRARY_siteFooter();
echo $display;

?>
