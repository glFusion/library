<?php
/**
 * Language file for the Library plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

global $_CONF_LIB;

//$LANG_LOCALE='de_DE';

$domain = $_CONF_LIB['pi_name'];
$results = setlocale(LC_MESSAGES, $LANG_LOCALE);
if ($results) {
    $r1 = bind_textdomain_codeset($domain, 'UTF-8');
    $r2 = bindtextdomain($domain, __DIR__ . '/locale');
}

/**
 * Global array to hold all plugin-specific configuration items.
 */
$LANG_LIB = array (
'plugin'            => dgettext('library', 'Library'),
'main_title'        => dgettext('library', 'Library Items'),
'admin_title'       => dgettext('library', 'Library Administration'),
'srchtitle'         => dgettext('library', 'Library'),
'category'          => dgettext('library', 'Category'),
'categories'        => dgettext('library', 'Categories'),
'mnu_library'       => dgettext('library', 'Library'),
'mnu_admin'         => dgettext('library', 'Admin'),
'item_id'           => dgettext('library', 'Item ID or SKU'),
'item_name'         => dgettext('library', 'Name or Title'),
'subtitle'          => dgettext('library', 'Subtitle'),
'qty'               => dgettext('library', 'Qty'),
'dscp'              => dgettext('library', 'Description'),
'short_dscp'        => dgettext('library', 'Short Description'),
'publisher'         => dgettext('library', 'Publisher'),
'pub_date'          => dgettext('library', 'Date Published'),
'author'            => dgettext('library', 'Author'),
'keywords'          => dgettext('library', 'Keywords'),
'exp_time_days'     => dgettext('library', 'Expiration Time (days)'),
'maxcheckout'       => dgettext('library', 'Max Checkout Days'),
'txn_id'            => dgettext('library', 'Txn ID'),
'daysonhold'        => dgettext('library', 'Days to hold for waitlist'),
'download'          => dgettext('library', 'Download'),
'downloadable'      => dgettext('library', 'Downloadable'),
'price'             => dgettext('library', 'Price'),
'history'           => dgettext('library', 'History'),
'view_history'      => dgettext('library', 'View item history'),
'type'              => dgettext('library', 'Media Type'),
'new_item'          => dgettext('library', 'New Item'),
'new_category'      => dgettext('library', 'New Category'),
'item_list'         => dgettext('library', 'Item List'),
'media_list'        => dgettext('library', 'Media Types'),
'new_mediatype'     => dgettext('library', 'New Media Type'),
'category_list'     => dgettext('library', 'Category List'),
'admin_item_hdr'    => dgettext('library', 'Manage library items.  You can add, modify, and delete items.  You can also check items out or in from this screen, as well as disable items (make them invisible in the catalog).'),
'admin_media_hdr'   => dgettext('library', 'Manage and edit media types.  Media types can only be deleted if they\'re not referenced by any library items.'),
'username'          => dgettext('library', 'User Name'),
'status_hdr'        => dgettext('library', 'Status'),
'purchaser'         => dgettext('library', 'Purchaser'),
'ip_addr'           => dgettext('library', 'IP Address'),
'datetime'          => dgettext('library', 'Date/Time'),
'verified'          => dgettext('library', 'Verified'),
'ipn_data'          => dgettext('library', 'Full IPN Data'),
'view_cart'         => dgettext('library', 'View Cart'),
'images'            => dgettext('library', 'Images'),
'cat_image'         => dgettext('library', 'Category Image'),
'click_to_enlarge'  => dgettext('library', 'Click to Enlarge Image'),
'enabled'           => dgettext('library', 'Enabled'),
'featured'          => dgettext('library', 'Featured'),
'taxable'           => dgettext('library', 'Taxable'),
'savetype'          => dgettext('library', 'Save Type'),
'deletetype'        => dgettext('library', 'Delete Type'),
'thanks_title'      => dgettext('library', 'Thank you for your order!'),
'yes'               => dgettext('library', 'Yes'),
'no'                => dgettext('library', 'No'),
'closed'            => dgettext('library', 'Closed'),
'true'              => dgettext('library', 'True'),
'false'             => dgettext('library', 'False'),
'info'              => dgettext('library', 'Information'),
'warning'           => dgettext('library', 'Warning'),
'error'             => dgettext('library', 'Error'),
'alert'             => dgettext('library', 'Alert'),
'login_req'         => dgettext('library', 'Please log in to view options.'),
'invalid_item_id' => dgettext('library', 'An invalid item ID was requested'),
'access_denied_msg' => dgettext('library', 'You do not have access to this page. <p />' .
    'If you believe you have reached this message in error, ' .
    'please contact your site administrator.<p />' .
    'All attempts to access this page are logged.'),
'access_denied'     => dgettext('library', 'Access Denied'),
'select_file'       => dgettext('library', 'Select File'),
'or_upload_new'     => dgettext('library', 'Or, upload a new file'),
'invalid_form'      => dgettext('library', 'The submitted form has missing or invalid fields'),
'item_type'         => dgettext('library', 'Item Type'),
'edit'              => dgettext('library', 'Edit'),
'create_category'   => dgettext('library', 'Create a New Category'),
'cat_name'          => dgettext('library', 'Category Name'),
'parent_cat'        => dgettext('library', 'Parent Category'),
'top_cat'           => dgettext('library', '-- Top --'),
'saveitem'          => dgettext('library', 'Save Item'),
'deleteitem'        => dgettext('library', 'Delete Item'),
'nodel_cat'         => dgettext('library', 'Cannot delete categories that are in use.'),
'nodel_type'        => dgettext('library', 'Unable to delete this media type'),
'savecat'           => dgettext('library', 'Save Category'),
'deletecat'         => dgettext('library', 'Delete Category'),
'product_id'        => dgettext('library', 'Product ID'),
'other_func'        => dgettext('library', 'Other Functions'),
'clearform'         => dgettext('library', 'Reset Form'),
'indicate_req_fld'  => dgettext('library', 'Indicates a required field'),
'item_info'         => dgettext('library', 'Item Information'),
'delete_image'      => dgettext('library', 'Delete Image'),
'sort'              => dgettext('library', 'Sort'),
'search'            => dgettext('library', 'Search'),
'dt_add'            => dgettext('library', 'Date Added'),
'ascending'         => dgettext('library', 'Ascending'),
'descending'        => dgettext('library', 'Descending'),
'sortdir'           => dgettext('library', 'Sort Direction'),
'comments'          => dgettext('library', 'Comments'),
'conf_addwaitlist'  => dgettext('library', 'Are you sure you want to reserve this item?'),
'conf_rmvwaitlist'  => dgettext('library', 'Are you sure you want to cancel your reservation?'),
'conf_delitem'      => dgettext('library', 'Are you sure you want to delete this item?'),
'add_waitlist'      => dgettext('library', 'Place your reservation'),
'on_waitlist'       => dgettext('library', 'You\'re #%d on the waiting list'),
'remove'            => dgettext('library', 'Remove'),
'click_to_remove'   => dgettext('library', 'Cancel Reservation'),
'available'         => dgettext('library', 'Available'),
'avail_cnt'         => dgettext('library', '%s available'),
'has_waitlist'      => dgettext('library', 'Pending reservations: %d'),
'pending'           => dgettext('library', 'Pending'),
'pending_actions'   => dgettext('library', 'Pending Actions'),
'overdue'           => dgettext('library', 'Overdue'),
'waitlisted'        => dgettext('library', 'Waitlisted'),
'checkout_user'     => dgettext('library', 'Check out to user'),
'checkout_instr'    => dgettext('library', 'Select the user who is checking out this item.  If there is a waiting list, the next user is automatically selected.'),
'submit'        => dgettext('library', 'Submit'),
'cancel'        => dgettext('library', 'Cancel'),
'action_hdr'    => dgettext('library', 'Action'),
'checkout'      => dgettext('library', 'Check Out'),
'checkin'       => dgettext('library', 'Check In'),
'checkedout'    => dgettext('library', 'Checked Out'),
'by_you'        => dgettext('library', 'You have checked out this item.'),
'back_to_list'  => dgettext('library', 'Back to Listing'),
'all'           => dgettext('library', 'All'),
'dt_due'        => dgettext('library', 'Due Date'),
'trans_hist_title' => dgettext('library', 'Transaction History for: '),
'by'                => dgettext('library', 'By'),
'next_on_list'      => dgettext('library', 'Next on waiting list'),
'subj_item_avail'   => dgettext('library', 'Your requested library item is available'),
'in_use'            => dgettext('library', 'In Use'),
'item_updated'  => dgettext('library', 'Item has been updated.'),
'item_nochange' => dgettext('library', 'Item has not been changed.'),
'search_openlib' => dgettext('library', 'Search online for the ISBN.'),
'max_wait_items' => dgettext('library', 'You can reserve up to <br />' . $_CONF_LIB['max_wait_items'] . ' items at a time.'),
'add_instances' => dgettext('library', 'Add Instances'),
'instance'  => dgettext('library', 'Instance'),
'view_instances' => dgettext('library', 'View instances of this item'),
'view_item' => dgettext('library', 'View this item&apos;s detail'),
'total_instances' => dgettext('library', '%d instances currently in the database'),
);

$LANG_LIB_HELP = array(
/*'item_id' => dgettext('library', 'Enter a unique item ID. A SKU or ISBN is recommended.'),
'item_name' => dgettext('library', 'Enter the item&apos;s name or title.'),
'short_dscp' => dgettext('library', 'Enter a short one-line description of the item.'),
'item_cat_id' => dgettext('library', 'Select the category for this item.'),
'author' => dgettext('library', 'Enter the author name(s)'),
'publisher' => dgettext('library', 'Enter the name of the publisher, if any.'),
'pub_date' => dgettext('library', 'Enter the date published. Any text may be entered'),
'media_type' => dgettext('library', 'Select the type of media for this item.'),
'item_dscp' => dgettext('library', 'Enter a detailed description of this item.'),
'keywords' => dgettext('library', 'Keywords are used for searching but do not appear in the item&apos;s display.'),
'images' => dgettext('library', 'Upload images to be shown with the item, such as book covers.'),
'maxcheckout' => dgettext('library', 'Enter the maximum number of days for which the item can be checked out.'),
'daysonhold' => dgettext('library', 'Enter the grace period, in days, to redeem a waitlisted item once it becomes available.'),
'enabled' => dgettext('library', 'Check if this item is enabled. Disabling an item can be used to temporarily remove an item from the catalog.'),
'comments' => dgettext('library', 'Select whether comments are enabled for this item.',*/
'max_wait_items' => dgettext('library', 'You can reserve up to ' . $_CONF_LIB['max_wait_items'] . ' items at a time.'),
'due_dt' => dgettext('library', 'Enter or select the due date for the item.'),
'checkout_user' => dgettext('library', 'Select the user to check out this item. The user at the top of the waiting list is shown first.'),
'cat_name' => dgettext('library', 'Enter a short name for this category'),
'cat_owner' => dgettext('library', 'Select the category owner. There is no submission option so this should normally be an administrator.'),
'cat_group' => dgettext('library', 'Select the category group. This can be used to limit read access to a specific group.'),
'cat_perms' => dgettext('library', 'Select the permissions for this category. Only Read permission is used.'),
'cat_enabled' => dgettext('library', 'Select whether this category is enabled. Disabling a category prevents any of its items from being displayed.'),
'add_instances' => dgettext('library', 'Enter the number of instances to add for this item.'),
'checkin_instance' => dgettext('library', 'Select the specific item instance to check in.'),
);

/** Message indicating plugin version is up to date */
$PLG_library_MESSAGE2 = dgettext('library', 'The library plugin is already up to date.');
$PLG_library_MESSAGE03 = dgettext('library', 'Error retrieving current version number');
$PLG_library_MESSAGE04 = dgettext('library', 'Error performing the plugin upgrade');
$PLG_library_MESSAGE05 = dgettext('library', 'Error upgrading the plugin version number');
$PLG_library_MESSAGE06 = dgettext('library', 'Plugin is already up to date');

/** Message indicating that no event was selected when required */
$PLG_library_MESSAGE101 = 'No Event Selected';

/** Language strings for the plugin configuration section */
$LANG_configsections['library'] = array(
    'label' => dgettext('library', 'Library'),
    'title' => dgettext('library', 'Library Configuration'),
);

/** Language strings for the field names in the config section */
$LANG_confignames['library'] = array(
    'daysonhold'    => dgettext('library', 'Days to keep items on hold for waitlisted users'),
    'order'         => dgettext('library', 'Default sort order for item display'),
    'items_per_page' => dgettext('library', 'Items to show per page'),
    'use_css_menus' => dgettext('library', 'CSS Menus? DEPRECATED'),
    'max_images'    => dgettext('library', 'Max number of item images'),
    'max_thumb_size' => dgettext('library', 'Max Thumbnail Dimension (px)'),
    'img_max_width' => dgettext('library', 'Max Image Width (px)'),
    'img_max_height' => dgettext('library', 'Max Image Height (px)'),
    'max_image_size' => dgettext('library', 'Max. Product Image Size'),
    'ena_comments'  => dgettext('library', 'Enable Comments?'),
    'ena_ratings'   => dgettext('library', 'Enable Ratings?'),
    'leftblocks'    => dgettext('library', 'Enable Left Blocks?'),
    'rightblocks'   => dgettext('library', 'Enable Right Blocks'),
    'maxcheckout'   => dgettext('library', 'Maximum number of days an item may be checked out'),
    'displayblocks'  => dgettext('library', 'Display glFusion Blocks'),
    'menuitem'      => dgettext('library', 'Show on user menu?'),
    'grp_librarians' => dgettext('library', 'Librarian Group'),
    'def_group_id'  => dgettext('library', 'Default Access Group'),
    'notify_checkout' => dgettext('library', 'Notify Librarians on item reservation?'),
    'lookup_method' => dgettext('library', 'ISBN Lookup Method'),
);

/** Language strings for the subgroup names in the config section */
$LANG_configsubgroups['library'] = array(
    'sg_main' => dgettext('library', 'Main Settings'),
);

/** Language strings for the field set names in the config section */
$LANG_fs['library'] = array(
    'fs_main'   => dgettext('library', 'General Settings'),
    'fs_paths'  => dgettext('library', 'Images and Paths'),
    'fs_notifications' => dgettext('library', 'Notifications'),
);

/**
 *  Language strings for the selection option names in the config section
 *  Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
 */
$LANG_configselects['library'] = array(
    0 => array(
        dgettext('library', 'True') => 1,
        dgettext('library', 'False') => 0,
    ),
    1 => array(
        dgettext('library', 'True') => TRUE,
        dgettext('library', 'False') => FALSE,
    ),
    2 => array(
        dgettext('library', 'Yes') => 1,
        dgettext('library', 'No') => 0,
    ),
    3 => array(
        dgettext('library', 'Never') => 0,
        dgettext('library', 'Always') => 1,
        dgettext('library', 'Logged-in Users') => 2,
    ),
    13 => array(
        dgettext('library', 'None') => 0,
        dgettext('library', 'Left') => 1,
        dgettext('library', 'Right') => 2,
        dgettext('library', 'Both') => 3,
    ),
    14 => array(
        '-None-' => '',
        'OpenLibrary.org' => 'openlib',
        dgettext('library', 'Astore Plugin') => 'astore',
    ),
);

?>
