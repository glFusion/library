<?php
/**
*   Class to manage library media types
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
*   @package    library
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Library;

/**
*   Class for media types
*   @package library
*/
class MediaType
{
    /** Property fields.  Accessed via __set() and __get()
    *   @var array */
    var $properties;

    /** Indicate whether the current user is an administrator
    *   @var boolean */
    var $isAdmin;

    var $isNew;

    /** Array of error messages, to be accessible by the calling routines.
    *   @var array */
    public $Errors = array();


    /**
    *   Constructor.
    *   Reads in the specified class, if $id is set.  If $id is zero,
    *   then a new entry is being created.
    *
    *   @param integer $id Optional type ID
    */
    public function __construct($id=0)
    {
        $this->properties = array();

        $this->isNew = true;

        $id = (int)$id;
        if ($id < 1) {
            $this->name = '';
        } else {
            $this->id = $id;
            if (!$this->Read()) {
                $this->id = 0;
            }
        }

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
           // Integer values
            $this->properties[$var] = (int)$value;
            break;

        case 'name':
            // String values
            $this->properties[$var] = trim($value);
            break;

        default:
            // Undefined values (do nothing)
            break;
        }
    }


    /**
    *   Get the value of a property.
    *   Emulates the behaviour of __get() function in PHP 5.
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
    *   Sets all variables to the matching values from $rows
    *
    *   @param array $row Array of values, from DB or $_POST
    */
    public function SetVars($row)
    {
        if (!is_array($row)) return;

        $this->id = $row['id'];
        $this->name = $row['name'];
    }


    /**
    *   Read a specific record and populate the local values.
    *
    *   @param  integer $id Optional ID.  Current ID is used if zero.
    *   @return boolean     True if a record was read, False on failure
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

        $result = DB_query("SELECT *
                    FROM {$_TABLES['library.types']}
                    WHERE id='$id'");
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
    *   Save the current values to the database.
    *
    *   @param  array   $A      Optional array of values from $_POST
    *   @return boolean         True if no errors, False otherwise
    */
    public function Save($A = array())
    {
        global $_TABLES, $_CONF_LIB;

        if (is_array($A)) {
            $this->SetVars($A);
        }

        if ($this->isNew) {
            $sql = "INSERT INTO {$_TABLES['library.types']}
                    VALUES (0, '" . DB_escapeString($this->name) . "')";
        } else {
            $sql = "UPDATE {$_TABLES['libarary.types']}
                    SET name='" . DB_escapeString($this->name) . "'
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
    *   Delete the current media type record from the database
    */
    public function Delete()
    {
        global $_TABLES, $_CONF_LIB;

        if ($this->id <= 0)
            return false;

        $this->DeleteImage();

        DB_delete($_TABLES['library.types'],
                'id', $this->id);

        $this->id = 0;
        return true;
    }


    /**
    *   Creates the edit form.
    *
    *   @param  integer $id Optional ID, current record used if zero
    *   @return string      HTML for edit form
    */
    public function showForm()
    {
        global $_TABLES, $_CONF, $_CONF_LIB, $LANG_LIB;

        $T = LIBRARY_getTemplate('mediatype_form', 'type');

        // If we have a nonzero media type ID, then we edit the existing record.
        // Otherwise, we're creating a new item.  Also set the $not and $items
        // values to be used in the parent media type selection accordingly.
        if ($this->id > 0) {
            $retval = COM_startBlock($LANG_LIB['edit'] . ': ' . $this->name);
            $T->set_var('id', $this->id);
        } else {
            $retval = COM_startBlock($LANG_LIB['create_itemtype']);
            $T->set_var('id', '');
        }

        $T->set_var(array(
            'action_url'    => LIBRARY_ADMIN_URL,
            'name'          => $this->name,
            'candelete'     => !$this->isNew && !self::isUsed($this->id),
        ) );
        $retval .= $T->parse('output', 'type');
        $retval .= COM_endBlock();
        return $retval;
    }


    /**
    *   Determine if this product is mentioned in any purchase records.
    *   Typically used to prevent deletion of product records that have
    *   dependencies.
    *
    *   @return boolean True if used, False if not
    */
    public static function isUsed($id = 0)
    {
        global $_TABLES;

        // Check if any products are under this media type
        if (DB_count($_TABLES['library.items'], 'type', $id) > 0) {
            return true;
        }
        return false;
    }


    /**
    *   Add an error message to the Errors array.  Also could be used to
    *   log certain errors or perform other actions.
    *
    *   @param  string  $msg    Error message to append
    */
    public function AddError($msg)
    {
        $this->Errors[] = $msg;
    }


    /**
    *   Create a formatted display-ready version of the error messages.
    *
    *   @return string      Formatted error messages.
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
    *   Get the options for a selection list.
    *
    *   @param  integer $sel_type   Selected media type
    *   @return string      HTML for options
    */
    public static function buildSelection($sel_type = 0)
    {
        global $_TABLES;

        $retval = '';
        $res = DB_query("SELECT * from {$_TABLES['library.types']}", 1);
        while ($A = DB_fetchArray($res, false)) {
            $sel = $A['id'] == $sel_type ? 'selected="selected"' : '';
            $retval .= "<option value='{$A['id']}' $sel>{$A['name']}</option>" . LB;
        }
        return $retval;
    }

}   // class MediaType

?>
