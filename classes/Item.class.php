<?php
/**
 * Class to manage library items
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
 * @package     library
 * @version     0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Library;

/**
 * Class for library item.
 * @package library
 */
class Item
{
    /** Item record ID, ISBN or similar.
     * @var string */
    private $id = '';

    /** View counter.
     * @var integer */
    private $views = 0;

    /** Date added (timestamp).
     * @var integer */
    private $dt_add = 0;

    /** Max days to keep on hold for a waitlisted user.
     * @var integer */
    private $daysonhold = 5;

    /** Max days the item can be checked out.
     * @var integer */
    private $maxcheckout = 14;

    /** Votes given to the item.
     * @var integer */
    private $votes = 0;

    /** Item media type.
     * @var integer */
    private $type = 0;

    /** Category record ID.
     * @var integer */
    private $cat_id = 0;

    /** Comments enabled?
     * @var integer */
    private $comments_enabled = 0;

    /** Item status flag. DEPRECATED.
     * @var integer */
    private $status = 0;

    /** Item due date. DEPRECATED.
     * @var integer */
    private $due = 0;

    /** Checkout limit per user.
     * @var integer */
    private $user_ckout_limit = 0;

    /** Overall item rating from votes.
     * @var float */
    private $rating = 0;

    /** Item full description.
     * @var string */
    private $dscp = '';

    /** Item title.
     * @var string */
    private $title = '';

    /** Item subtitle.
     * @var string */
    private $subtitle = '';

    /** Search keywords.
     * @var string */
    private $keywords = '';

    /** Publisher name
     * @var string */
    private $publisher = '';

    /** Publication date (free-form text).
     * @var string */
    private $pub_date = '';

    /** Author/Artist name.
     * @var string */
    private $author = '';

    /** Item enabled flag.
     * @var boolean */
    private $enabled = 1;

    /** Indicate whether the current user is an administrator
     * @var boolean */
    private $isAdmin = 0;

    /** Indicate that this is a new item
     * @var boolean */
    private $isNew = 1;

    /** URL to item list, including search params
     * @var string */
    private $ListingUrl = '';

    /** Array of error messages
     * @var mixed */
    private $Errors = array();

    /** Category object for related category.
     * @var object */
    public $Category = NULL;

    /**
     * Constructor.
     * Reads in the specified class, if $id is set.  If $id is empty,
     * then a new entry is being created.
     *
     * @param   string  $id     Optional item ID
     */
    public function __construct($id = '')
    {
        $this->user_ckout_limit = (int)Config::get('def_checkout_limit');
        if ($id == '') {
            $this->isNew = true;
            $this->id = COM_makeSid();
            $this->oldid = '';
            $this->title = '';
            $this->subtitle = '';
            $this->cat_id = '';
            $this->dscp= '';
            $this->publisher = '';
            $this->pub_date = '';
            $this->author = '';
            $this->daysonhold = (int)Config::get('daysonhold');
            $this->type = 0;
            $this->maxcheckout = (int)Config::get('maxcheckout');
            $this->enabled = 1;
            $this->dt_add = time();
            $this->views = 0;
            $this->rating = 0;
            $this->votes = 0;
            $this->keywords = '';
            $this->status = 0;
            $this->uid = 0;
            $this->Category = NULL;
        } elseif (is_array($id)) {
            $this->setVars($id, true);
            $this->Category = Category::getInstance($this->cat_id);
        } else {
            $this->id = $id;
            if (!$this->Read()) {
                $this->id = COM_makeSid();
                $this->isNew = true;
            } else {
                $this->isNew = false;
                $this->Category = Category::getInstance($this->cat_id);
            }
        }
        $this->ListingUrl = Config::get('url') . '/index.php';
        $this->isAdmin = SEC_hasRights('library.admin') ? 1 : 0;
    }


    /**
     * Get the item record ID.
     *
     * @return  string      DB record ID
     */
    public function getID()
    {
        return $this->id;
    }


    /**
     * Get the item title.
     *
     * @return  string      Item title
     */
    public function getTitle()
    {
        return $this->title;
    }


    /**
     * Get the item subtitlt.
     *
     * @return   string     Item subtitle
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }


    /**
     * Get the item description.
     *
     * @return   string     Item description
     */
    public function getDscp()
    {
        return $this->dscp;
    }


    /**
     * Get the number of votes cast for this item.
     *
     * @return  integer     Number of votes
     */
    public function getVotes()
    {
        return (int)$this->votes;
    }


    /**
     * Get the overall rating for this item.
     *
     * @return  float       Item rating
     */
    public function getRating()
    {
        return (float)$this->rating;
    }


    /**
     * Get the author name.
     *
     * @return  string      Author name
     */
    public function getAuthor()
    {
        return $this->author;
    }


    /**
     * Get the publisher name.
     *
     * @return  string      Publisher name
     */
    public function getPublisher()
    {
        return $this->publisher;
    }


    /**
     * Check whether the item is available.
     *
     * @return  boolean     True if available, False if not
     */
    public function isAvailable()
    {
        return $this->status == LIB_STATUS_AVAIL;
    }


    /**
     * Check if this is a new unsaved record.
     *
     * @return  boolean     1 if new, 0 if existing
     */
    public function isNew()
    {
        return $this->isNew ? 1 : 0;
    }


    /**
     * Get the number of days this item can be on hold.
     *
     * @return  integer     Number of days allowed on hold
     */
    public function getDaysOnHold()
    {
        return (int)$this->daysonhold;
    }


    /**
     * Get an item object
     *
     * @param   string|array  $item_id    Item ID
     * @return  object              Item Object
     */
    public static function getInstance($item_id)
    {
        global $_TABLES;
        static $items = array();

        if (empty($item_id)) {
            return new self();
        } elseif (is_array($item_id)) {
            // Already have the DB record, load the vars
            return new self($item_id);
        } elseif (isset($items[$item_id])) {
            return new self($items[$item_id]);
        } else {
            $item_id = COM_sanitizeId($item_id, false);
            $result = DB_query("SELECT * FROM {$_TABLES['library.items']}
                WHERE id='$item_id'");
            if (!$result || DB_numRows($result) != 1) {
                return new self();
            } else {
                $items[$item_id] = DB_fetchArray($result, false);
                return new self($items[$item_id]);
            }
        }
    }


    /**
     * Sets all variables to the matching values from $rows.
     *
     * @param   array   $row        Array of values, from DB or $_POST
     * @param   boolean $fromDB     True if read from DB, false if from $_POST
     * @return  object  $this
     */
    public function setVars($row, $fromDB=false)
    {
        if (!is_array($row)) {
            return $this;
        }

        $this->id   = $row['id'];
        $this->dscp = $row['dscp'];
        $this->publisher = $row['publisher'];
        $this->pub_date = $row['pub_date'];
        $this->author = $row['author'];
        $this->enabled = isset($row['enabled']) && $row['enabled'] ? 1 : 0;
        $this->title = $row['title'];
        $this->subtitle = $row['subtitle'];
        $this->cat_id = (int)$row['cat_id'];
        $this->daysonhold = (int)$row['daysonhold'];
        $this->maxcheckout = (int)$row['maxcheckout'];
        $this->dt_add = (int)$row['dt_add'];
        $this->views = (int)$row['views'];
        $this->keywords = $row['keywords'];
        $this->type = (int)$row['type'];
        $this->votes = (int)$row['votes'];
        $this->rating = (float)$row['rating'];
        $this->comments_enabled = (int)$row['comments_enabled'];

        if ($fromDB) {
            $this->oldid = $row['id'];
            $this->status = (int)$row['status'];
        } else {
            $this->oldid = $row['oldid'];
            $this->status = 0;
        }
        return $this;
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @param   string  $id     Optional ID.  Current ID is used if zero.
     * @return  boolean     True if a record was read, False on failure
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
     * Save the current values to the database.
     * Appends error messages to the $Error property.
     *
     * @param   array   $A      Optional array of values from $_POST
     * @return  boolean         True if no errors, False otherwise
     */
    public function Save($A = '')
    {
        global $_TABLES;

        if (is_array($A)) {
            $this->setVars($A);
        }

        $add_instances = isset($A['add_instances']) ? (int)$A['add_instances'] : 0;

        // Insert or update the record, as appropriate.  Make sure the new ID
        // being inserted or changed to doesn't already exist.
        $allowed = ($this->isNew || $this->id != $this->oldid) ? 0 : 1;
        $num1 = DB_count($_TABLES['library.items'], 'id', $this->id);
        if ($num1 > $allowed) {
            $this->Errors[] = 'Duplicate ID ' . $this->id;
        } else {
            if ($this->isNew) {
                $sql1 = "INSERT INTO {$_TABLES['library.items']} SET ";
                $sql3 = '';
            } else {
                $sql1 = "UPDATE {$_TABLES['library.items']} SET ";
                $sql3 = " WHERE id = '" . DB_escapeString($this->oldid) . "'";
            }
            $sql2 = "id = '" . DB_escapeString($this->id) . "',
                title ='" . DB_escapeString($this->title) . "',
                subtitle ='" . DB_escapeString($this->subtitle) . "',
                cat_id = '{$this->cat_id}',
                type = '{$this->type}',
                dscp = '" . DB_escapeString($this->dscp) . "',
                publisher = '" . DB_escapeString($this->publisher) . "',
                pub_date = '" . DB_escapeString($this->pub_date) . "',
                author = '" . DB_escapeString($this->author) . "',
                keywords='" . DB_escapeString($this->keywords) . "',
                daysonhold='{$this->daysonhold}',
                maxcheckout='{$this->maxcheckout}',
                enabled='{$this->enabled}',
                views='{$this->views}'";
            $sql = $sql1 . $sql2 . $sql3;
            DB_query($sql, 1);
            if (DB_error()) {
                $this->Errors[] = "Failed to insert or update record";
            } else {
                // Handle image uploads.  This is done last because we need
                // the product id to name the images filenames.
                $U = new Image($this->id, 'images');
                $U->uploadFiles();
                if ($U->areErrors() > 0) {
                    $this->Errors[] = $U->printErrors(false);
                }
            }
        }
        if (empty($this->Errors)) {
            self::makeClone($this->id, $add_instances);
            PLG_itemSaved($this->id, Config::get('pi_name'));
            return true;
        } else {
            return false;
        }
    }


    /**
     * Delete the current item record from the database.
     */
    public function Delete()
    {
        global $_TABLES;

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

        PLG_itemDeleted($this->id, Config::get('pi_name'));
        DB_delete($_TABLES['library.items'], 'id', $this->id);
        Waitlist::deleteByItem($this->id);
        $this->id = '';
        return true;
    }


    /**
     * Deletes a single image from disk.
     *
     * @param   integer $img_id     DB ID of image to delete
     */
    public function DeleteImage($img_id)
    {
        global $_TABLES;

        $img_id = (int)$img_id;
        if ($img_id < 1) return;

        $filename = DB_getItem(
            $_TABLES['library.images'],
            'filename',
            "img_id=$img_id");

        $img_dir = Config::get('image_dir');
        if (file_exists("$img_dir/{$filename}")) {
            unlink( "$img_dir/{$filename}" );
        }

        if (file_exists("$img_dir/thumbs/{$filename}")) {
            unlink("$img_dir/thumbs/{$filename}");
        }

        DB_delete($_TABLES['library.images'], 'img_id', $img_id);
    }


    /**
     * Determines if the current record is valid.
     *
     * @return  boolean     True if ok, False when first test fails.
     */
    private function isValidRecord()
    {
        // Check that basic required fields are filled in
        if ($this->title == '') {
            return false;
        }

        return true;
    }


    /**
     * Creates the edit form
     *
     * @param   integer     $id Optional ID, current record used if zero
     * @return  string      HTML for edit form
     */
    public function showForm($id = 0)
    {
        global $_TABLES, $_CONF;

        if ($id != '') {
            // If an id is passed in, then read that record
            if (!$this->Read($id)) {
                return LIBRARY_errorMessage(_('Invalid Item ID'), 'info');
            }
        }
        $id = $this->id;

        $T = new \Template(Config::get('pi_path') . '/templates');
        $T->set_file(array(
            'product'   => 'item_form.thtml',
            'tips'      => 'tooltipster.thtml',
        ) );
        $action_url = Config::get('admin_url') . '/index.php';
        if ($this->oldid != '') {
            $retval = COM_startBlock(_('Edit') . ': ' . $this->title);
        } else {
            $retval = COM_startBlock(_('New Item'));
        }

        // Set up the wysiwyg editor, if available
        switch (PLG_getEditorType()) {
        case 'ckeditor':
            $T->set_var('show_htmleditor', true);
            PLG_requestEditor('library','library_entry','ckeditor_library.thtml');
            PLG_templateSetVars('library_entry', $T);
            break;
        case 'tinymce':
            $T->set_var('show_htmleditor',true);
            PLG_requestEditor('library','library_entry','tinymce_library.thtml');
            PLG_templateSetVars('library_entry', $T);
            break;
        default:
            // don't support others right now
            $T->set_var('show_htmleditor', false);
            break;
        }
        $total_instances = count(Instance::getAll($this->id));

        $T->set_var(array(
            'lang_item_info' => _('Item Information'),
            'oldid'         => $this->oldid,
            'dt_add'        => $this->dt_add,
            'id'            => $this->id,
            'title'         => htmlspecialchars($this->title),
            'subtitle'      => htmlspecialchars($this->subtitle),
            'category'      => $this->cat_id,
            'dscp'          => htmlspecialchars($this->dscp),
            'publisher'     => $this->publisher,
            'pub_date'      => $this->pub_date,
            'author'        => $this->author,
            'daysonhold'    => $this->daysonhold,
            'maxcheckout'   => $this->maxcheckout,
            'pi_admin_url'  => Config::get('admin_url'),
            'keywords'      => htmlspecialchars($this->keywords),
            'cat_select'    => Category::buildSelection($this->cat_id),
            'pi_url'        => Config::get('url'),
            'doc_url'       => LIBRARY_getDocURL('item_form.html',
                                            $_CONF['language']),
            'lookup_method' => Config::get('lookup_method'),
            'add_instances' => $this->isNew ? 1 : 0,
            //            'total_instances' => sprintf($LANG_LIB['total_instances'], $total_instances),
            'total_instances' => sprintf(
                _n(
                    '%d instance in the database',
                    '%d total instances in the database',
                    $total_instances
                ),
                $total_instances),
            'type_select'   => MediaType::buildSelection($this->type, false),
            'ena_chk'       => $this->enabled == 1 ? ' checked="checked"' : '',
            'lang_add_instances' => _('Add Instances'),
            'lang_search_openlib' => _('Search OpenLib'),
            'lang_item_id'  => _('Item ID'),
            'lang_item_info' => _('Item Information'),
            'lang_item_name' => _('Item Name'),
            'lang_subtitle' => _('Subtitle'),
            'lang_category' => _('Category'),
            'lang_author' => _('Author'),
            'lang_publisher' => _('Publisher'),
            'lang_pub_date' => _('Date Published'),
            'lang_type' => _('Type'),
            'lang_dscp' => _('Description'),
            'lang_keywords' => _('Keywords'),
            'lang_images' => _('Images'),
            'lang_delete' => _('Delete'),
            'lang_maxcheckout' => _('Max Checkout Days'),
            'lang_daysonhold' => _('Days to hold for waitlist'),
            'lang_enabled' => _('Enabled'),
            'lang_comments' => _('Enable Comments'),
            'lang_saveitem' => _('Save Item'),
            'lang_clearform' => _('Reset Form'),
            'lang_cancel' => _('Cancel'),
            'lang_delete' => _('Delete'),
            'lang_conf_del_item' => _('Are you sure you want to delete this item?'),
            'lang_yes' => _('Yes'),
            'lang_no' => _('No'),
        ) );
        if (!$this->isNew && !self::isUsed($this->id)) {
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
                $filepath = Config::get('image_dir') . '/' . $prow['filename'];
                $T->set_var(array(
                    'img_url'   => LGLIB_ImageUrl($filepath, 800, 600), // todo
                    'thumb_url' => LGLIB_ImageUrl(
                        $filepath,
                        Config::get('max_thumb_size'),
                        Config::get('max_thumb_size')
                    ),
                    'seq_no'    => $i,
                    'id'        => $this->id,
                    'del_img_url' => Config::get('admin_url') . '/index.php' .
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
        for ($j = $i; $j < Config::get('max_images'); $j++) {
            $T->parse('UFLD', 'UploadFld', true);
        }
        $T->parse('tooltipster', 'tips');
        $retval .= $T->parse('output', 'product');
        @setcookie($_CONF['cookie_name'].'fckeditor',
                SEC_createTokenGeneral('advancededitor'),
                time() + 1200, $_CONF['cookie_path'],
                $_CONF['cookiedomain'], $_CONF['cookiesecure']);

        $retval .= COM_endBlock();
        return $retval;
    }   // function showForm()


    /**
     * Toggles the value of a field.
     *
     * @param   integer $oldvalue   Original value to change
     * @param   string  $varname    Name of field
     * @param   string  $id         Item ID number of element to modify
     * @return  integer     New value, or old value upon failure
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
     * Sets the "enabled" field to the specified value.
     *
     * @param   integer $oldvalue   Original value to be changed
     * @param   string  $id         ID number of element to modify
     * @return         New value, or old value upon failure
     */
    public static function toggleEnabled($oldvalue, $id)
    {
        $oldvalue = $oldvalue == 0 ? 0 : 1;
        if ($id == '') return $oldval;
        return self::_toggle($oldvalue, 'enabled', $id);
    }


    /**
     * Determine if this item has any transaction or waitlist records.
     * Typically used to prevent deletion of product records that have
     * dependencies.
     *
     * @param   string  $item_id    Library Item ID
     * @return  boolean         True if used, False if not
     */
    public static function isUsed($item_id)
    {
        global $_TABLES;

        $item_id = DB_escapeString($item_id);
        if (DB_count($_TABLES['library.log'], 'id',
                $item_id) > 0) {
            return true;
        } elseif (DB_count($_TABLES['library.waitlist'], 'id',
                $item_id) > 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Display the detail page for the product.
     *
     * @return  string      HTML for the product page.
     */
    public function Detail()
    {
        global $_CONF, $_TABLES, $_USER;

        USES_lib_comments();

        if ($this->id == '') {
            return LIBRARY_errorMessage(_('Invalid Item ID'), 'info');
        }

        $retval = COM_startBlock();
        $T = new \Template(Config::get('pi_path') . '/templates');
        $T->set_file(array(
            'item' => 'item_detail.thtml',
            'formjs' => 'checkinout_js.thtml',
        ) );

        // Highlight the query terms if coming from a search
        if (isset($_REQUEST['query']) && !empty($_REQUEST['query'])) {
            $title = COM_highlightQuery($this->title, $_REQUEST['query']);
            $subtitle = COM_highlightQuery($this->subtitle, $_REQUEST['query']);
            $l_desc = COM_highlightQuery($this->dscp, $_REQUEST['query']);
        } else {
            $title = $this->title;
            $subtitle = $this->subtitle;
            $l_desc = $this->dscp;
        }
        $T->set_var(array(
            'user_id'           => $_USER['uid'],
            'id'                => $this->id,
            'title'             => $title,
            'subtitle'          => $subtitle,
            'dscp'              => $l_desc,
            'img_cell_width'    => Config::get('max_thumb_size') + 20,
            'pi_url'            => Config::get('url'),
            'avail_blk'         => $this->AvailBlock(),
            'publisher'         => $this->publisher,
            'pub_date'          => $this->pub_date,
            'author'            => $this->author,
            'listing_url'       => $this->ListingUrl,
            'can_edit'          => plugin_ismoderator_library(),
            'lang_edit'         => _('Edit'),
            'lang_back_to_list' => _('Back to List'),
            'lang_publisher'    => _('Publisher'),
            'lang_pub_date'     => _('Date Published'),
            'lang_author'       => _('Author'),
            'lang_click_to_enlarge' => _('Click to Enlarge Image'),
        ) );

        // Retrieve the photos and put into the template
        $sql = "SELECT img_id, filename
                FROM {$_TABLES['library.images']}
                WHERE item_id='{$this->id}'";
        //echo $sql;die;
        $img_res = DB_query($sql);
        $photo_detail = '';
        $T->set_var('have_photo', '');     // assume no photo available
        for ($i = 0; $prow = DB_fetchArray($img_res, false); $i++) {
            $img_file = Config::get('image_dir') . '/' . $prow['filename'];
            $tn_url = LGLIB_ImageUrl(
                $img_file,
                Config::get('max_thumb_size'),
                Config::get('max_thumb_size')
            );
            $img_url = LGLIB_ImageUrl($img_file, 800, 600);
            if ($tn_url !== '') {
                if ($i == 0) {
                    $T->set_var('main_img_url', LGLIB_ImageUrl($img_file,
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
                CMT_userComments($this->id, $this->title, 'library', '',
                    '', 0, 1, false, false, $mode));
        }

        if (Config::get('ena_ratings') == 1) {
            $ratedIds = RATING_getRatedIds('library');
            if (in_array($this->id, $ratedIds)) {
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

        if (plugin_ismoderator_library()) {
            $T->parse('checkinout_js', 'formjs');
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
     * Create a formatted display-ready version of the error messages.
     *
     * @return  string      Formatted error messages.
     */
    public function PrintErrors()
    {
        $retval = '';
        foreach($this->Errors as $key=>$msg) {
            $retval .= "<li>$msg</li>\n";
        }
        return $retval;
    }


    /**
     * Check out this item to a user.
     *
     * @param   integer $to     User ID of the borrower
     * @param   string  $due    Optional due date
     */
    public function checkOut($to, $due='')
    {
        $to = (int)$to;
        if (
            $to == 1 ||         // Can't check out to anonymous
            ($to == 0 && empty($_POST['co_username']))
        ) {
            return;
        }
        if (empty($due)) {
            $due = LIBRARY_dueDate($this->maxcheckout)->toUnix();
        } else {
            $due = (int)$due;
        }

        $instances = Instance::getAll($this->id, LIB_STATUS_AVAIL);
        if (empty($instances)) {
            COM_errorLog("Checkout:: no instances found for {$this->id}");
            return;
        }
        Instance::checkOut($instances[0], $to, $due);
        // Delete this user from the waitlist, if applicable
        Waitlist::Remove($this->id, $to);

        // Reset other waitlist expirations. If this borrower was not the next
        // in line, the actual first borrower probably has a reservation
        // expiration that should be removed.
        Waitlist::resetExpirations($this->id);
    }


    /**
     * Check in an item.
     *
     * @param   integer $instance_id    Item instance ID
     */
    public function checkIn($instance_id)
    {
        $I = new Instance($instance_id);
        COM_errorLog("checking in $instance_id");
        $I->checkIn();
        // If there's a reservation for this item, notify the reserver.
        Waitlist::notifyNext($this->id);
    }


    /**
     * Creates a block showing the item availability and links.
     * Meant to be added to the detail or listing.
     *
     * @return  string      HTML for block.
     */
    public function AvailBlock()
    {
        global $_TABLES, $_USER;

        $T = new \Template(Config::get('pi_path') . '/templates');
        $T->set_file('avail', 'avail_block.thtml');
        $avail = Instance::getAll($this->id, LIB_STATUS_AVAIL);
        $waitlisters = Waitlist::countByItem($this->id);
        $num_avail = max(count($avail) - $waitlisters, 0);
        if (!$this->canCheckout()) {
            $can_reserve = false;
            $is_reserved = false;
            $reserve_txt = _('Login Required');
            $waitlisters = 0;
            $user_wait_items = 0;
            $waitlist_txt = '';
        } else {
            $waitlist_pos = Waitlist::getUserPosition($this->id, $_USER['uid']);
            //$waitlisters = Waitlist::countByItem($this->id);
            $user_wait_items = Waitlist::countByUser($_USER['uid']);
            $reserve_txt = $waitlisters ? sprintf(_('Pending reservations: %d'), $waitlisters) : '';
            $waitlist_txt = '';
            if ($waitlist_pos > 0) {
                $can_reserve = false;
                $is_reserved = true;
                $waitlist_txt = sprintf(_('You\'re #%d on the waiting list'), $waitlist_pos);
            } else {
                $can_reserve = $user_wait_items < Config::get('max_wait_items') ? true : false;
                $is_reserved = false;
            }
        }

        // Check if the current user already has the item checked out
        $max_wait_items = Config::get('max_wait_items');
        $item_checked_out = Instance::UserHasItem($this->id, $_USER['uid']);
        $all_checked_out= Instance::countByUser($_USER['uid']);
        if ($item_checked_out) {
        //if ($all_checked_out >= $this->user_ckout_limit) {
            $avail_txt = _('Checked out by you');
            $can_reserve = false;
            $wait_action_txt = '';
            $reserve_txt = '';
        } else {
            if ($num_avail > 0) {
                if (($user_wait_items + $all_checked_out) < $max_wait_items) {
                    $avail_txt = sprintf(_('%d available'), $num_avail);
                } elseif (!$is_reserved) {
                    $avail_txt = sprintf(_('You can reserve up to <br />%d items at a time.'), $max_wait_items);
                    $can_reserve = false;
                } else {
                    $avail_txt = '';
                }
            } else {
                $avail_txt = _('Checked Out');
                $avail_icon = 'red.png';
            }
        }
        $total_instances = count(self::getInstances($this->id));
        if ($total_instances > count($avail)) {
            $can_checkin = true;
        } else {
            $can_checkin = false;
        }
        $T->set_var(array(
            'can_reserve'   => $can_reserve,
            'is_reserved'   => $is_reserved,
            'wait_limit_reached' => $user_wait_items >= $max_wait_items,
            'avail_txt'     => $avail_txt,
            'waitlist_txt'  => $waitlist_txt,
            'due_dt'        => '',
            'reserve_txt' => $reserve_txt,
            'id'            => $this->id,
            'pi_url'        => Config::get('url'),
            'pi_admin_url'  => Config::get('admin_url'),
            'is_librarian'  => plugin_ismoderator_library(),
            'can_checkout'  => count($avail),
            'can_checkin'   => $can_checkin,
            'num_avail'     => sprintf(_('%s available'), count($avail) . '/' . $total_instances),
            'lang_add_waitlist' => _('Reserve'),
            'lang_click_to_remove' => _('Cancel'),
            'lang_checkin'  => _('Check In'),
            'lang_checkout' => _('Check Out'),
            'lang_due'      => _('Due'),
        ) );
        $T->parse('output', 'avail');
        $retval = $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
     * Allow the listing URL to be overridden with parameters, etc.
     *
     * @param   string  $url    New complete listing URL
     * @return  object  $this
     */
    public function setListUrl($url)
    {
        $this->ListingUrl = $url;
        return $this;
    }


    /**
     * Add one or more instances of an item
     *
     * @param   string  $item_id    Item ID
     * @param   integer $count      Number of instances to add
     */
    public static function makeClone($item_id, $count = 1)
    {
        global $_TABLES;

        $count = (int)$count;
        $item_id = DB_escapeString($item_id);
        $values = array();
        for ($i = 0; $i < $count; $i++) {
            $values[] = "('$item_id')";
        }
        if (!empty($values)) {
            $values = implode(',', $values);
            $sql = "INSERT INTO {$_TABLES['library.instances']}
                    (item_id) VALUES $values";
            DB_query($sql);
        }
    }


    /**
     * Shortcut function to get all the instances of an item with a given status
     *
     * @param   string  $item_id    Item ID
     * @param   integer $status     One of 0 = all, 1 = available, 2 = checked out, 3 = all
     * @return  array       Array of instance records
     */
    public static function getInstances($item_id, $status = 0)
    {
        return Instance::getAll($item_id, $status);
    }


    /**
     * Check if the current user can view this item.
     * Just calls Category::hasAccess() if the category is valid.
     *
     * @return  boolean     True if view access is allowed.
     */
    public function canView()
    {
        if ($this->Category !== NULL &&
            $this->Category->hasAccess()) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Check if the current user can check out this item.
     * Requires view access to the category along with the checkout
     * privilege.
     *
     * @param   integer $uid    User ID being checked. @deprecated.
     * @return  boolean     True if the user can checkout this item.
     */
    public function canCheckout($uid = 0)
    {
        global $_USER;
        static $ckouts = array();

        if (!$this->canView() || !SEC_hasRights('library.checkout')) {
            return false;
        }
/*
        if ($uid == 0) {
            $uid = $_USER['uid'];
        }
        $uid = (int)$uid;
        $checkouts = Instance::countByUser($uid);
        if ($checkouts >= $this->user_ckout_limit) {
            return false;
        }
 */
        // Didn't fail any conditions, return OK
        return true;
    }


    /**
     * Display the item checkin form.
     *
     * @param   string  $id     Item ID
     * @param   boolean $ajax   True if this is an AJAX form
     * @return  string          HTML for the form
     */
    public static function checkinForm($id, $ajax=false)
    {
        $I = self::getInstance($id);
        if ($I->isNew || $I->id == '') {
            return 'Invalid Item Selected';
        }

        $instances = self::getInstances($id, LIB_STATUS_OUT);
        COM_errorLog("instances: " . print_r($instances,true));
        $opts = '';
        foreach ($instances as $inst_id=>$inst) {
            $username = COM_getDisplayName($inst->getUid());
            $due = $inst->getDueDate('Y-m-d');
            $opts .= '<option value="' . $inst->getID() . '">' .
                $username . ' - ' . _('Due Date') . ': ' . $due .
                '</option>';
        }
        $T = new \Template(Config::get('pi_path') . '/templates');
        $T->set_file('form', 'checkin_form.thtml');
        $T->set_var(array(
            'title'         => _('Library Administration'),
            'action_url'    => Config::get('admin_url') . '/index.php',
            'pi_url'        => Config::get('url'),
            'item_id'       => $I->getID(),
            'title'         => $I->getTitle(),
            'subtitle'      => $I->getSubtitle(),
            'item_desc'     => $I->getDscp(),
            'instance_select' => $opts,
            'is_ajax'       => $ajax,
            'lang_item_id'  => _('Item ID'),
            'lang_item_name' => _('Item Name'),
            'lang_checkin'  => _('Check In'),
            'lang_instance' => _('Instance'),
            'lang_hlp_checkin_instance' => _('Select the specific item instance to check in.'),
            'lang_cancel'   => _('Cancel'),
        ) );
        $T->parse('output', 'form');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Display the item checkout form.
     *
     * @param   string  $id     Item ID
     * @param   boolean $ajax   True if this is an AJAX form
     * @return  string          HTML for the form
     */
    public static function checkoutForm($id, $ajax=false)
    {
        global $_CONF;

        $I = self::getInstance($id);
        if ($I->isNew || $I->id == '') {
            return '';
        }
        USES_library_functions();

        // Get the ISO language.  This is to load the correct language for
        // the calendar popup, so make sure a corresponding language file
        // exists.  Default to English if not found.
        $iso_lang = $_CONF['iso_lang'];
        if (!is_file($_CONF['path_html'] . Config::get('pi_name') .
            '/js/calendar/lang/calendar-' . $iso_lang . '.js')) {
            $iso_lang = 'en';
        }

        // Default to the plugin config for maxcheckout if not set for
        // this item.
        if ($I->maxcheckout < 1) {
            $I->maxcheckout = (int)Config::get('maxcheckout');
        }

        $T = new \Template(Config::get('pi_path') . '/templates');
        $T->set_file('form', 'checkout_form.thtml');
        $T->set_var(array(
            'title'         => _('Library Administration'),
            'action_url'    => Config::get('admin_url') . '/index.php',
            'pi_url'        => Config::get('url'),
            'item_id'       => $I->id,
            'title'         => $I->title,
            'subtitle'      => $I->subtitle,
            'item_desc'     => $I->dscp,
            'user_select'   => LIBRARY_userSelect($I->id),
            'due'           => LIBRARY_dueDate($I->maxcheckout)->format('Y-m-d'),
            'iso_lang'      => $iso_lang,
            'is_ajax'       => $ajax,
            'lang_item_id'  => _('Item ID'),
            'lang_item_name' => _('Item Name'),
            'lang_checkout_user' => _('Check out to user'),
            'lang_checkout' => _('Check Out'),
            'lang_dt_due'   => _('Date Due'),
            'lang_hlp_checkout_user' => _('Select the user to check out this item. The user at the top of the waiting list is shown first.'),
            'lang_hlp_dt_due' => _('Enter or select the due date for the item.'),
            'lang_cancel'   => _('Cancel'),
        ) );
        $T->parse('output', 'form');
        return $T->finish($T->get_var('output'));
    }

<<<<<<< HEAD

=======
>>>>>>> master
    /**
     * Product Admin List View.
     *
     * @param   integer $cat_id     Optional category to limit view
     * @param   integer $status     Optional status, to limit view
     */
    public static function adminList($cat_id = 0, $status = 0)
    {
        global $_CONF, $_TABLES, $_USER;
/*+------------------+---------------------+------+-----+---------+-------+
| Field            | Type                | Null | Key | Default | Extra |
+------------------+---------------------+------+-----+---------+-------+
| id               | varchar(40)         | NO   | PRI | NULL    |       |
| title            | varchar(128)        | NO   | MUL | NULL    |       |
| subtitle         | varchar(128)        | YES  |     | NULL    |       |
| cat_id           | int(11) unsigned    | NO   |     | 0       |       |
| dscp             | text                | YES  |     | NULL    |       |
| keywords         | varchar(255)        | YES  |     |         |       |
| author           | varchar(255)        | YES  |     |         |       |
| publisher        | varchar(255)        | YES  |     |         |       |
| pub_date         | varchar(20)         | YES  |     |         |       |
| type             | tinyint(2)          | YES  |     | 0       |       |
| qoh              | int(4)              | YES  |     | 1       |       |
| daysonhold       | int(4)              | YES  |     | 0       |       |
| maxcheckout      | int(4)              | NO   |     | 0       |       |
| enabled          | tinyint(1)          | YES  |     | 1       |       |
| dt_add           | int(11) unsigned    | YES  |     | NULL    |       |
| views            | int(4) unsigned     | YES  |     | 0       |       |
| comments         | int(5) unsigned     | YES  |     | 0       |       |
| comments_enabled | tinyint(1) unsigned | YES  |     | 0       |       |
| rating           | double(6,4)         | NO   |     | 0.0000  |       |
| votes            | int(11) unsigned    | NO   |     | 0       |       |
| status           | tinyint(1)          | YES  |     | 0       |       |
+------------------+---------------------+------+-----+---------+-------+*/

        $sql = "SELECT p.id, MAX(p.title) AS title,
            MAX(p.enabled) AS enabled,
            MAX(t.dscp) AS typename,
            MAX(c.cat_name) as cat_name
            FROM {$_TABLES['library.items']} p
            LEFT JOIN {$_TABLES['library.types']} t
                ON p.type = t.id
            LEFT JOIN {$_TABLES['library.categories']} c
                ON c.cat_id = p.cat_id
            LEFT JOIN {$_TABLES['library.instances']} inst
                ON p.id = inst.item_id
            LEFT JOIN {$_TABLES['library.waitlist']} w
                ON p.id = w.item_id ";
            //GROUP BY p.id ";
        $query_arr = array(
            'table' => 'library.items',
            'sql' => $sql,
            'query_fields' => array('p.name', 'p.dscp'),
            'default_filter' => '',
            //'group_by' => 'p.id',
        );

        switch ($status) {
        case 0:     // All
            $sql .= ' GROUP BY p.id';
            break;
        case 1:     // Available
            $sql .= " WHERE inst.uid = 0 GROUP BY p.id HAVING COUNT(inst.item_id) > 0";
            break;
        case 2:     // Checked Out
            $sql .= " WHERE inst.uid > 0 GROUP BY p.id HAVING COUNT(inst.item_id) > 0";
            break;
        case 3:     // Pending Actions, include available only
            $sql .= " GROUP BY p.id HAVING count(w.id) > 0";
            break;
        case 4:     // Overdue
            //$sql .= "LEFT JOIN {$_TABLES['library.instances']} inst
            //            ON p.id = inst.item_id
            $sql .= " WHERE inst.uid > 0 AND inst.due < UNIX_TIMESTAMP() ";
            $sql .= " GROUP BY  p.id ";
            break;
        }

        $display = '';
        $header_arr = array(
            array(  'text'  => _('Edit'),
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
            array(  'text'  => _('Copy'),
                'field' => 'copy',
                'sort'  => false,
                'align' => 'center',
            ),
            array(  'text'  => _('ID'),
                'field' => 'id',
                'sort'  => true,
            ),
            array(  'text'  => _('Enabled'),
                'field' => 'enabled',
                'sort'  => false,
                'align' => 'center',
            ),
            array(  'text'  => _('Item Name'),
                'field' => 'title',
                'sort'  => true,
            ),
            array(  'text'  => _('Media Type'),
                'field' => 'typename',
                'sort'  => true,
            ),
            array(  'text'  => _('Category'),
                'field' => 'cat_name',
                'sort'  => true,
            ),
            array(  'text'  => _('Available'),
                'field' => 'status',
                'sort'  => false,
                'align' => 'center',
            ),
            array(  'text'  => _('History'),
                'field' => 'history',
                'sort'  => false,
            ),
            array(  'text'  => _('Check Out'),
                'field' => 'checkout',
                'sort'  => false,
            ),
            array(  'text'  => _('Check In'),
                'field' => 'checkin',
                'sort'  => false,
            ),
            array(  'text'  => _('Delete'),
                'field' => 'delete',
                'sort'  => false,
                'align' => 'center',
            ),
        );

        $defsort_arr = array(
            'field' => 'id',
            'direction' => 'asc',
        );

        $display .= COM_startBlock(
            '', '',
            COM_getBlockTemplate('_admin_block', 'header')
        );

        $query_arr = array(
            'table' => 'library.items',
            'sql' => $sql,
            'query_fields' => array('p.name', 'p.dscp'),
            'default_filter' => '',
            'group_by' => 'p.id',
        );
        $text_arr = array(
            //'has_extras' => true,
            'form_url' => Config::get('admin_url') . '/index.php?status=' . $status,
        );
        $form_arr = LIBRARY_itemStatusForm($status);
        $filter = '';
        $extras = array(
            'status'    => $status,
        );
        if (!isset($_REQUEST['query_limit'])) {
            $_GET['query_limit'] = 20;
        }

        $display .= '<div class="floatright">' . COM_createLink(_('New Item'),
            Config::get('admin_url') . '/index.php?edititem=0',
            array('class' => 'uk-button uk-button-success')
        ) . '</div>';
        $display .= ADMIN_list(
            'library_adminlist_item',
            array(__CLASS__, 'adminListField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, $extras, '', $form_arr
        );
        $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $display;
    }


    /**
     * Get an individual field for the Item Admin screen.
     *
     * @param   string  $fieldname  Name of field (from the array, not the db)
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Array of all fields from the database
     * @param   array   $icon_arr   System icon array (not used)
     * @return  string              HTML for field display in the table
     */
    public static function adminListField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $_TABLES;

        $retval = '';

        $avail = count(Instance::getAll($A['id'], LIB_STATUS_AVAIL));
        $out = count(Instance::getAll($A['id'], LIB_STATUS_OUT));
        $total = $avail + $out;

        switch($fieldname) {
        case 'id':
            $retval = COM_createLink(
                $fieldvalue,
                Config::get('admin_url') . '/index.php?instances=x&item_id=' . $fieldvalue,
                array(
                    'title' => _('View Instances'),
                    'class' => 'tooltip',
                ) );
            break;

        case 'edit':
            $retval .= COM_createLink(
                '<i class="uk-icon uk-icon-edit"></i>',
                Config::get('admin_url') . "/index.php?edititem=x&amp;id={$A['id']}"
            );
            break;

        case 'copy':
            $retval .= COM_createLink(
                '<i class="uk-icon uk-icon-copy"></i>',
                Config::get('admin_url') . "/index.php?copyitem=x&amp;id={$A['id']}"
            );
            break;

        case 'delete':
            if (!self::isUsed($A['id'])) {
                $retval .= COM_createLink(
                    Icon::getHTML('delete'),
                    Config::get('admin_url') . '/index.php?deleteitem=x&amp;id=' . $A['id'],
                    array(
                        'onclick'=>'return confirm(\'' .
                        _('Are you sure you want to delete this item?') .
                        '\');',
                        'title' => _('Delete Item'),
                        'class' => 'tooltip',
                    )
                );
            }
            break;

        case 'enabled':
            $chk = $fieldvalue == 1 ? ' checked="checked"' : '';
            $retval .= "<input type=\"checkbox\" $chk value=\"1\" name=\"ena_check\"
                id=\"togenabled{$A['id']}\"
                onclick='LIBR_toggle(this,\"{$A['id']}\",\"enabled\",\"item\");'>".LB;
            break;

        case 'title':
            $retval = COM_createLink(
                $fieldvalue,
                Config::get('url') . '/index.php?detail=x&id=' . $A['id'],
                array(
                    'title' => _('View Item'),
                    'class' => 'tooltip',
                ) );
            break;

        case 'type':
            $retval = LGLIB_getVar(_('Media Types'), $A['type'], 'string', 'Unknown');
            break;

        case 'status':
            $retval = $avail . ' / ' . $total;
            break;
            if ($fieldvalue == LIB_STATUS_OUT) {
                if ($A['due'] < LIBRARY_now()) {
                    $cls = 'danger';
                    $msg = _('Overdue');
                } else {
                    $cls = 'unknown';
                    $msg = _('Checked Out');
                }
            } elseif (isset($A['wait_count']) && $A['wait_count'] > 0) {
                $cls = 'warning';
                $msg = _('Waitlisted');
            } elseif ($fieldvalue == LIB_STATUS_AVAIL) {
                $cls = 'ok';
                $msg = _('Available');
            } else {
                $cls = 'unknown';
                $msg = '';
            }
            $retval .= '<i class="uk-icon uk-icon-circle uk-icon-' . $cls .
                '" title="' . $msg . '" class="tooltip"></i>';
            break;

        case 'checkout':
            if ($avail > 0) {
                $retval .= COM_createLink(
                    _('Check Out'),
                    Config::get('admin_url') . '/index.php?checkoutform=x&id=' . $A['id']
                );
            }
            break;

        case 'checkin':
            if ($total > $avail) {
                $retval .= COM_createLink(
                    _('Check In'),
                    Config::get('admin_url') . '/index.php?checkinform=x&id=' . $A['id']
                );
            }
            break;

        case 'history':
            if (DB_count($_TABLES['library.log'], 'item_id', $A['id']) > 0) {
                $retval .= COM_createLink('<i class="uk-icon uk-icon-file-text-o"></i>',
                    Config::get('admin_url') . '/index.php?history=x&id=' . $A['id'],
                    array(
                        'title' => _('View History'),
                        'class' => 'tooltip',
                    ) );
            }
            break;

        default:
            $retval = htmlspecialchars($fieldvalue);
            break;
        }

        return $retval;
    }

}

function test()
{
    echo \_('This is a dummy test');
    echo _('This is a good string');
}

?>
