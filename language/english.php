<?php
/**
*   English language file for the LIbrary plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/**
*   Global array to hold all plugin-specific configuration items.
*/
$LANG_LIB = array (
'plugin'            => 'Library',
'main_title'        => 'Library Items',
'admin_title'       => 'Library Administration',
'srchtitle'         => 'Library',
'category'          => 'Category',
'categories'        => 'Categories',
'mnu_library'       => 'Library',
'mnu_admin'         => 'Admin',
'item_id'           => 'Item ID or SKU',
'item_name'         => 'Name or Title',
'subtitle'          => 'Subtitle',
'qty'               => 'Qty',
'dscp'              => 'Description',
'short_dscp'        => 'Short Description',
'publisher'         => 'Publisher',
'pub_date'          => 'Date Published',
'author'            => 'Author',
'keywords'          => 'Keywords',
'exp_time_days'     => 'Expiration Time (days)',
'maxcheckout'       => 'Max Checkout Days',
'txn_id'            => 'Txn ID',
'daysonhold'        => 'Days to hold for waitlist',
'download'          => 'Download',
'downloadable'      => 'Downloadable',
'price'             => 'Price',
'history'           => 'History',
'view_history'      => 'View item history',
'type'              => 'Media Type',
'new_item'          => 'New Item',
'new_category'      => 'New Category',
'item_list'         => 'Item List',
'media_list'        => 'Media Types',
'new_mediatype'     => 'New Media Type',
'category_list'     => 'Category List',
'admin_item_hdr'    => 'Manage library items.  You can add, modify, and delete items.  You can also check items out or in from this screen, as well as disable items (make them invisible in the catalog).',
'admin_media_hdr'   => 'Manage and edit media types.  Media types can only be deleted if they\'re not referenced by any library items.',
'username'          => 'User Name',
'status_hdr'        => 'Status',
'purchaser'         => 'Purchaser',
'ip_addr'           => 'IP Address',
'datetime'          => 'Date/Time',
'verified'          => 'Verified',
'ipn_data'          => 'Full IPN Data',
'view_cart'         => 'View Cart',
'images'            => 'Images',
'cat_image'         => 'Category Image',
'click_to_enlarge'  => 'Click to Enlarge Image',
'enabled'           => 'Enabled',
'featured'          => 'Featured',
'taxable'           => 'Taxable',
'savetype'          => 'Save Type',
'deletetype'        => 'Delete Type',
'thanks_title'      => 'Thank you for your order!',
'yes'               => 'Yes',
'no'                => 'No',
'closed'            => 'Closed',
'true'              => 'True',
'false'             => 'False',
'info'              => 'Information',
'warning'           => 'Warning',
'error'             => 'Error',
'alert'             => 'Alert',
'login_req'         => 'Please log in to view options.',
'invalid_item_id' => 'An invalid item ID was requested',
'access_denied_msg' => 'You do not have access to this page. <p />' .
    'If you believe you have reached this message in error, ' .
    'please contact your site administrator.<p />' .
    'All attempts to access this page are logged.',
'access_denied'     => 'Access Denied',
'select_file'       => 'Select File',
'or_upload_new'     => 'Or, upload a new file',
'invalid_form'      => 'The submitted form has missing or invalid fields',
'item_type'         => 'Item Type',
'edit'              => 'Edit',
'create_category'   => 'Create a New Category',
'cat_name'          => 'Category Name',
'parent_cat'        => 'Parent Category',
'top_cat'           => '-- Top --',
'saveitem'          => 'Save Item',
'deleteitem'        => 'Delete Item',
'nodel_cat'         => 'Cannot delete categories that are in use.',
'nodel_type'        => 'Unable to delete this media type',
'savecat'           => 'Save Category',
'deletecat'         => 'Delete Category',
'product_id'        => 'Product ID',
'other_func'        => 'Other Functions',
'clearform'         => 'Reset Form',
'indicate_req_fld'  => 'Indicates a required field',
'item_info'         => 'Item Information',
'delete_image'      => 'Delete Image',
'sort'              => 'Sort',
'search'            => 'Search',
'dt_add'            => 'Date Added',
'ascending'         => 'Ascending',
'descending'        => 'Descending',
'sortdir'           => 'Sort Direction',
'comments'          => 'Comments',
'conf_addwaitlist'  => 'Are you sure you want to reserve this item?',
'conf_rmvwaitlist'  => 'Are you sure you want to cancel your reservation?',
'conf_delitem'      => 'Are you sure you want to delete this item?',
'add_waitlist'      => 'Place your reservation',
'on_waitlist'       => 'You\'re #%d on the waiting list',
'remove'            => 'Remove',
'click_to_remove'   => 'Cancel Reservation',
'available'         => 'Available',
'avail_cnt'         => '%s available',
'has_waitlist'      => 'Pending reservations: %d',
'pending'           => 'Pending',
'pending_actions'   => 'Pending Actions',
'overdue'           => 'Overdue',
'waitlisted'        => 'Waitlisted',
'checkout_user'     => 'Check out to user',
'checkout_instr'    => 'Select the user who is checking out this item.  If there is a waiting list, the next user is automatically selected.',
'submit'        => 'Submit',
'cancel'        => 'Cancel',
'action_hdr'    => 'Action',
'checkout'      => 'Check Out',
'checkin'       => 'Check In',
'checkedout'    => 'Checked Out',
'by_you'        => 'You have checked out this item.',
'back_to_list'  => 'Back to Listing',
'all'           => 'All',
'dt_due'        => 'Due Date',
'trans_hist_title' => 'Transaction History for: ',
'by'                => 'By',
'next_on_list'      => 'Next on waiting list',
'subj_item_avail'   => 'Your requested library item is available',
'in_use'            => 'In Use',
'item_updated'  => 'Item has been updated.',
'item_nochange' => 'Item has not been changed.',
'search_openlib' => 'Search online for the ISBN.',
'max_wait_items' => 'You can reserve up to <br />%s items at a time.',
'add_instances' => 'Add Instances',
'instance'  => 'Instance',
'view_instances' => 'View instances of this item',
'view_item' => 'View this item&apos;s detail',
'total_instances' => '%d instances currently in the database',
);

$LANG_LIB_HELP = array(
/*'item_id' => 'Enter a unique item ID. A SKU or ISBN is recommended.',
'item_name' => 'Enter the item&apos;s name or title.',
'short_dscp' => 'Enter a short one-line description of the item.',
'item_cat_id' => 'Select the category for this item.',
'author' => 'Enter the author name(s)',
'publisher' => 'Enter the name of the publisher, if any.',
'pub_date' => 'Enter the date published. Any text may be entered',
'media_type' => 'Select the type of media for this item.',
'item_dscp' => 'Enter a detailed description of this item.',
'keywords' => 'Keywords are used for searching but do not appear in the item&apos;s display.',
'images' => 'Upload images to be shown with the item, such as book covers.',
'maxcheckout' => 'Enter the maximum number of days for which the item can be checked out.',
'daysonhold' => 'Enter the grace period, in days, to redeem a waitlisted item once it becomes available.',
'enabled' => 'Check if this item is enabled. Disabling an item can be used to temporarily remove an item from the catalog.',
'comments' => 'Select whether comments are enabled for this item.',*/
'max_wait_items' => 'You can reserve up to %s items at a time.',
'due_dt' => 'Enter or select the due date for the item.',
'checkout_user' => 'Select the user to check out this item. The user at the top of the waiting list is shown first.',
'cat_name' => 'Enter a short name for this category',
'cat_owner' => 'Select the category owner. There is no submission option so this should normally be an administrator.',
'cat_group' => 'Select the category group. This can be used to limit read access to a specific group.',
'cat_perms' => 'Select the permissions for this category. Only Read permission is used.',
'cat_enabled' => 'Select whether this category is enabled. Disabling a category prevents any of its items from being displayed.',
'add_instances' => 'Enter the number of instances to add for this item.',
'checkin_instance' => 'Select the specific item instance to check in.',
);

/** Message indicating plugin version is up to date */
$PLG_library_MESSAGE2 = 'The library plugin is already up to date.';

$PLG_library_MESSAGE03 = 'Error retrieving current version number';
$PLG_library_MESSAGE04 = 'Error performing the plugin upgrade';
$PLG_library_MESSAGE05 = 'Error upgrading the plugin version number';
$PLG_library_MESSAGE06 = 'Plugin is already up to date';

/** Message indicating that no event was selected when required */
$PLG_library_MESSAGE101 = 'No Event Selected';

/** Language strings for the plugin configuration section */
$LANG_configsections['library'] = array(
    'label' => 'Library',
    'title' => 'Library Configuration'
);

/** Language strings for the field names in the config section */
$LANG_confignames['library'] = array(
    'daysonhold'    => 'Days to keep items on hold for waitlisted users',
    'order'         => 'Default sort order for item display',
    'items_per_page' => 'Items to show per page',
    'use_css_menus' => 'CSS Menus? DEPRECATED',
    'max_images'    => 'Max number of item images',
    'max_thumb_size' => 'Max Thumbnail Dimension (px)',
    'img_max_width' => 'Max Image Width (px)',
    'img_max_height' => 'Max Image Height (px)',
    'max_image_size' => 'Max. Product Image Size',
    'ena_comments'  => 'Enable Comments?',
    'ena_ratings'   => 'Enable Ratings?',
    'leftblocks'    => 'Enable Left Blocks?',
    'rightblocks'   => 'Enable Right Blocks',
    'maxcheckout'   => 'Maximum number of days an item may be checked out',
    'displayblocks'  => 'Display glFusion Blocks',
    'menuitem'      => 'Show on user menu?',
    'grp_librarians' => 'Librarian Group',
    'def_group_id'  => 'Default Access Group',
    'notify_checkout' => 'Notify Librarians on item reservation?',
    'lookup_method' => 'ISBN Lookup Method',
);

/** Language strings for the subgroup names in the config section */
$LANG_configsubgroups['library'] = array(
    'sg_main' => 'Main Settings',
);

/** Language strings for the field set names in the config section */
$LANG_fs['library'] = array(
    'fs_main'   => 'General Settings',
    'fs_paths'  => 'Images and Paths',
    'fs_notifications' => 'Notifications',
);

/**
 *  Language strings for the selection option names in the config section
 *  Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
 */
$LANG_configselects['library'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('True' => TRUE, 'False' => FALSE),
    2 => array('Yes' => 1, 'No' => 0),
    3 => array('Never' => 0, 'Always' => 1, 'Logged-in Users' => 2),
    5 => array('Name' => 'name', 'Price' => 'price', 'Product ID' => 'id'),
    6 => array('Always' => 2, 'Physical Items Only' => 1, 'Never' => 0),
    12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3),
    13 => array('None' => 0, 'Left' => 1, 'Right' => 2, 'Both' => 3),
    14 => array('-None-' => '', 'OpenLibrary.org' => 'openlib', 'Astore Plugin' => 'astore'),
);

?>
