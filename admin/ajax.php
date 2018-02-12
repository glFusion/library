<?php
/**
*   Common AJAX functions.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

// This is for administrators only.  It's called by Javascript,
// so don't try to display a message
if (!SEC_hasRights('library.admin')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the classifieds admin ajax function.");
    exit;
}
$newval = NULL;
switch ($_POST['action']) {
case 'toggle':
    switch ($_POST['component']) {
    case 'item':
        switch ($_POST['type']) {
        case 'enabled':
            $newval = Library\Item::toggleEnabled($_POST['oldval'], $_POST['id']);
            break;
         default:
            exit;
        }
        break;
    case 'category':
        switch ($_POST['type']) {
        case 'enabled':
            $newval = Library\Category::toggleEnabled($_POST['oldval'], $_POST['id']);
            break;
         default:
            exit;
        }
        break;
    default:
        exit;
    }
}

if ($newval !== NULL) {
    if ($newval != $_POST['oldval']) {
        $message = $LANG_LIB['item_updated'];
    } else {
        $message = $LANG_LIB['item_nochange'];
    }
    $retval = array(
            'id'    => $_POST['id'],
            'newval' => $newval,
            'statusMessage' => $message,
    );

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    //A date in the past
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    echo json_encode($retval);
}

?>
