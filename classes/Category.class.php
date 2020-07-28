<?php
/**
 * Class to manage library item categories.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
 * @package    library
 * @version    0.0.1
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Library;

/**
 * Class for categories.
 * @package library
 */
class Category
{
    /** Indicate whether the current user is an administrator
     * @var boolean */
    private $isAdmin = 0;

    /** Indicate that this is a new category, vs. one read from the DB.
     * @var boolean */
    private $isNew = 1;

    /** Category record ID.
     * @var integer */
    private $cat_id = 0;

    /** Group ID that can view and check out.
     * @var integer */
    private $group_id = 13;

    /** Category name.
     * @var string */
    private $cat_name = '';

    /** Category description.
     * @var string */
    private $dscp = '';

    /** Flag indicating the category is enabled.
     * @var boolean */
    private $enabled = 1;

    /** Array of error messages, to be accessible by the calling routines.
     * @var array */
    public $Errors = array();


    /**
    * Reads in the specified class, if $id is set.  If $id is zero,
    * then a new entry is being created.
    *
    * @param    integer $id     Optional category ID
    * @param    array   $data   Optional database record
    */
    public function __construct($id=0, $data=NULL)
    {
        $this->properties = array();
        //$this->button_types = array('buy_now', 'add_cart'); // TODO
        $this->isNew = true;

        $id = (int)$id;
        if ($id < 1) {
            $this->cat_id = 0;
            $this->cat_name = '';
            $this->dscp = '';
            $this->group_id = Config::getInstance()->get('def_group_id');
            $this->enabled = 1;
        } else {
            $this->cat_id = $id;
            if ($data !== NULL) {
                $this->SetVars($data, true);
                $this->isNew = false;
            } elseif ($this->Read()) {
                $this->isNew = false;
            } else {
                $this->cat_id = 0;
                $this->isNew = true;
            }
        }
        $this->isAdmin = SEC_hasRights('library.admin') ? 1 : 0;
    }


    /**
     * Get an instance of a category object.
     * Caches objects in a static variable.
     *
     * @param   integer $cat_id     Category ID
     * @return  object              Category object
     */
    public static function getInstance($cat_id)
    {
        static $cats = array();
        $cat_id = (int)$cat_id;
        if ($cat_id < 1) {
            return new self();
        } elseif (isset($cats[$cat_id])) {
            return $cats[$cat_id];
        } else{
            $cats[$cat_id] = new self($cat_id);
        }
        return $cats[$cat_id];
    }


    /**
     * Sets all variables to the matching values from $row
     *
     * @param   array $row          Array of values, from DB or $_POST
     * @param   boolean $fromDB     True if this is from the databse.
     */
    public function setVars($row, $fromDB=true)
    {
        if (!is_array($row)) return;

        $this->cat_id = (int)$row['cat_id'];
        $this->dscp = $row['dscp'];
        $this->enabled = isset($row['enabled']) && $row['enabled'] ? 1 : 0;
        $this->cat_name = $row['cat_name'];
        $this->group_id = (int)$row['group_id'];
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @param   integer $id     Optional ID.  Current ID is used if zero.
     * @return  boolean         True if a record was read, False on failure
     */
    public function Read($id = 0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id == 0) $id = $this->cat_id;
        if ($id == 0) {
            $this->error = 'Invalid ID in Read()';
            return;
        }

        $result = DB_query("SELECT *
                    FROM {$_TABLES['library.categories']}
                    WHERE cat_id='$id'");
        if (!$result || DB_numRows($result) != 1) {
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->setVars($row);
            $this->isNew = false;
            return true;
        }
    }


    /**
     * Save the current values to the database.
     *
     * @param   array   $A      Optional array of values from $_POST
     * @return  boolean         True if no errors, False otherwise
     */
    public function Save($A = array())
    {
        global $_TABLES;

        if (is_array($A)) {
            $this->setVars($A, false);
        }

        // Insert or update the record, as appropriate, as long as a
        // previous error didn't occur.
        if (empty($this->Errors)) {
            if ($this->isNew) {
                $sql1 = "INSERT INTO {$_TABLES['library.categories']} SET ";
                $sql3 = '';
            } else {
                $sql1 = "UPDATE {$_TABLES['library.categories']} SET ";
                $sql3 = " WHERE cat_id = {$this->cat_id}";
            }
            $sql2 = "cat_name = '" . DB_escapeString($this->cat_name) . "',
                dscp = '" . DB_escapeString($this->dscp) . "',
                enabled = '{$this->enabled}',
                group_id = '{$this->group_id}'";
            //echo $sql1.$sql2.$sql3;die;
            DB_query($sql1 . $sql2 . $sql3, 1);
            if (DB_error()) {
                $this->AddError('Failed to insert or update record');
            }
        }

        if (empty($this->Errors)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Delete the current category record from the database
     *
     * @return  boolean True if deleted, False if not
     */
    public function Delete()
    {
        global $_TABLES;

        // Can't delete root category
        if ($this->cat_id > 1) {
            DB_delete($_TABLES['library.categories'], 'cat_id', $this->cat_id);
            $this->cat_id = 0;
            return true;
        } else {
            return false;
        }
    }


    /**
     * Determines if the current record is valid.
     *
     * @return  boolean     True if ok, False when first test fails.
     */
    public function isValidRecord()
    {
        // Check that basic required fields are filled in
        if ($this->cat_name == '') {
            return false;
        }

        return true;
    }


    /**
     * Creates the edit form.
     *
     * @param   integer $id Optional ID, current record used if zero
     * @return  string      HTML for edit form
     */
    public function showForm()
    {
        global $_TABLES, $_CONF;

        if ($this->cat_id > 0) {
            $retval = COM_startBlock(_('Edit') . ': ' . $this->cat_name);
        } else {
            $retval = COM_startBlock(_('Create Category'));
        }

        $T = new \Template(__DIR__ . '/../templates');
        $T->set_file(array(
            'category'  => 'category_form.thtml',
            'tips'      => 'tooltipster.thtml',
        ));
        $T->set_var(array(
            'cat_id'        => $this->cat_id,
            'action_url'    => Config::getInstance()->get('admin_url'),
            'cat_name'      => $this->cat_name,
            'dscp'          => $this->dscp,
            'ena_chk'       => $this->enabled == 1 ? 'checked="checked"' : '',
            'candelete'     => !self::isUsed($this->cat_id),
            'group_dropdown' => SEC_getGroupDropdown($this->group_id, 3),
            'doc_url'       => LIBRARY_getDocURL('cat_form.html', $_CONF['language']),
            'lang_cat_name' => _('Category Name'),
            'lang_parent_cat' => _('Parent Category'),
            'lang_dscp'     => _('Description'),
            'lang_enabled'  => _('Enabled?'),
            'lang_group'    => _('Group'),
            'lang_savecat'  => _('Save Category'),
            'lang_cancel'   => _('Cancel'),
            'lang_delcat'   => _('Delete Category'),
        ) );
        $T->parse('tooltipster', 'tips');
        $retval .= $T->parse('output', 'category');

        @setcookie($_CONF['cookie_name'].'fckeditor',
                SEC_createTokenGeneral('advancededitor'),
                time() + 1200, $_CONF['cookie_path'],
                $_CONF['cookiedomain'], $_CONF['cookiesecure']);

        $retval .= COM_endBlock();
        return $retval;
    }


    /**
     * Sets the "enabled" field to the specified value.
     *
     * @param   integer $oldvalue   Original value
     * @param   string  $varname    Field name to update
     * @param   integer $id         Category ID
     * @return  integer     New value, or old value upon failure
     */
    private static function _toggle($oldvalue, $varname, $id=0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id == 0) {
            if (is_object($this))
                $id = $this->id;
            else
                return;
        }

        // If it's still an invalid ID, return the old value
        if ($id < 1)
            return $oldvalue;

        // Determing the new value (opposite the old)
        $newvalue = $oldvalue == 1 ? 0 : 1;

        $sql = "UPDATE {$_TABLES['library.categories']}
                SET $varname=$newvalue
                WHERE cat_id=$id";
        //echo $sql;die;
        DB_query($sql);

        return $newvalue;
    }


    /**
     * Sets the "enabled" field to the specified value.
     *
     * @param   integer $oldvalue Current, original value
     * @param   integer $id     ID number of element to modify
     * @return  integer     New value, or old value upon failure
     */
    public static function toggleEnabled($oldvalue, $id=0)
    {
        $oldvalue = $oldvalue == 0 ? 0 : 1;
        $id = (int)$id;
        if ($id == 0) {
            return $oldvalue;
        }
        return self::_toggle($oldvalue, 'enabled', $id);
    }


    /**
     * Determine if this product is mentioned in any purchase records.
     * Typically used to prevent deletion of product records that have
     * dependencies.
     *
     * @param   integer $cat_id     ID of category to check
     * @return  boolean             True if used, False if not
     */
    public static function isUsed($cat_id=0)
    {
        global $_TABLES;

        // Always treat root category as in_use
        if ($cat_id == 1) return true;

        // Check if any products are under this category
        //if (DB_count($_TABLES['library.prodXcat'], 'cat_id', $cat_id) > 0) {
        if (DB_count($_TABLES['library.items'], 'cat_id', $cat_id) > 0) {
            return true;
        }
        return false;
    }


    /**
     * Add an error message to the Errors array.
     * Also could be used to log certain errors or perform other actions.
     *
     * @param   string  $msg    Error message to append
     */
    public function AddError($msg)
    {
        $this->Errors[] = $msg;
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
     * Recurse through the category table building an option list
     * sorted by id.
     *
     * @param   integer $sel        Category ID to be selected in list
     * @param   boolean $enabled    True to get only enabled categories
     * @return  string              HTML option list, without <select> tags
     */
    public static function buildSelection($sel=0, $enabled=true)
    {
        global $_TABLES;

        $str = '';
        $root = 1;
        $Cats = self::getTree($enabled);
        foreach ($Cats as $Cat) {
            $selected = $Cat->cat_id == $sel ? 'selected="selected"' : '';
            $str .= "<option value=\"{$Cat->cat_id}\" $selected>";
            $str .= $Cat->dscp;
            $str .= "</option>\n";
        }
        return $str;
    }


    /**
     * Read all the categories into a static array.
     *
     * @param   integer $enabled    True to get only enabled categories
     * @return  array           Array of category objects
     */
    public static function getTree($enabled = false)
    {
        global $_TABLES;

        $All = array();
        $key = 'category_tree_' . $enabled ? 1 : 0;
        $sql = "SELECT * FROM {$_TABLES['library.categories']}";
        if ($enabled) {
            $sql .= ' WHERE enabled = 1';
        }
        $sql .= ' ORDER BY cat_id ASC';
        //echo $sql;die;
        $result = DB_query($sql);
        $cats = DB_fetchAll($result, false);
        foreach ($cats as $A) {
            $All[$A['cat_id']] = new self($A['cat_id'], $A);
        }
        return $All;
    }


    /**
     * Check if the current or specified user has view/checkout access.
     *
     * @param   integer $uid    Optional user ID, current user if empty
     * @return  boolean         True if user has access
     */
    public function hasAccess($uid=0)
    {
        global $_GROUPS, $_USER;

        if ($uid == 0) $uid = $_USER['uid'];
        if (SEC_inGroup($this->group_id, $uid) || plugin_ismoderator_library()) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Category Admin List View.
     */
    public static function adminlist()
    {
        global $_CONF, $_TABLES, $_USER;

        $display = '';
        $sql = "SELECT cat.cat_id, cat.cat_name, cat.dscp, cat.enabled
            FROM {$_TABLES['library.categories']} cat";

        $header_arr = array(
            array(
                'text' => _('Edit'),
                'field' => 'edit',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => _('ID'),
                'field' => 'cat_id',
                'sort' => true,
            ),
            array(
                'text' => _('Enabled'),
                'field' => 'enabled',
                'sort' => false,
                'align' => 'center',
            ),
            array(
                'text' => _('Category'),
                'field' => 'cat_name',
                'sort' => true,
            ),
            array(
                'text' => _('Description'),
                'field' => 'dscp',
                'sort' => true,
            ),
            array(
                'text' => _('Delete'),
                'field' => 'delete',
                'sort' => false,
                'align' => 'center',
            ),
        );
        $display .= COM_startBlock(
            '', '',
            COM_getBlockTemplate('_admin_block', 'header')
        );

        $defsort_arr = array(
            'field' => 'cat_id',
            'direction' => 'asc',
        );
        $query_arr = array(
            'table' => 'library.categories',
            'sql' => $sql,
            'query_fields' => array('cat.name', 'cat.dscp'),
            'default_filter' => 'WHERE 1=1',
        );
        $text_arr = array(
            //'has_extras' => true,
            'form_url' => Config::getInstance()->get('admin_url') . '/index.php',
        );
        $form_arr = array();
        $filter = '';
        if (!isset($_REQUEST['query_limit'])) {
            $_GET['query_limit'] = 20;
        }

        $display .= '<div class="floatright">';
        $display .= COM_createLink(
            _('New Category'),
            Config::getInstance()->get('admin_url') . '/index.php?editcat=0',
            array(
                'class' => 'uk-button uk-button-success',
            )
        ) . '</div>';

        $display .= ADMIN_list(
            'library',
            array(__CLASS__, 'getAdminField'),
            $header_arr, $text_arr, $query_arr, $defsort_arr,
            $filter, '', '', $form_arr
        );
        $display .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
        return $display;
    }


    /**
     * Get an individual field for the category admin list.
     *
     * @param   string  $fieldname  Name of field (from the array, not the db)
     * @param   mixed   $fieldvalue Value of the field
     * @param   array   $A          Array of all fields from the database
     * @param   array   $icon_arr   System icon array (not used)
     * @return  string              HTML for field display in the table
     */
    public static function getAdminField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF;

        $retval = '';

        switch($fieldname) {
        case 'edit':
            $retval .= COM_createLink(
                '<i class="uk-icon uk-icon-edit"></i>',
                Config::getInstance()->get('admin_url') . "/index.php?mode=editcat&amp;id={$A['cat_id']}",
                array(
                    'title' => _('Edit'),
                    'class' => 'tooltip',
                )
            );
            break;

        case 'enabled':
            $chk = $fieldvalue == 1 ? 'checked="checked"' : '';
            $retval .= "<input type=\"checkbox\" $chk value=\"1\" name=\"ena_check\"
                id=\"togenabled{$A['cat_id']}\" class=\"tooltip\" title=\"Enable/Disable\"
                onclick='LIBR_toggle(this,\"{$A['cat_id']}\",\"{$fieldname}\",".
                "\"category\");' />" . LB;
            break;

        case 'delete':
            if (!self::isUsed($A['cat_id'])) {
                $retval .= COM_createLink(
                    Icon::getHTML('delete'),
                    Config::getInstance()->get('admin_url') . '/index.php?deletecat&id=' . $A['cat_id'],
                    array(
                        'onclick' => 'return confirm(\'' .
                        _('Are you sure you want to delete this item?'),
                        '\');',
                        'title' => _('Delete Item'),
                        'class' => 'tooltip',
                    )
                );
            } else {
                $retval .= '<i class="tooltip uk-icon uk-icon-remove uk-text-danger' .
                    '" title="' . _('Cannot delete categories that are in use.') . '"></i>';
            }
            break;

        default:
            $retval = htmlspecialchars($fieldvalue);
            break;
        }
        return $retval;
    }

}   // class Category

?>
