<?php
/**
*   Class to manage library items
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Library;

/**
*   Class for library item
*   @package library
*/
class Item
{
    /** Property fields.  Accessed via Set() and Get()
    *   @var array */
    private $properties;

    /** Indicate whether the current user is an administrator
    *   @var boolean */
    private $isAdmin;

    /** Indicate that this is a new item
    *   @var boolean */
    private $isNew;

    /** URL to item list, including search params
    *   @var string */
    private $ListingUrl;
    //var $button_types = array();

    /** Array of error messages
    *   @var mixed */
    var $Error;


    /**
    *  Constructor.
    *  Reads in the specified class, if $id is set.  If $id is empty,
    *  then a new entry is being created.
    *
    *  @param integer $id  Optional item ID
    */
    public function __construct($id = '')
    {
        global $_CONF_LIB;

        $this->properties = array();
        if ($id == '') {
            $this->isNew = true;
            $this->id = COM_makeSid();
            $this->oldid = '';
            $this->name = '';
            $this->cat_id = '';
            $this->dscp= '';
            $this->publisher = '';
            $this->pub_date = '';
            $this->author = '';
            $this->daysonhold = $_CONF_LIB['daysonhold'];
            $this->type = 0;
            $this->maxcheckout = $_CONF_LIB['maxcheckout'];
            $this->enabled = 1;
            $this->dt_add = time();
            $this->views = 0;
            $this->rating = 0;
            $this->votes = 0;
            $this->keywords = '';
            $this->status = 0;
            $this->uid = 0;
        } else {
            $this->id = $id;
            if (!$this->Read()) {
                $this->id = COM_makeSid();
                $this->isNew = true;
            } else {
                $this->isNew = false;
            }
        }

        $this->ListingUrl = LIBRARY_URL . '/index.php';
        $this->isAdmin = SEC_hasRights('library.admin') ? 1 : 0;
    }


    /**
    *   Set a property's value.
    *
    *   @param  string  $var    Name of property to set.
    *   @param  mixed   $value  New value for property.
    */
    public function __set($var, $value='')
    {
        switch ($var) {
        case 'id':
        case 'oldid':
            $this->properties[$var] = COM_sanitizeID($value, false);
            break;

        case 'views':
        case 'dt_add':
        case 'daysonhold':
        case 'maxcheckout':
        case 'votes':
        case 'type':
        case 'cat_id':
        case 'comments_enabled':
        case 'uid':
        case 'status':
        case 'due':
            // Integer values
            $this->properties[$var] = (int)$value;
            break;

        case 'rating':
            // Float values
            $this->properties[$var] = (float)$value;
            break;

        case 'dscp':
        case 'short_dscp':
        case 'name':
        case 'keywords':
        case 'publisher':
        case 'pub_date':
        case 'author':
            // String values
            $this->properties[$var] = trim($value);
            break;

        case 'enabled':
            // Boolean values
            $this->properties[$var] = $value == 1 ? 1 : 0;
            break;

        default:
            // Undefined values (do nothing)
            break;
        }
    }


    /**
    *   Get the value of a property.
    *
    *   @param  string  $var    Name of property to retrieve.
    *   @return mixed           Value of property, NULL if undefined.
    */
    public function __get($var)
    {
        if (array_key_exists($var, $this->properties)) {
            return $this->properties[$var];
        } else {
            return NULL;
        }
    }


    /**
    *   Sets all variables to the matching values from $rows.
    *
    *   @param  array   $row        Array of values, from DB or $_POST
    *   @param  boolean $fromDB     True if read from DB, false if from $_POST
    */
    public function setVars($row, $fromDB=false)
    {
        if (!is_array($row)) return;

        $this->id   = $row['id'];
        $this->dscp = $row['dscp'];
        $this->short_dscp = $row['short_dscp'];
        $this->publisher = $row['publisher'];
        $this->pub_date = $row['pub_date'];
        $this->author = $row['author'];
        $this->enabled = isset($row['enabled']) ? 1 : 0;
        $this->name = $row['name'];
        $this->cat_id = $row['cat_id'];
        $this->daysonhold = $row['daysonhold'];
        $this->maxcheckout = $row['maxcheckout'];
        $this->dt_add = $row['dt_add'];
        $this->views = $row['views'];
        $this->keywords = $row['keywords'];
        $this->type = $row['type'];
        $this->votes = $row['votes'];
        $this->rating = $row['rating'];
        $this->comments_enabled = $row['comments_enabled'];

        if ($fromDB) {
            $this->oldid = $row['id'];
            $this->uid = $row['uid'];
            $this->due = $row['due'];
            $this->status = $row['status'];
            $this->uid = $row['uid'];
        } else {
            $this->oldid = $row['oldid'];
            $this->uid = 0;
            $this->due = 0;
            $this->status = 0;
            $this->uid = 0;
        }
    }


    /**
    *   Read a specific record and populate the local values.
    *
    *   @param  integer $id Optional ID.  Current ID is used if zero.
    *   @return boolean     True if a record was read, False on failure
    */
    public function Read($id = '')
    {
        global $_TABLES;

        if ($id == '') $id = $this->id;
        if ($id == '') {
            $this->error = 'Invalid ID in Read()';
            return false;
        }
        $id = COM_sanitizeId($id);
        $result = DB_query("SELECT *
                    FROM {$_TABLES['library.items']}
                    WHERE id='$id'");
        if (!$result || DB_numRows($result) != 1) {
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->setVars($row, true);
            return true;
        }
    }


    /**
    *   Save the current values to the database.
    *   Appends error messages to the $Error property.
    *
    *   @param  array   $A      Optional array of values from $_POST
    *   @return boolean         True if no errors, False otherwise
    */
    public function Save($A = '')
    {
        global $_TABLES, $_CONF_LIB;

        if (is_array($A)) {
            $this->setVars($A);
        }

        // Insert or update the record, as appropriate.  Make sure the new ID
        // being inserted or changed to doesn't already exist.
        $allowed = ($this->isNew || $this->id != $this->oldid) ? 0 : 1;
        $num1 = DB_count($_TABLES['library.items'], 'id', $this->id);
        if ($num1 > $allowed) {
            $this->Error[] = 'Duplicate ID ' . $this->id;
        } else {
            if ($this->isNew) {
                $sql1 = "INSERT INTO {$_TABLES['library.items']} SET ";
                $sql3 = '';
            } else {
                $sql1 = "UPDATE {$_TABLES['library.items']} SET ";
                $sql3 = " WHERE id = '" . DB_escapeString($this->oldid) . "'";
            }
            $sql2 = "id = '" . DB_escapeString($this->id) . "',
                name='" . DB_escapeString($this->name) . "',
                cat_id = '{$this->cat_id}',
                type = '{$this->type}',
                dscp = '" . DB_escapeString($this->dscp) . "',
                publisher = '" . DB_escapeString($this->publisher) . "',
                pub_date = '" . DB_escapeString($this->pub_date) . "',
                author = '" . DB_escapeString($this->author) . "',
                short_dscp = '" . DB_escapeString($this->short_dscp) . "',
                keywords='" . DB_escapeString($this->keywords) . "',
                daysonhold='{$this->daysonhold}',
                maxcheckout='{$this->maxcheckout}',
                enabled='{$this->enabled}',
                views='{$this->views}'";
            $sql = $sql1 . $sql2 . $sql3;
            DB_query($sql, 1);
            if (DB_error()) {
                $this->Error[] = "Failed to insert or update record";
            } else {
                // Handle image uploads.  This is done last because we need
                // the product id to name the images filenames.
                $U = new Image($this->id, 'images');
                $U->uploadFiles();
                if ($U->areErrors() > 0) {
                    $this->Error[] = $U->printErrors(false);
                }
            }
        }
        if (empty($this->Error)) {
            PLG_itemSaved($this->id, $_CONF_LIB['pi_name']);
            return true;
        } else {
            return false;
        }
    }


    /**
    *   Delete the current product record from the database
    */
    public function Delete()
    {
        global $_TABLES, $_CONF_LIB;

        if ($this->id == '')
            return false;

        // Locate and delete photos
        $sql = "SELECT img_id
                FROM {$_TABLES['library.images']}
                WHERE item_id='{$this->id}'";
        //echo $sql;
        $photo= DB_query($sql, 1);
        if (!$photo) return false;
        while ($prow = DB_fetchArray($photo)) {
            $this->DeleteImage($prow['img_id']);
        }

        PLG_itemDeleted($this->id, $_CONF_LIB['pi_name']);
        DB_delete($_TABLES['library.items'], 'id', $this->id);
        $this->id = 0;
        return true;
    }


    /**
    *   Deletes a single image from disk.
    *   Only needs the $img_id value, so this function may be called as a
    *   standalone function.
    *
    *   @param  integer $img_id     DB ID of image to delete
    */
    public function DeleteImage($img_id)
    {
        global $_TABLES, $_CONF_LIB;

        $img_id = (int)$img_id;
        if ($img_id < 1) return;

        $filename = DB_getItem($_TABLES['library.images'], 'filename',
                "img_id=$img_id");

        if (file_exists("{$_CONF_LIB['image_dir']}/{$filename}"))
                unlink( "{$_CONF_LIB['image_dir']}/{$filename}" );

        if (file_exists("{$_CONF_LIB['image_dir']}/thumbs/{$filename}"))
                unlink("{$_CONF_LIB['image_dir']}/thumbs/{$filename}");

        DB_delete($_TABLES['library.images'], 'img_id', $img_id);
    }


    /**
    *   Determines if the current record is valid.
    *
    *   @return boolean     True if ok, False when first test fails.
    */
    private function isValidRecord()
    {
        // Check that basic required fields are filled in
        if ($this->name == '') {
            return false;
        }

        return true;
    }


    /**
    *   Creates the edit form
    *
    *   @param  integer     $id Optional ID, current record used if zero
    *   @return string      HTML for edit form
    */
    public function showForm($id = 0)
    {
        global $_TABLES, $_CONF, $_CONF_LIB, $LANG_LIB, $LANG24,
                $LANG_postmodes;

        if ($id != '') {
            // If an id is passed in, then read that record
            if (!$this->Read($id)) {
                return LIBRARY_errorMessage($LANG_LIB['invalid_item_id'], 'info');
            }
        }
        $id = $this->id;

        $T = LIBRARY_getTemplate('item_form', 'product');
        $action_url = LIBRARY_ADMIN_URL . '/index.php';
        if ($this->oldid != '') {
            $retval = COM_startBlock($LANG_LIB['edit'] . ': ' . $this->name);
        } else {
            $retval = COM_startBlock($LANG_LIB['new_item']);
        }

        $T->set_var(array(
            'oldid'         => $this->oldid,
            'dt_add'        => $this->dt_add,
            'id'            => $this->id,
            'name'          => htmlspecialchars($this->name),
            'category'      => $this->cat_id,
            'dscp'          => htmlspecialchars($this->dscp),
            'short_dscp'    =>
                            htmlspecialchars($this->short_dscp),
            'publisher'     => $this->publisher,
            'pub_date'      => $this->pub_date,
            'author'        => $this->author,
            'daysonhold'    => $this->daysonhold,
            'maxcheckout'   => $this->maxcheckout,
            'pi_admin_url'  => LIBRARY_ADMIN_URL,
            'keywords'      => htmlspecialchars($this->keywords),
            'cat_select'    => Category::buildSelection($this->cat_id),
            'pi_url'        => LIBRARY_URL,
            'doc_url'       => LIBRARY_getDocURL('product_form.html',
                                            $_CONF['language']),
            'type'          => $this->type,
        ) );

        $T->set_block('product', 'TypeSelBlock', 'TypeSel');
        $res = DB_query("SELECT id, name FROM {$_TABLES['library.types']}");
        while ($A = DB_fetchArray($res, false)) {
            $T->set_var(array(
                'type_id'   => $A['id'],
                'type_name' => $A['name'],
                'selected'  => $A['id'] == $this->type ?
                                'selected="selected"' : '',
            ) );
            $T->parse('TypeSel', 'TypeSelBlock', true);
        }

        $T->set_var('ena_chk',
                $this->enabled == 1 ? ' checked="checked"' : '');

        if (!$this->isNew && !$this->isUsed()) {
            $T->set_var('candelete', 'true');
        }

        // Set up the photo fields.  Use $photocount defined above.
        // If there are photos, read the $photo result.  Otherwise,
        // or if this is a new ad, just clear the photo area
        $T->set_block('product', 'PhotoRow', 'PRow');
        $i = 0;

        // Get the existing photos.  Will only have photos with an
        // existing product entry.
        $photocount = 0;
        if ($this->id != NULL) {
            $sql = "SELECT img_id, filename
                    FROM {$_TABLES['library.images']}
                    WHERE item_id='{$this->id}'";
            $photo = DB_query($sql);

            // save the count of photos for later use
            if ($photo)
                $photocount = DB_numRows($photo);
            else
                $photocount = 0;

            // While we're checking the ID, set it as a hidden value
            // for updating this record
            $T->set_var('product_id', $this->id);
        } else {
            $T->set_var('product_id', '');
        }

        // If there are any images, retrieve and display the thumbnails.
        if ($photocount > 0) {
            while ($prow = DB_fetchArray($photo)) {
                $i++;
                $filepath = $_CONF_LIB['image_dir'] . '/' . $prow['filename'];
                $T->set_var(array(
                    'img_url'   => LGLIB_ImageUrl($filepath, 800, 600), // todo
                    'thumb_url' => LGLIB_ImageUrl($filepath,
                            $_CONF_LIB['max_thumb_size'], $_CONF_LIB['max_thumb_size']),
                    'seq_no'    => $i,
                    'id'        => $this->id,
                    'del_img_url' => LIBRARY_ADMIN_URL . '/index.php' .
                        '?mode=delete_img' .
                        "&img_id={$prow['img_id']}".
                        "&id={$this->id}",
                ) );
                $T->parse('PRow', 'PhotoRow', true);
            }
        } else {
            $T->parse('PRow', '');
        }

        // add upload fields for unused images
        $T->set_block('product', 'UploadFld', 'UFLD');
        for ($j = $i; $j < $_CONF_LIB['max_images']; $j++) {
            $T->parse('UFLD', 'UploadFld', true);
        }
        $retval .= $T->parse('output', 'product');
        @setcookie($_CONF['cookie_name'].'fckeditor',
                SEC_createTokenGeneral('advancededitor'),
                time() + 1200, $_CONF['cookie_path'],
                $_CONF['cookiedomain'], $_CONF['cookiesecure']);

        $retval .= COM_endBlock();
        return $retval;

    }   // function showForm()


    /**
    *   Toggles the value of a field.
    *
    *   @param  integer $oldval     Original value to change
    *   @param  string  $varname    Name of field
    *   @param  string  $id         ID number of element to modify
    *  @return         New value, or old value upon failure
    */
    private static function _toggle($oldvalue, $varname, $id)
    {
        global $_TABLES;

        // Determing the new value (opposite the old)
        $newvalue = $oldvalue == 1 ? 0 : 1;
        $id = COM_sanitizeID($id, false);

        $sql = "UPDATE {$_TABLES['library.items']}
                SET $varname=$newvalue
                WHERE id='$id'";
        //COM_errorLog($sql);
        DB_query($sql, 1);
        return DB_error() ? $oldvalue : $newvalue;
    }


    /**
    *   Sets the "enabled" field to the specified value.
    *
    *   @param  integer $oldvalue   Original value to be changed
    *   @param  string  $id         ID number of element to modify
    *   @return         New value, or old value upon failure
    */
    public static function toggleEnabled($oldvalue, $id)
    {
        $oldvalue = $oldvalue == 0 ? 0 : 1;
        if ($id == '') return $oldval;
        return self::_toggle($oldvalue, 'enabled', $id);
    }


    /**
    *   Determine if this item has any transaction or waitlist records.
    *   Typically used to prevent deletion of product records that have
    *   dependencies.
    *
    *   @return boolean True if used, False if not
    */
    public function isUsed()
    {
        global $_TABLES;
        if (DB_count($_TABLES['library.trans'], 'id',
                $this->id) > 0) {
            return true;
        } elseif (DB_count($_TABLES['library.waitlist'], 'id',
                $this->id) > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
    *   Display the detail page for the product.
    *
    *   @return string      HTML for the product page.
    */
    public function Detail()
    {
        global $_CONF, $_CONF_LIB, $_TABLES, $LANG_LIB, $_USER;

        USES_lib_comments();

        if ($this->id == '') {
            return LIBRARY_errorMessage($LANG_LIB['invalid_item_id'], 'info');
        }

        $retval = COM_startBlock();
        $T = LIBRARY_getTemplate('item_detail', 'item');

        // Highlight the query terms if coming from a search
        if (isset($_REQUEST['query']) && !empty($_REQUEST['query'])) {
            $name = COM_highlightQuery($this->name,
                        $_REQUEST['query']);
            $l_desc = COM_highlightQuery($this->dscp,
                        $_REQUEST['query']);
            $s_desc = COM_highlightQuery($this->short_dscp,
                        $_REQUEST['query']);
        } else {
            $name = $this->name;
            $l_desc = $this->dscp;
            $s_desc = $this->short_dscp;
        }
        $T->set_var(array(
            'user_id'           => $_USER['uid'],
            'id'                => $this->id,
            'name'              => $name,
            'dscp'              => $l_desc,
            'short_dscp'        => $s_desc,
            'img_cell_width'    => ($_CONF_LIB['max_thumb_size'] + 20),
            'pi_url'            => LIBRARY_URL,
            'avail_blk'         => $this->AvailBlock(),
            'publisher'         => $this->publisher,
            'pub_date'          => $this->pub_date,
            'author'            => $this->author,
            'listing_url'       => $this->ListingUrl,
        ) );

        /*$on_waitlist = DB_count($_TABLES['library.waitlist'],
                    array('item_id', 'uid'),
                    array($this->Get('id'), $_USER['uid'])) == 1;
        $waitlist = DB_count($_TABLES['library.waitlist'],
                    array('item_id'),
                    array($this->Get('id')));

        if ($this->Get('status') == LIB_STATUS_AVAIL) {
            $T->set_var(array(
                'avail_icon'    => $waitlist > 0 ? 'yellow.png' : 'on.png',
                'due_dt'        => '',
            ) );
            $avail_txt = $LANG_LIB['available'];
        } else {
            $T->set_var(array(
                'due_dt'    => date("d-M-Y", $this->Get('due')),
                'avail_icon' => 'red.png',
                'have_checkout' => $this->Get('uid') == $_USER['uid'] ? 'true' : '',
            ) );
            $avail_txt = $LANG_LIB['not_available'];
        }
        $reserv_txt = '<br />' . sprintf($LANG_LIB['has_waitlist'], $waitlist);
        if ($on_waitlist) {
            $T->set_var(array(
                'wait_action' => 'rmv',
                'wait_confirm_txt' => $LANG_LIB['conf_rmvwaitlist'],
                'wait_action_txt' => $LANG_LIB['on_waitlist'] . '<br />' .
                                    $LANG_LIB['click_to_remove'],
            ) );
        } else {
            $T->set_var(array(
                    'wait_action' => 'add',
                    'wait_confirm_txt' => $LANG_LIB['conf_addwaitlist'],
                    'wait_action_txt' => $LANG_LIB['add_waitlist'] . $reserv_txt,
            ) );
        }
        $T->set_var('avail_txt', $avail_txt . $reserv_txt);*/

        // Retrieve the photos and put into the template
        $sql = "SELECT img_id, filename
                FROM {$_TABLES['library.images']}
                WHERE item_id='{$this->id}'";
        //echo $sql;die;
        $img_res = DB_query($sql);
        $photo_detail = '';
        $T->set_var('have_photo', '');     // assume no photo available
        for ($i = 0; $prow = DB_fetchArray($img_res, false); $i++) {
            $img_file = "{$_CONF_LIB['image_dir']}/{$prow['filename']}";
            $tn_url = LGLIB_ImageUrl($img_file, $_CONF_LIB['max_thumb_size'],
                    $_CONF_LIB['max_thumb_size']);
            $img_url = LGLIB_ImageUrl($img_file, 800, 600);
            if ($tn_url !== '') {
                if ($i == 0) {
                    $T->set_var('main_img_url', LGLIB_ImageUrl($img_file,
                        //$_CONF_LIB['max_img_width'], $_CONF_LIB['max_img_height']));
                        800, 600));
                }
                $T->set_block('item', 'Thumbnail', 'PBlock');
                $T->set_var('tn_url', $tn_url);
                $T->set_var('img_url', $img_url);
                $T->parse('PBlock', 'Thumbnail', true);
                $T->set_var('have_photo', 'true');
            }
        }
        // Show the user comments
        if (plugin_commentsupport_library() &&
            $this->comments_enabled < 2) {
            if ($_CONF['commentsloginrequired'] == 1 && COM_isAnonUser()) {
                $mode = -1;
            } else {
                $mode = $this->comments_enabled;
            }
            $T->set_var('usercomments',
                CMT_userComments($this->id, $this->name, 'library', '',
                    '', 0, 1, false, false, $mode));
        }

        if ($_CONF_LIB['ena_ratings'] == 1) {
            $PP_ratedIds = RATING_getRatedIds('library');
            if (in_array($this->id, $PP_ratedIds)) {
                $static = true;
                $voted = 1;
            } elseif (plugin_canuserrate_library($this->id, $_USER['uid'])) {
                $static = 0;
                $voted = 0;
            } else {
                $static = 1;
                $voted = 0;
            }
            $rating_box = RATING_ratingBar('library', $this->id,
                    $this->votes, $this->rating,
                    $voted, 5, $static, 'sm');
            $T->set_var('rating_bar', $rating_box);
        } else {
            $T->set_var('ratign_bar', '');
        }

        $retval .= $T->parse('output', 'item');

        // Update the hit counter
        DB_query("UPDATE {$_TABLES['library.items']}
                SET views = views + 1
                WHERE id = '{$this->id}'");

        $retval .= COM_endBlock();

        return $retval;

    }


    /**
    *   Create a formatted display-ready version of the error messages.
    *
    *   @return string      Formatted error messages.
    */
    public function PrintErrors()
    {
        $retval = '';
        foreach($this->Error as $key=>$msg) {
            $retval .= "<li>$msg</li>\n";
        }
        return $retval;
    }


    /**
    *   Hightlight keywords found in text string.
    *   Credits: http://www.bitrepository.com/
    *
    *   @param string $str Text to highlight
    *   @param string $keywords String of keywords to highlight in $str
    *   @return string New text with highlighting
    */
    private function highlight($str = '', $keywords = '')
    {
        $patterns = Array();
        $replaces = Array();
        $style = 'background: #66FF66;';

        if ($keywords == "") {
            return $str;
        }

        $words = explode(" ", $keywords);

        foreach($words as $word) {

            $patterns[] = '/' . $word . '/i';
            $replaces[] = "<span style=\"$style\">$0</span>";

        }

        return preg_replace($patterns, $replaces, $str);

        return $str;
    }


    public function CheckOut($to, $due='')
    {
        global $_TABLES, $_USER;

        $to = (int)$to;
        if ($to == 1)           // Can't check out to anonymous
            return;
        if ($to == 0 && empty($_POST['co_username']))
            return;
        $me = (int)$_USER['uid'];

        if (empty($due)) {
            $due = LIBRARY_dueDate($this->maxcheckout)->toUnix();
        }

        // Set the due date from the POSTed variable, if present.  If not
        // present, or if an error occurs in the creation of the timestamp,
        // fall back to now + the max checkout days.  Add one to the due date
        // to get it to midnight the following day.
        DB_query("UPDATE {$_TABLES['library.items']} SET
                    status='" . LIB_STATUS_OUT . "',
                    uid=$to,
                    due=$due
                WHERE id='{$this->id}'");

        // Delete this user from the waitlist, if applicable
        DB_delete($_TABLES['library.waitlist'], array('item_id','uid'),
                array($this->id, $to));

        // Insert the trasaction record
        DB_query("INSERT INTO {$_TABLES['library.trans']}
                    (item_id, dt, doneby, uid, trans_type)
                VALUES (
                    '{$this->id}', UNIX_TIMESTAMP(), $me, $to, 'checkout')");
    }


    /**
    *   Check in an item.
    */
    public function checkIn()
    {
        global $_TABLES, $_USER;

        $me = (int)$_USER['uid'];

        DB_query("UPDATE {$_TABLES['library.items']} SET
                    status='" . LIB_STATUS_AVAIL . "',
                    uid = 0,
                    due = 0
                WHERE id='$id'");

        // Insert the trasaction record, only if it's checked out.
        if ($uid > 1) {
            DB_query("INSERT INTO {$_TABLES['library.trans']}
                    (item_id, dt, doneby, uid, trans_type)
                VALUES (
                    '{$this->id}', UNIX_TIMESTAMP(), $me, {$this->uid}, 'checkin')");
        }
        // If there's a reservation for this item, notify the reserver.
        Waitlist::notifyNext($this);
    }


    /**
    *   Creates a block showing the item availability and links.
    *   Meant to be added to the detail or listing.
    *
    *   @return string      HTML for block.
    */
    public function AvailBlock()
    {
        global $_TABLES, $LANG_LIB, $_USER, $_CONF_LIB;

        $T = LIBRARY_getTemplate('avail_block', 'avail');

        // Check if we have the item reserved, and if there's a waitlist.
        $on_waitlist = DB_count($_TABLES['library.waitlist'],
                    array('item_id', 'uid'),
                    array($this->id, $_USER['uid'])) == 1;
        $waitlist = DB_count($_TABLES['library.waitlist'],
                    'item_id', $this->id);
        $user_wait_items = DB_count($_TABLES['library.waitlist'],
                    'uid', $_USER['uid']);

        $reserve_txt = sprintf($LANG_LIB['has_waitlist'], $waitlist);
        if ($on_waitlist) {
            $can_reserve = false;
            $is_reserved = true;
            //$wait_action = 'rmv';
            //$wait_confirm_txt = $LANG_LIB['conf_rmvwaitlist'];
            //$wait_action_txt = $LANG_LIB['on_waitlist'] . '<br />' .
            //                        $LANG_LIB['click_to_remove'];
        } else {
            $can_reserve = $user_wait_items < $_CONF_LIB['max_wait_items'] ? true : false;
            $is_reserved = false;
            //$wait_action = 'add';
            //$wait_confirm_txt = $LANG_LIB['conf_addwaitlist'];
            //$wait_action_txt = $LANG_LIB['add_waitlist'];
        }

        switch ($this->status) {
        case LIB_STATUS_AVAIL:
            if ($user_wait_items < $_CONF_LIB['max_wait_items'])
                $avail_txt = $LANG_LIB['available'];
            elseif (!$is_reserved)
                $avail_txt = $LANG_LIB['max_wait_items'];
            break;

        case LIB_STATUS_OUT:
            $avail_txt = $LANG_LIB['not_available'];
            $avail_icon = 'red.png';
            if ($this->uid == $_USER['uid']) {
                $avail_txt .= ' ' . $LANG_LIB['by_you'];
                $can_reserve = false;
                $wait_action_txt = '';
            }
            break;
        }

        $T->set_var(array(
            'can_reserve'   => $can_reserve,
            'is_reserved'   => $is_reserved,
            'wait_limit_reached' => $user_wait_items >= $_CONF_LIB['max_wait_items'],
            //'avail_icon'    => $avail_icon,
            'avail_txt'     => $avail_txt,
            'due_dt'        => '',
            //'wait_action' => $wait_action,
            //'wait_confirm_txt' => $wait_confirm_txt,
            //'wait_action_txt' => $wait_action_txt,
            //'reserve_txt' => $reserve_txt,
            'id'            => $this->id,
            'pi_url'        =>  LIBRARY_URL,
        ) );
        $T->parse('output', 'avail');
        $retval = $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
    *   Allow the listing URL to be overridden with parameters, etc.
    *
    *   @param  string  $url    New complete listing URL
    */
    public function SetListUrl($url)
    {
        $this->ListingUrl = $url;
    }

}   // class Item

?>
