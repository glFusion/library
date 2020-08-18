<?php
/**
 * Class to manage library media types
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
*   Class for media types.
*   @package library
*/
class MediaType
{
    /** Record ID.
     * @var integer */
    private $id = 0;

    /** Description.
     * @var string */
    private $dscp = '';

    /** Indicate whether the current user is an administrator.
     *  @var boolean */
    private $isAdmin = 0;

    /** Indicate that this is a new record.
     * @var boolean */
    private $isNew = 1;

    /** Array of error messages, to be accessible by the calling routines.
     *  @var array */
    public $Errors = array();


    /**
     * Constructor.
     * Reads in the specified class, if $id is set.  If $id is zero,
     * then a new entry is being created.
     *
     * @param   integer $id     Optional type ID
     */
    public function __construct($id=0)
    {
        if (is_array($id)) {    // Record passed in
            $this->setVars($id);
            $this->isNew = false;
        } else {
            $id = (int)$id;
            if ($id < 1) {
                $this->dscp = '';
            } else {
                $this->id = $id;
                if (!$this->Read()) {
                    $this->id = 0;
                }
            }
        }
        $this->isAdmin = SEC_hasRights('library.admin') ? 1 : 0;
    }


    /**
     * Get an instance of a MediaType object.
     * There isn't likely to be a large number of objects, so just call
     * self::getAll() and if the ID is found in the array, instantiate
     * an object.
     *
     * @param   integer $id     Media Type ID
     * @return  object          MediaType object
     */
    public static function getInstance($id)
    {
        $types = self::getAll();
        if (isset($types[$id])) {   // previously read this object
            return new self($types[$id]);
        } else{
            return new self();
        }
    }


    /**
     * Get all MediaType records and return as an array of id=>array(data).
     *
     * @return  array   Array of mediatype records
     */
    public static function getAll()
    {
        global $_TABLES;

        static $types = NULL;
        if ($types === NULL) {      // check if previously loaded
            $key = 'all_mediatypes';
            $types = array();
            $res = DB_query("SELECT * FROM {$_TABLES['library.types']}");
            if ($res) {
                while ($A = DB_fetchArray($res, false)) {
                    $types[$A['id']] = $A;
                }
            }
        }
        return $types;
    }


    /**
     * Sets all variables to the matching values from $rows.
     *
     * @param   array $row Array of values, from DB or $_POST
     */
    public function SetVars($row)
    {
        if (!is_array($row)) return;

        $this->id = (int)$row['id'];
        $this->dscp = $row['dscp'];
    }


    /**
     * Read a specific record and populate the local values.
     *
     * @param   integer $id Optional ID.  Current ID is used if zero.
     * @return  boolean     True if a record was read, False on failure
     */
    public function Read($id = 0)
    {
        global $_TABLES;

        $id = (int)$id;
        if ($id == 0) $id = $this->id;
        if ($id == 0) {
            $this->error = 'Invalid ID in Read()';
            return;
        }

        $result = DB_query(
            "SELECT * FROM {$_TABLES['library.types']}
            WHERE id='$id'"
        );
        if (!$result || DB_numRows($result) != 1) {
            return false;
        } else {
            $row = DB_fetchArray($result, false);
            $this->SetVars($row);
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
            $this->SetVars($A);
        }

        if ($this->isNew) {
            $sql = "INSERT INTO {$_TABLES['library.types']}
                (id, dscp)
                VALUES (0, '" . DB_escapeString($this->dscp) . "')";
        } else {
            $sql = "UPDATE {$_TABLES['library.types']}
                SET dscp ='" . DB_escapeString($this->dscp) . "'
                WHERE id = '{$this->id}'";
        }
        $status = DB_query($sql);
        if (!$status) {
                $this->AddError('Failed to insert or update record');
        }

        if (empty($this->Errors)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *   Delete the current media type record from the database.
     */
    public function Delete()
    {
        global $_TABLES;

        if ($this->id > 0) {
            DB_delete($_TABLES['library.types'], 'id', $this->id);
            $this->id = 0;
            return true;
        } else {
            return false;
        }
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

        $T = new \Template(__DIR__ . '/../templates');
        $T->set_file(array(
            'type'  =>'mediatype_form.thtml',
            'tips'  => 'tooltipster.thtml',
        ) );

        // If we have a nonzero media type ID, then we edit the existing record.
        // Otherwise, we're creating a new item.  Also set the $not and $items
        // values to be used in the parent media type selection accordingly.
        if ($this->id > 0) {
            $retval = COM_startBlock(_('Edit') . ': ' . $this->dscp);
            $T->set_var('id', $this->id);
        } else {
            $retval = COM_startBlock(_('New Media Type'));
            $T->set_var('id', '');
        }

        $T->set_var(array(
            'action_url'    => Config::getInstance()->get('admin_url'),
            'dscp'          => $this->dscp,
            'candelete'     => !$this->isNew && !self::isUsed($this->id),
            'lang_type'     => _('Media Type'),
            'lang_hlp_mt_type' => _('Enter a description for this media type. This should be unique.'),
            'lang_save'     => _('Save'),
            'lang_cancel'   => _('Cancel'),
            'lang_delete'   => _('Delete'),
            'doc_url'       => LIBRARY_getDocURL('cat_form.html', $_CONF['language']),
        ) );
        $T->parse('tooltipster', 'tips');
        $retval .= $T->parse('output', 'type');
        $retval .= COM_endBlock();
        return $retval;
    }


    /**
     * Determine if this media type is associated with any items.
     * Caches the result for an ID since this might be called standalone
     * and also by canDelete().
     *
     * @param   integer $id     Media Type ID
     * @return  boolean True if used, False if not
     */
    public static function isUsed($id)
    {
        global $_TABLES;

        static $ids = array();
        if (!isset($ids[$id])) {
            $ids[$id] = (int)DB_count($_TABLES['library.items'], 'type', $id);
        }
        return ($ids[$id] > 0);
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
     * Get the options for a selection list.
     *
     * @param   integer $sel_type   Selected media type
     * @param   boolean $used_only  True to include only types with related items
     * @return  string      HTML for options
     */
    public static function buildSelection($sel_type = 0, $used_only = false)
    {
        global $_TABLES;

        $retval = '';
        if ($used_only) {
            $sql = "SELECT m.id, MAX(m.dscp) AS dscp
                FROM {$_TABLES['library.types']} m";
            $sql .= " RIGHT JOIN {$_TABLES['library.items']} i
                ON i.type = m.id GROUP BY m.id";
        } else {
            $sql = "SELECT m.* FROM {$_TABLES['library.types']} m";
        }
        $res = DB_query($sql, 1);
        $A = DB_fetchAll($res);
        foreach ($A as $data) {
            $sel = $data['id'] == $sel_type ? 'selected="selected"' : '';
            $retval .= "<option value='{$data['id']}' $sel>{$data['dscp']}</option>" . LB;
        }
        return $retval;
    }


    /**
     * Determine if a specified media type can be deleted.
     * There must be one media type, so check that the ID is greater than one
     * and that it is not used by any items.
     *
     * @param   integer $id     Media type ID to check
     * @return  boolean     True if the type can be deleted
     */
    public static function canDelete($id)
    {
        return ($id > 1) && !self::isUsed($id);
    }


    /**
     *   Media Type Admin List View.
     */
    public static function adminlist()
    {
        global $_CONF, $_TABLES, $_USER;

        $display = '';
        $sql = "SELECT  *
            FROM {$_TABLES['library.types']} ";

        $header_arr = array(
            array(
                'text'  => _('Edit'),
                'field' => 'edit',
                'sort'  => false,
                'align' => 'center',
            ),
            array(
                'text'  => _('Media Type'),
                'field' => 'dscp',
                'sort'  => true,
            ),
            array(
                'text'  => _('Delete'),
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
            'table' => 'library.types',
            'sql' => $sql,
            'query_fields' => array('dscp'),
            'default_filter' => 'WHERE 1=1',
        );
        $text_arr = array(
            'has_extras' => true,
            'form_url' => Config::getInstance()->get('admin_url') . '/index.php',
        );
        $form_arr = array();
        $filter = '';
        if (!isset($_REQUEST['query_limit'])) {
            $_GET['query_limit'] = 20;
        }

        $display .= '<div class="floatright">' .
            COM_createLink(
                _('New Media Type'),
                Config::getInstance()->get('admin_url') . '/index.php?editmedia=0',
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
     * Get an individual field for the media type admin list.
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

        switch($fieldname) {
        case 'edit':
            $retval = COM_createLink(
                '<i class="uk-icon uk-icon-edit"></i>',
                Config::getInstance()->get('admin_url') . "/index.php?editmedia=x&amp;id={$A['id']}",
                array(
                    'class' => 'tooltip',
                    'title' => _('Edit'),
            ) );
            break;

        case 'delete':
            if (!self::isUsed($A['id'])) {
                $retval = COM_createLink(
                    Icon::getHTML('delete'),
                    Config::getInstance()->get('admin_url') . '/index.php?deletemedia=x&id=' . $A['id'],
                    array(
                        'onclick'=>'return confirm(\''.
                        _('Are you sure you want to delete this item?') .
                        '\');',
                        'title' => _('Delete'),
                        'class' => 'tooltip',
                    )
                );
            } else {
                $retval = Icon::getHTML('delete-grey', 'tooltip', array('title'=>_('In Use')));
            }
            break;

        default:
            $retval = htmlspecialchars($fieldvalue);
            break;
        }
        return $retval;
    }

}   // class MediaType

?>
