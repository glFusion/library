<?php
/**
 * Administrative AJAX functions for the Library plugin
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

use Library\MO;

// This is for administrators only.  It's called by Javascript,
// so don't try to display a message
if (!SEC_hasRights('library.admin')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the classifieds admin ajax function.");
    exit;
}

$retval = array();
switch ($_POST['action']) {
case 'toggle':
    $newval = NULL;
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
    if ($newval !== NULL) {
        if ($newval != $_POST['oldval']) {
            $message = MO::_('Item Updated');
        } else {
            $message = MO::_('Item Unchanged');
        }
        $retval = array(
            'id'    => $_POST['id'],
            'newval' => $newval,
            'statusMessage' => $message,
        );
    }
    break;

case 'lookup':
    $isbn = isset($_POST['isbn']) ? $_POST['isbn'] : '';
    if (empty($isbn)) exit;
    $status = PLG_invokeService('astore', 'getiteminfo',
        array(
            'keytype' => 'isbn',
            'keyval' => $isbn,
        ),
        $item,
        $svc_msg
    );
    if ($status == PLG_RET_OK) {
        if (is_array($item)) $item = array_shift($item);
        if (is_array($item->EditorialReviews->EditorialReview)) {
            $review = $item->EditorialReviews->EditorialReview[0];
        } else {
            $review = $item->EditorialReviews->EditorialReview;
        }
        if (is_array($item->ItemAttributes->Author)) {
            $by_statement = implode(', ', $item->ItemAttributes->Author);
        } else {
            $by_statement = $item->ItemAttributes->Author;
        }
        $retval = array(
            'error' => '',
            'author' => $item->ItemAttributes->Author,
            'by_statement' => $by_statement,
            'title' => $item->ItemAttributes->Title,
            'publisher' => $item->ItemAttributes->Publisher,
            'publish_date' => $item->ItemAttributes->PublicationDate,
            'dscp' => $review->Content,
        );
    } else {
        $retval = array(
            'error' => "Astore lookup error for $isbn",
        );
    }
    break;

case 'checkinoutform':
    $item_id = isset($_POST['item_id']) ? $_POST['item_id'] : '';
    $ck_type = isset($_POST['ck_type']) ? $_POST['ck_type'] : '';
    if ($ck_type == 'checkout') {
        $retval['content'] = \Library\Item::checkoutForm($item_id, true);
    } else {
        $retval['content'] = \Library\Item::checkinForm($item_id, true);
    }
    if ($retval['content'] == '') {
        // Get count of available instances
        $retval['error'] = 'Invalid form returned';
        $retval['status'] = 1;
    } else {
        $retval['status'] = 0;
    }
    break;

case 'docheckout':
    $item_id = isset($_POST['id']) ? $_POST['id'] : '';
    $uid = isset($_POST['uid']) ? $_POST['uid'] : '0';
    $due_dt = isset($_POST['due_dt']) ? $_POST['due_dt'] : '';
    if ($uid == 0) {
        $retval['error'] = 'Invalid User ID';
        break;
    } else {
        if ($due_dt == '') {
            $due_dt = LIBRARY_dueDate();
        }
        $I = Library\Item::getInstance($item_id);
        $I->checkOut($uid);
        $retval['content'] = $I->AvailBlock();
        $retval['item_id'] = $item_id;  // needed to update the right avail blk
        $retval['status'] = 0;
    }
    //COM_errorLog(print_r($retval,true));
    break;

case 'docheckin':
    $item_id = isset($_POST['id']) ? $_POST['id'] : '';
    $instance_id = isset($_POST['instance_id']) ? $_POST['instance_id'] : '0';
    //COM_errorLog("checking in $item_id, $instance_id");
    $I = Library\Item::getInstance($item_id);
    $I->checkIn($instance_id);
    $retval['content'] = $I->AvailBlock();
    $retval['item_id'] = $item_id;  // needed to update the right avail blk
    $retval['status'] = 0;
    break;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
//A date in the past
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
echo json_encode($retval);

?>
