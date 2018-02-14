<?php
/**
*   Common Guest-Facing AJAX functions.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Include required glFusion common functions */
require_once '../lib-common.php';

// This is for administrators only.  It's called by Javascript,
// so don't try to display a message
if (!SEC_hasRights('library.checkout')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the classifieds admin ajax function.");
    exit;
}
$error = 0;
$Item = new Library\Item($_POST['item_id']);
if ($Item->isNew) {
    $error = 1;
}
if ($error == 0) {
    switch ($_POST['action']) {
    case 'addwait':
        Library\Waitlist::Add($Item);
        $html = $Item->AvailBlock();
        break;
    case 'rmvwait':
        Library\Waitlist::Remove($_POST['item_id']);
        $html = $Item->AvailBlock();
        break;
    }
}

$retval = array(
    'item_id' => $Item->id,
    'html' => $html,
    'error' => $error,
);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
//A date in the past
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo json_encode($retval);

?>
