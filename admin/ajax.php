<?php
//  $Id: ajax.php 2 2009-12-30 04:11:52Z root $
/**
 *  Common AJAX functions.
 *
 *  @author     Lee Garner <lee@leegarner.com>
 *  @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
 *  @package    library
 *  @version    0.0.1
 *  @license    http://opensource.org/licenses/gpl-2.0.php 
 *  GNU Public License v2 or later
 *  @filesource
 */

/** Include required glFusion common functions */
require_once '../../../lib-common.php';

// This is for administrators only.  It's called by Javascript,
// so don't try to display a message
if (!SEC_hasRights('library.admin')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the classifieds admin ajax function.");
    exit;
}

switch ($_GET['action']) {
case 'toggle':
    switch ($_GET['component']) {
    case 'item':
        USES_library_class_item();

        switch ($_GET['type']) {
        case 'enabled':
            $newval = LibraryItem::toggleEnabled($_REQUEST['oldval'], $_REQUEST['id']);
            break;

         default:
            exit;
        }

        $img_url = LIBRARY_URL . '/images/';
        $img_url .= $newval == 1 ? 'on.png' : 'off.png';

        header('Content-Type: text/xml');
        header("Cache-Control: no-cache, must-revalidate");
        //A date in the past
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        echo '<?xml version="1.0" encoding="ISO-8859-1"?>
        <info>'. "\n";
        echo "<newval>$newval</newval>\n";
        echo "<id>{$_REQUEST['id']}</id>\n";
        echo "<type>{$_REQUEST['type']}</type>\n";
        echo "<component>{$_REQUEST['component']}</component>\n";
        echo "<imgurl>$img_url</imgurl>\n";
        echo "<baseurl>" . LIBRARY_ADMIN_URL . "</baseurl>\n";
        echo "</info>\n";
        break;

    case 'category':
        USES_library_class_category();

        switch ($_GET['type']) {
        case 'enabled':
            $newval = Category::toggleEnabled($_REQUEST['oldval'], $_REQUEST['id']);
            break;

         default:
            exit;
        }

        $img_url = LIBRARY_URL . '/images/';
        $img_url .= $newval == 1 ? 'on.png' : 'off.png';

        header('Content-Type: text/xml');
        header("Cache-Control: no-cache, must-revalidate");
        //A date in the past
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

        echo '<?xml version="1.0" encoding="ISO-8859-1"?>
        <info>'. "\n";
        echo "<newval>$newval</newval>\n";
        echo "<id>{$_REQUEST['id']}</id>\n";
        echo "<type>{$_REQUEST['type']}</type>\n";
        echo "<component>{$_REQUEST['component']}</component>\n";
        echo "<imgurl>$img_url</imgurl>\n";
        echo "<baseurl>" . LIBRARY_ADMIN_URL . "</baseurl>\n";
        echo "</info>\n";
        break;

    default:
        exit;
    }

}

?>
