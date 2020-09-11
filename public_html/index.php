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

use Library\Config;
use Library\MO;

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
$page = $action;    // Unless overridden by the action
switch ($action) {
case 'addwait':
    $Item = Library\Item::getInstance($id);
    if ($Item->canCheckout()) {
        if (!$Item->isNew()) {
            Library\Waitlist::Add($Item);
            if ($Item->isAvailable() && Config::get('notify_checkout') == 1) {
                LIBRARY_notifyLibrarian($id, $_USER['uid']);
            }
        }
        COM_refresh(Config::getInstance()->get('url'));
    } else {
        $content .= COM_showMessageText(MO::_('Access Denied'));
        $view = 'itemlist';
    }
    break;

case 'rmvwait':
    if (SEC_hasRights('library.checkout')) {
        Library\Waitlist::Remove($id);
        COM_refresh(Config::getInstance()->get('url'));
    } else {
        $content .= COM_showMessageText(MO::_('Access Denied'));
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
    $menu_opt = MO::_('Purchase History');
    break;

case 'detail':
    $P = new Library\Item($id);
    $params = array();
    if (isset($_GET['query'])) $params[] = 'query=' . $_GET['query'];
    if (isset($_GET['sortdir'])) $params[] = 'sortdir=' . $_GET['sortdir'];
    if (isset($_GET['type'])) $params[] = 'type=' . $_GET['type'];
    if (!empty($params)) {
        $P->SetListUrl(Config::getInstance()->get('url') . '/index.php?' . implode('&', $params));
    }
    $content .= $P->Detail();
    $menu_opt = MO::_('Item List');
    break;

case 'itemlist':
default:
    $cat = isset($_GET['category']) ? $_GET['category'] : 0;
    $content .= LIBRARY_ItemList($cat);
    $menu_opt = MO::_('Item List');
    break;
}

// Create the user menu
$menu = array();
$menu[MO::_('Item List')] = Config::getInstance()->get('url') . '/index.php';
if (SEC_hasRights('library.admin')) {
    $menu[MO::_('Admin Home')] = Config::getInstance()->get('admin_url') . '/index.php';
}

$display = LIBRARY_siteHeader();
$display .= $content;
$display .= LIBRARY_siteFooter();
echo $display;

?>
